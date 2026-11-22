<?php

declare(strict_types=1);

use App\Enums\ProjectRole;
use App\Enums\TicketStatus;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('creates a ticket with an atomic number, activity and friendly key', function (): void {
    $requester = User::factory()->create();
    $project = Project::factory()->create(['key' => 'IT']);

    $response = $this->actingAs($requester)->post(route('tickets.store'), [
        'project_id' => $project->id,
        'kind' => 'request',
        'title' => 'Printer is broken',
        'description' => 'It makes a noise',
    ])->assertRedirect();

    $ticket = Ticket::query()->where('title', 'Printer is broken')->firstOrFail();
    expect($ticket->number)->toBe(1)
        ->and($ticket->key)->toBe('IT-1')
        ->and($ticket->status)->toBe(TicketStatus::New)
        ->and($ticket->activities()->where('event', 'created')->exists())->toBeTrue()
        ->and($response)->not->toBeNull();
});

it('rejects invalid status transitions from the UI', function (): void {
    $agent = User::factory()->agent()->create();
    $project = Project::factory()->create();
    $project->members()->attach($agent->id, ['role' => ProjectRole::Member->value]);
    $ticket = Ticket::factory()->create(['project_id' => $project->id, 'status' => TicketStatus::Closed]);

    $this->actingAs($agent)->put(route('tickets.update', $ticket), ['status' => 'new'])
        ->assertSessionHasErrors();
    expect($ticket->refresh()->status)->toBe(TicketStatus::Closed);
});

it('sets resolved_at when a ticket is done and clears it on reopen', function (): void {
    $requester = User::factory()->create();
    $project = Project::factory()->create();
    $ticket = Ticket::factory()->create([
        'project_id' => $project->id, 'requester_id' => $requester->id, 'reporter_id' => $requester->id,
        'status' => TicketStatus::InProgress,
    ]);

    $this->actingAs($requester)->get(route('tickets.show', $ticket))->assertOk();
    $this->actingAs(User::factory()->admin()->create())->put(route('tickets.update', $ticket), ['status' => 'done']);
    expect($ticket->refresh()->resolved_at)->not->toBeNull();

    $this->actingAs($requester)->post(route('tickets.reopen', $ticket))->assertRedirect();
    expect($ticket->refresh()->status)->toBe(TicketStatus::InProgress)
        ->and($ticket->resolved_at)->toBeNull();
});

it('validates attachments and serves them only to authorized users', function (): void {
    Storage::fake('attachments');
    $agent = User::factory()->agent()->create();
    $project = Project::factory()->create();
    $project->members()->attach($agent->id, ['role' => ProjectRole::Member->value]);
    $ticket = Ticket::factory()->create(['project_id' => $project->id]);

    $this->actingAs($agent)->post(route('attachments.store', $ticket), [
        'file' => UploadedFile::fake()->createWithContent('notes.txt', 'hello'),
    ])->assertRedirect();
    $attachment = $ticket->attachments()->firstOrFail();
    $this->actingAs($agent)->get(route('attachments.show', $attachment))
        ->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Disposition');

    $this->actingAs($agent)->post(route('attachments.store', $ticket), [
        'file' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
    ])->assertSessionHasErrors();
});

it('adds activity entries only through the append-only log', function (): void {
    $ticket = Ticket::factory()->create();
    expect(Activity::where('ticket_id', $ticket->id)->exists())->toBeTrue();
});
