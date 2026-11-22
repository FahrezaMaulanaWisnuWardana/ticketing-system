<?php

declare(strict_types=1);

use App\Enums\ProjectRole;
use App\Livewire\RequesterConversation;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('attachments');
    Notification::fake();
});

it('stores editor uploads as pending attachments visible only to their uploader', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('editor.uploads.store'), [
        'file' => UploadedFile::fake()->image('screenshot.png'),
    ])->assertCreated()->assertJsonStructure(['id', 'url', 'name', 'mime', 'size']);

    $attachment = Attachment::findOrFail($response->json('id'));
    expect($attachment->ticket_id)->toBeNull()
        ->and($attachment->user_id)->toBe($user->id)
        ->and($attachment->mime)->toBe('image/png');

    $this->actingAs($user)->get(route('attachments.show', $attachment))->assertOk();
    $this->actingAs($other)->get(route('attachments.show', $attachment))->assertForbidden();
});

it('rejects blocked file types uploaded from the editor', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('editor.uploads.store'), [
        'file' => UploadedFile::fake()->create('malware.php', 1, 'text/plain'),
    ])->assertUnprocessable();

    expect(Attachment::count())->toBe(0);
});

it('claims editor uploads when a ticket is created', function (): void {
    $requester = User::factory()->create();
    $project = Project::factory()->create();
    $this->actingAs($requester);

    $attachmentResponse = $this->postJson(route('editor.uploads.store'), [
        'file' => UploadedFile::fake()->image('evidence.png'),
    ])->assertCreated();
    $attachment = Attachment::findOrFail($attachmentResponse->json('id'));

    $this->post(route('tickets.store'), [
        'project_id' => $project->id,
        'kind' => 'request',
        'title' => 'Screen is broken',
        'description' => '<p>See <img src="'.$attachmentResponse->json('url').'" alt="evidence"></p>',
        'attachment_ids' => (string) $attachment->id,
    ])->assertRedirect();

    $ticket = Ticket::where('title', 'Screen is broken')->firstOrFail();
    expect($attachment->fresh()->ticket_id)->toBe($ticket->id);
});

it('does not claim files uploaded by somebody else', function (): void {
    $requester = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($other)->postJson(route('editor.uploads.store'), [
        'file' => UploadedFile::fake()->image('theirs.png'),
    ])->assertCreated();
    $attachment = Attachment::where('user_id', $other->id)->firstOrFail();

    $this->actingAs($requester)->post(route('tickets.store'), [
        'project_id' => $project->id,
        'kind' => 'request',
        'title' => 'Not my upload',
        'attachment_ids' => (string) $attachment->id,
    ])->assertRedirect();

    expect($attachment->fresh()->ticket_id)->toBeNull();
});

it('claims editor uploads when a requester replies', function (): void {
    $requester = User::factory()->create();
    $project = Project::factory()->create(['key' => 'IT']);
    $project->members()->attach($requester->id, ['role' => ProjectRole::Member->value]);
    $ticket = Ticket::factory()->create([
        'project_id' => $project->id,
        'requester_id' => $requester->id,
        'reporter_id' => $requester->id,
        'assignee_id' => null,
    ]);

    $this->actingAs($requester);
    $upload = $this->postJson(route('editor.uploads.store'), [
        'file' => UploadedFile::fake()->image('reply.png'),
    ])->assertCreated();
    $attachment = Attachment::findOrFail($upload->json('id'));

    Livewire::test(RequesterConversation::class, ['ticket' => $ticket->id])
        ->set('replyBody', '<p>Here is the screenshot.</p>')
        ->set('replyAttachmentIds', (string) $attachment->id)
        ->call('reply')
        ->assertHasNoErrors();

    $attachment->refresh();
    expect($attachment->ticket_id)->toBe($ticket->id)
        ->and($attachment->comment_id)->not->toBeNull();
});
