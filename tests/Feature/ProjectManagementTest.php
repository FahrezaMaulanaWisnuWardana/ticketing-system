<?php

declare(strict_types=1);

use App\Actions\CreateDevTicketFromRequest;
use App\Actions\CreateProject;
use App\Domain\ProgressCalculator;
use App\Domain\ProjectHealth;
use App\Enums\ProjectRole;
use App\Enums\TicketStatus;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketLink;
use App\Models\User;
use App\Services\ProjectMetricsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('computes progress and health from time elapsed and overdue milestones', function (): void {
    expect(ProgressCalculator::percent(1, 4))->toBe(25)
        ->and(ProjectHealth::calculate(90, CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-30'), false))->toBe('on_track')
        ->and(ProjectHealth::calculate(10, CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-30'), false))->toBe('at_risk')
        ->and(ProjectHealth::calculate(90, CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-30'), true))->toBe('late');
});

it('seeds categories and labels from project templates', function (): void {
    $user = User::factory()->admin()->create();
    $project = app(CreateProject::class)($user, [
        'name' => 'Helpdesk', 'key' => 'HD', 'template' => 'helpdesk',
    ]);

    expect($project->categories()->count())->toBe(3)
        ->and($project->labels()->count())->toBe(3)
        ->and($project->members()->first()->pivot->role->value)->toBe('lead');
});

it('links helpdesk requests to dev tickets and notifies on completion', function (): void {
    $agent = User::factory()->agent()->create();
    $project = Project::factory()->create();
    $project->members()->attach($agent->id, ['role' => ProjectRole::Member->value]);
    $request = Ticket::factory()->create(['project_id' => $project->id, 'requester_id' => $agent->id, 'title' => 'Broken printer']);

    $dev = app(CreateDevTicketFromRequest::class)($request, $agent);

    expect($dev->kind->value)->toBe('dev')
        ->and(TicketLink::where('from_id', $request->id)->where('to_id', $dev->id)->where('type', 'spawned')->exists())->toBeTrue()
        ->and(TicketLink::where('from_id', $dev->id)->where('to_id', $request->id)->where('type', 'caused_by')->exists())->toBeTrue();

    $this->actingAs($agent)->put(route('tickets.update', $dev), ['status' => 'done']);

    expect($request->comments()->where('is_internal', true)->where('body', 'like', '%'.$dev->key.'%')->exists())->toBeTrue();
});

it('caches project metrics and invalidates them when a ticket changes', function (): void {
    $project = Project::factory()->create(['start_date' => now()->subDays(5), 'due_date' => now()->addDays(5)]);
    $ticket = Ticket::factory()->create(['project_id' => $project->id]);
    $service = app(ProjectMetricsService::class);

    expect($service->for($project)['total'])->toBe(1);
    $ticket->update(['status' => TicketStatus::Done]);
    expect($service->for($project)['done'])->toBe(1);

    Milestone::create(['project_id' => $project->id, 'name' => 'Launch', 'due_date' => now()->subDay()]);
    expect($service->for($project)['health'])->toBe('late');
});

it('summarizes project work and recent activity for the selected period', function (): void {
    $lead = User::factory()->agent()->create(['theme' => 'light']);
    $project = Project::factory()->create(['owner_id' => $lead->id]);
    $project->members()->attach($lead->id, ['role' => ProjectRole::Lead->value]);
    $this->actingAs($lead);

    $done = Ticket::factory()->create([
        'project_id' => $project->id, 'reporter_id' => $lead->id, 'requester_id' => $lead->id,
        'status' => TicketStatus::Done, 'title' => 'Finished this week',
    ]);
    $done->update(['resolved_at' => now()->subDay()]);
    Ticket::factory()->create([
        'project_id' => $project->id, 'reporter_id' => $lead->id, 'requester_id' => $lead->id,
        'status' => TicketStatus::Closed,
    ])->update(['resolved_at' => now()->subDays(15)]);
    Ticket::factory()->create([
        'project_id' => $project->id, 'reporter_id' => $lead->id, 'requester_id' => $lead->id,
        'status' => TicketStatus::Todo, 'due_date' => now()->addDays(3),
    ]);

    $response = $this->get(route('projects.overview', ['project' => $project, 'days' => 7]))
        ->assertOk()
        ->assertSee(__('pm.summary.title'))
        ->assertSee($done->key);

    $summary = $response->viewData('summary');
    expect($summary['days'])->toBe(7)
        ->and($summary['completed'])->toBe(1)
        ->and($summary['created'])->toBe(3)
        ->and($summary['due_soon'])->toBe(1)
        ->and($summary['total'])->toBe(3)
        // Done + Closed and Todo + InProgress are now grouped into two buckets.
        ->and($summary['statuses'])->toHaveCount(2)
        ->and($summary['activities'])->not->toBeEmpty();

    $wider = $this->get(route('projects.overview', ['project' => $project, 'days' => 30]))
        ->assertOk()
        ->viewData('summary');
    expect($wider['days'])->toBe(30)
        ->and($wider['completed'])->toBe(2);
});

it('renders overview, epics, timeline and workload pages for project members', function (): void {
    $lead = User::factory()->agent()->create();
    $project = Project::factory()->create(['owner_id' => $lead->id]);
    $project->members()->attach($lead->id, ['role' => ProjectRole::Lead->value]);
    Ticket::factory()->dev()->create(['project_id' => $project->id, 'assignee_id' => $lead->id, 'estimate' => 3]);

    $this->actingAs($lead)->get(route('projects.overview', $project))->assertOk();
    $this->actingAs($lead)->get(route('projects.epics', $project))->assertOk();
    $this->actingAs($lead)->get(route('projects.timeline', $project))->assertOk();
    $this->actingAs($lead)->get(route('projects.workload', $project))->assertOk();
});
