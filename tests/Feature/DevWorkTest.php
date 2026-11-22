<?php

declare(strict_types=1);

use App\Actions\ChangeRank;
use App\Enums\ProjectRole;
use App\Enums\TicketStatus;
use App\Events\TicketMoved;
use App\Livewire\Backlog;
use App\Livewire\Board;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\SprintSnapshot;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function devSetup(): array
{
    $lead = User::factory()->agent()->create();
    $agent = User::factory()->agent()->create();
    $requester = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $lead->id]);
    $project->members()->attach($lead->id, ['role' => ProjectRole::Lead->value]);
    $project->members()->attach($agent->id, ['role' => ProjectRole::Member->value]);

    return [$lead, $agent, $requester, $project];
}

it('starts one sprint at a time and completes it transactionally', function (): void {
    [$lead, $agent, $requester, $project] = devSetup();
    $sprint = Sprint::factory()->create(['project_id' => $project->id]);
    $done = Ticket::factory()->dev()->create([
        'project_id' => $project->id, 'sprint_id' => $sprint->id, 'status' => TicketStatus::Done, 'estimate' => 3,
    ]);
    $open = Ticket::factory()->dev()->create([
        'project_id' => $project->id, 'sprint_id' => $sprint->id, 'status' => TicketStatus::InProgress, 'estimate' => 5,
    ]);
    $next = Sprint::factory()->create(['project_id' => $project->id, 'name' => 'Next']);

    $this->actingAs($lead)->post(route('dev.sprints.start', [$project, $sprint]))->assertRedirect();
    expect($sprint->refresh()->state)->toBe('active');

    $other = Sprint::factory()->create(['project_id' => $project->id]);
    $this->actingAs($lead)->post(route('dev.sprints.start', [$project, $other]))->assertSessionHasErrors();

    $this->actingAs($lead)->post(route('dev.sprints.complete', [$project, $sprint]))->assertRedirect();
    expect($sprint->refresh()->state)->toBe('closed')
        ->and($open->refresh()->sprint_id)->toBe($next->id)
        ->and($done->refresh()->sprint_id)->toBe($sprint->id)
        ->and(SprintSnapshot::where('sprint_id', $sprint->id)->exists())->toBeTrue();
});

it('moves a ticket on the board via a keyboard-friendly action and broadcasts it', function (): void {
    [$lead, $agent, $requester, $project] = devSetup();
    Event::fake([TicketMoved::class]);
    $ticket = Ticket::factory()->dev()->create([
        'project_id' => $project->id, 'status' => TicketStatus::Todo, 'assignee_id' => $agent->id,
    ]);

    Livewire::actingAs($agent)->test(Board::class, ['project' => $project->id])
        ->call('moveTicket', $ticket->id, 'in_progress')
        ->assertHasNoErrors();

    expect($ticket->refresh()->status)->toBe(TicketStatus::InProgress);
    Event::assertDispatched(TicketMoved::class);
});

it('denies requesters board moves', function (): void {
    [$lead, $agent, $requester, $project] = devSetup();
    $ticket = Ticket::factory()->dev()->create(['project_id' => $project->id, 'status' => TicketStatus::Todo]);

    Livewire::actingAs($requester)->test(Board::class, ['project' => $project->id])
        ->call('moveTicket', $ticket->id, 'in_progress')
        ->assertForbidden();
});

it('updates ranks without N+1 lazy-loading violations', function (): void {
    [$lead, $agent, $requester, $project] = devSetup();
    $tickets = Ticket::factory()->dev()->count(3)->create(['project_id' => $project->id]);
    $ids = array_values($tickets->pluck('id')->reverse()->all());

    app(ChangeRank::class)($lead, $ids);

    expect(Ticket::whereIn('id', $ids)->orderBy('rank')->pluck('id')->all())->toBe($ids);

    Livewire::actingAs($lead)->test(Backlog::class, ['project' => $project->id])
        ->assertOk();
});

it('renders the board with eager-loaded relations', function (): void {
    [$lead, $agent, $requester, $project] = devSetup();
    $ticket = Ticket::factory()->dev()->create([
        'project_id' => $project->id, 'status' => TicketStatus::InProgress, 'assignee_id' => $agent->id,
    ]);

    Livewire::actingAs($lead)->test(Board::class, ['project' => $project->id])
        ->assertSee($ticket->title);
});
