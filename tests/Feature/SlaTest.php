<?php

declare(strict_types=1);

use App\Actions\AddComment;
use App\Enums\ProjectRole;
use App\Enums\TicketStatus;
use App\Jobs\CheckSlaJob;
use App\Models\Holiday;
use App\Models\Project;
use App\Models\Setting;
use App\Models\SlaEvent;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SlaService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
});

it('computes SLA due dates on creation and recalculates on priority change', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($user)->post(route('tickets.store'), [
        'project_id' => $project->id, 'kind' => 'request', 'title' => 'SLA ticket', 'priority' => 'normal',
    ])->assertRedirect();
    $ticket = Ticket::where('title', 'SLA ticket')->firstOrFail();
    $firstDue = $ticket->sla_resolve_due_at?->toIso8601String();
    expect($firstDue)->not->toBeNull();

    $this->actingAs(User::factory()->admin()->create())->put(route('tickets.update', $ticket), ['priority' => 'urgent']);
    expect($ticket->refresh()->sla_resolve_due_at?->toIso8601String())->not->toBe($firstDue);
});

it('pauses the clock on waiting requester and resumes it when the requester replies', function (): void {
    $requester = User::factory()->create();
    $agent = User::factory()->agent()->create();
    $project = Project::factory()->create();
    $project->members()->attach($agent->id, ['role' => ProjectRole::Member->value]);
    $ticket = Ticket::factory()->create([
        'project_id' => $project->id, 'requester_id' => $requester->id, 'reporter_id' => $requester->id,
        'status' => TicketStatus::InProgress,
    ]);
    app(SlaService::class)->apply($ticket);
    $due = $ticket->sla_resolve_due_at?->copy();

    $this->actingAs($agent)->put(route('tickets.update', $ticket), ['status' => 'waiting_requester']);
    expect($ticket->refresh()->sla_paused_at)->not->toBeNull();

    $ticket->forceFill(['sla_paused_at' => CarbonImmutable::parse('2026-11-20 16:00:00', 'Asia/Jakarta'), 'sla_resolve_due_at' => $due])->save();
    app(AddComment::class)($ticket->refresh(), $requester, 'Here is the info');

    expect($ticket->refresh()->status)->toBe(TicketStatus::InProgress)
        ->and($ticket->sla_paused_at)->toBeNull()
        ->and($ticket->sla_paused_seconds)->toBeGreaterThan(0)
        ->and($ticket->sla_resolve_due_at?->timestamp)->not->toBe($due?->timestamp);
});

it('fires SLA events only once per ticket and channel', function (): void {
    $agent = User::factory()->agent()->create();
    $project = Project::factory()->create();
    $project->members()->attach($agent->id, ['role' => ProjectRole::Member->value]);
    $ticket = Ticket::factory()->create([
        'project_id' => $project->id, 'assignee_id' => $agent->id, 'created_at' => now()->subDays(5),
    ]);
    app(SlaService::class)->apply($ticket);
    $ticket->forceFill(['sla_resolve_due_at' => now()->subHour()])->save();

    (new CheckSlaJob)->handle(app(SlaService::class));
    (new CheckSlaJob)->handle(app(SlaService::class));

    expect(SlaEvent::where('ticket_id', $ticket->id)->where('channel', 'resolve')->count())->toBe(1)
        ->and(SlaEvent::where('ticket_id', $ticket->id)->count())->toBe(2);
});

it('lets admins manage SLA settings and holidays only', function (): void {
    $this->actingAs(User::factory()->create())->get(route('admin.settings.sla'))->assertForbidden();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->put(route('admin.settings.sla.update'), [
        'business_start' => '09:00', 'business_end' => '17:00', 'workdays' => ['1', '2', '3', '4', '5'],
        'urgent_24x7' => '1',
        'targets' => [
            'urgent' => ['response' => 30, 'resolve' => 240],
            'high' => ['response' => 120, 'resolve' => 480],
            'normal' => ['response' => 480, 'resolve' => 1440],
            'low' => ['response' => 960, 'resolve' => 2400],
        ],
    ])->assertRedirect();
    expect(Setting::get('sla.business_hours')['start'])->toBe('09:00');

    Holiday::create(['date' => now()->addDay()->toDateString(), 'name' => 'Libur']);
    expect(app(SlaService::class)->configuration()->holidays)->toHaveCount(1);
});
