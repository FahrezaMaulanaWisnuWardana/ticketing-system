<?php

declare(strict_types=1);

use App\Enums\ProjectRole;
use App\Livewire\TicketDetail;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function projectWithAgent(User $agent): Project
{
    $project = Project::factory()->create();
    $project->members()->attach($agent->id, ['role' => ProjectRole::Member->value]);

    return $project;
}

it('prevents a requester from viewing another requesters ticket', function (): void {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $project = Project::factory()->create();
    $ticket = Ticket::factory()->create(['project_id' => $project->id, 'requester_id' => $b->id, 'reporter_id' => $b->id]);

    $this->actingAs($a)->get(route('tickets.show', $ticket))->assertNotFound();
    expect(Ticket::query()->whereKey($ticket->id)->exists())->toBeFalse();
});

it('shows only tickets reported by the requester on the my requests pages', function (): void {
    $requester = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->create();

    Ticket::factory()->create([
        'project_id' => $project->id,
        'reporter_id' => $requester->id,
        'requester_id' => $requester->id,
        'title' => 'Ticket I reported',
    ]);
    $onMyBehalf = Ticket::factory()->create([
        'project_id' => $project->id,
        'reporter_id' => $other->id,
        'requester_id' => $requester->id,
        'title' => 'Ticket filed for me by someone else',
    ]);

    $this->actingAs($requester)->get(route('home'))
        ->assertOk()
        ->assertSee('Ticket I reported')
        ->assertDontSee('Ticket filed for me by someone else');

    $this->actingAs($requester)->get(route('requester.requests'))
        ->assertOk()
        ->assertSee('Ticket I reported')
        ->assertDontSee('Ticket filed for me by someone else');

    // The list narrows to reported tickets, but access to a ticket filed on
    // the requester's behalf is unchanged in the global scope and policy.
    $this->actingAs($requester)->get(route('tickets.show', $onMyBehalf))->assertOk();
});

it('hides internal notes and attachments from requesters', function (): void {
    $requester = User::factory()->create();
    $agent = User::factory()->agent()->create();
    $project = projectWithAgent($agent);
    $ticket = Ticket::factory()->create([
        'project_id' => $project->id, 'requester_id' => $requester->id, 'reporter_id' => $requester->id,
    ]);
    $internal = Comment::factory()->internal()->create(['ticket_id' => $ticket->id, 'user_id' => $agent->id]);
    $public = Comment::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $agent->id, 'body' => 'Public answer']);
    $attachment = Attachment::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $agent->id]);

    $this->actingAs($requester)->get(route('tickets.show', $ticket))->assertOk();
    Livewire::actingAs($requester)->test(TicketDetail::class, ['ticket' => $ticket->id])
        ->assertSee('Public answer')
        ->assertDontSee($internal->body);

    $outsider = User::factory()->create();
    $this->actingAs($outsider)->get(route('attachments.show', $attachment))->assertForbidden();
});

it('hides projects from agents who are not members', function (): void {
    $agent = User::factory()->agent()->create();
    $project = Project::factory()->create();
    $ticket = Ticket::factory()->create(['project_id' => $project->id]);

    $this->actingAs($agent)->get(route('projects.show', $project))->assertForbidden();
    $this->actingAs($agent)->get(route('tickets.show', $ticket))->assertNotFound();
});

it('lets admins see everything', function (): void {
    $admin = User::factory()->admin()->create();
    $project = Project::factory()->create();
    $ticket = Ticket::factory()->create(['project_id' => $project->id]);

    $this->actingAs($admin)->get(route('projects.show', $project))->assertOk();
    $this->actingAs($admin)->get(route('tickets.show', $ticket))->assertOk();
});

it('denies requesters administrative and project management actions', function (): void {
    $requester = User::factory()->create();
    $project = Project::factory()->create();
    $ticket = Ticket::factory()->create(['project_id' => $project->id, 'requester_id' => $requester->id]);

    $this->actingAs($requester)
        ->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($requester)->get(route('projects.create'))->assertForbidden();
    $this->actingAs($requester)->post(route('tickets.store'), [
        'project_id' => $project->id, 'kind' => 'dev', 'title' => 'Nope',
    ])->assertSessionHasErrors('kind');
    $this->actingAs($requester)->delete(route('tickets.destroy', $ticket))->assertForbidden();
});

it('rejects tampered Livewire ticket ids', function (): void {
    $attacker = User::factory()->create();
    $victim = User::factory()->create();
    $ticket = Ticket::factory()->create(['requester_id' => $victim->id, 'reporter_id' => $victim->id]);

    Livewire::actingAs($attacker)->test(TicketDetail::class, ['ticket' => $ticket->id])
        ->assertForbidden();
});

it('gates health page to admins while /up stays public', function (): void {
    $requester = User::factory()->create();

    $this->get('/up')->assertOk();
    $this->actingAs($requester)->get(route('health'))->assertForbidden();
    Setting::set('health.scheduler_at', now()->toIso8601String());
    Setting::set('health.last_backup_at', now()->toIso8601String());
    $this->actingAs(User::factory()->admin()->create())->get(route('health'))->assertOk();
});
