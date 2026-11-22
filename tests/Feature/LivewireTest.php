<?php

declare(strict_types=1);

use App\Enums\ProjectRole;
use App\Livewire\QuickCreate;
use App\Livewire\TicketDetail;
use App\Livewire\TicketList;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('allows an agent to assign a ticket to themselves from the detail component', function (): void {
    $agent = User::factory()->agent()->create();
    $project = Project::factory()->create();
    $project->members()->attach($agent->id, ['role' => ProjectRole::Member->value]);
    $ticket = Ticket::factory()->create(['project_id' => $project->id]);

    Livewire::actingAs($agent)->test(TicketDetail::class, ['ticket' => $ticket->id])
        ->call('assignToMe')
        ->assertHasNoErrors();

    expect($ticket->refresh()->assignee_id)->toBe($agent->id);
});

it('creates a ticket from the quick create modal', function (): void {
    $agent = User::factory()->agent()->create();
    $project = Project::factory()->create();

    Livewire::actingAs($agent)->test(QuickCreate::class)
        ->set('projectId', $project->id)
        ->set('title', 'Quick ticket')
        ->call('create')
        ->assertHasNoErrors();

    expect(Ticket::where('title', 'Quick ticket')->exists())->toBeTrue();
});

it('does not expose other tickets through ticket list scoping', function (): void {
    $requester = User::factory()->create();
    $other = User::factory()->create();
    Project::factory()->create();
    $mine = Ticket::factory()->create(['requester_id' => $requester->id, 'reporter_id' => $requester->id, 'title' => 'My own request']);
    Ticket::factory()->create(['requester_id' => $other->id, 'reporter_id' => $other->id, 'title' => 'Secret request']);

    Livewire::actingAs($requester)->test(TicketList::class)
        ->assertSee('My own request')
        ->assertDontSee('Secret request')
        ->assertSet('search', '');
});
