<?php

declare(strict_types=1);

use App\Enums\Priority;
use App\Enums\ProjectRole;
use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function portalProject(): Project
{
    return Project::factory()->create(['key' => 'IT']);
}

it('renders the portal home without a ticket table and with grouped request types', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk()
        ->assertDontSee('<table', false)
        ->assertSee((string) __('portal.home.heading'))
        ->assertSee((string) __('portal.home.search_placeholder'))
        ->assertSee((string) __('portal.groups.it'))
        ->assertSee((string) __('portal.types.gangguan_it.label'))
        ->assertSee(route('tickets.create', ['type' => 'gangguan_it']), false)
        ->assertSee(route('requester.requests'), false);
});

it('searches request types and help articles through the suggest endpoint', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson(route('portal.suggest', ['q' => 'email']))
        ->assertOk()
        ->assertJsonPath('types.0.key', 'akses_akun')
        ->assertJsonStructure(['types', 'articles']);

    $this->actingAs($user)->getJson(route('portal.suggest', ['q' => str_repeat('x', 300)]))
        ->assertOk()
        ->assertJson(['types' => [], 'articles' => []]);
});

it('shows open requests by default and supports status, type and text filters', function (): void {
    $user = User::factory()->create();
    $project = portalProject();

    $open = Ticket::factory()->create([
        'project_id' => $project->id, 'reporter_id' => $user->id, 'requester_id' => $user->id,
        'status' => TicketStatus::InProgress, 'type' => 'gangguan_it', 'title' => 'Koneksi VPN terputus',
    ]);
    $done = Ticket::factory()->create([
        'project_id' => $project->id, 'reporter_id' => $user->id, 'requester_id' => $user->id,
        'status' => TicketStatus::Done, 'type' => 'bug_aplikasi', 'title' => 'Laporan bulanan sudah diperbaiki',
    ]);

    $this->actingAs($user)->get(route('requester.requests'))
        ->assertOk()
        ->assertSee('Koneksi VPN terputus')
        ->assertDontSee('Laporan bulanan sudah diperbaiki');

    $this->actingAs($user)->get(route('requester.requests', ['status' => 'done']))
        ->assertOk()
        ->assertSee('Laporan bulanan sudah diperbaiki')
        ->assertDontSee('Koneksi VPN terputus');

    $this->actingAs($user)->get(route('requester.requests', ['status' => 'all', 'type' => 'bug_aplikasi']))
        ->assertOk()
        ->assertSee('Laporan bulanan sudah diperbaiki')
        ->assertDontSee('Koneksi VPN terputus');

    $this->actingAs($user)->get(route('requester.requests', ['q' => 'VPN']))
        ->assertOk()
        ->assertSee('Koneksi VPN terputus')
        ->assertDontSee('Laporan bulanan sudah diperbaiki');

    expect($open->status->isOpen())->toBeTrue()
        ->and($done->status->isFinal())->toBeTrue();
});

it('sanitizes untrusted filter values instead of failing', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('requester.requests', [
        'status' => 'drop_table', 'sort' => 'password', 'dir' => 'sideways',
        'type' => '../../etc/passwd', 'creator' => 'everyone', 'q' => str_repeat('x', 300),
    ]))->assertOk();
});

it('paginates the request list at 20 per page and keeps filters in the query string', function (): void {
    $user = User::factory()->create();
    $project = portalProject();

    for ($i = 0; $i < 25; $i++) {
        Ticket::factory()->create([
            'project_id' => $project->id, 'reporter_id' => $user->id, 'requester_id' => $user->id,
            'status' => TicketStatus::New,
        ]);
    }

    $this->actingAs($user)->get(route('requester.requests', ['status' => 'open', 'type' => 'gangguan_it']))
        ->assertOk()
        ->assertViewHas('tickets', static function ($tickets): bool {
            return $tickets->perPage() === 20
                && $tickets->total() === 0
                && $tickets->count() === 0;
        });

    $this->actingAs($user)->get(route('requester.requests', ['status' => 'open']))
        ->assertOk()
        ->assertViewHas('tickets', static function ($tickets): bool {
            return $tickets->perPage() === 20
                && $tickets->total() === 25
                && $tickets->count() === 20;
        })
        ->assertSee('page=2', false);
});

it('never exposes another users requests in the portal list', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = portalProject();

    Ticket::factory()->create([
        'project_id' => $project->id, 'reporter_id' => $user->id, 'requester_id' => $user->id,
        'title' => 'Permintaan saya sendiri',
    ]);
    Ticket::factory()->create([
        'project_id' => $project->id, 'reporter_id' => $other->id, 'requester_id' => $other->id,
        'title' => 'Permintaan orang lain',
    ]);

    $this->actingAs($user)->get(route('requester.requests'))
        ->assertOk()
        ->assertSee('Permintaan saya sendiri')
        ->assertDontSee('Permintaan orang lain');
});

it('sorts tickets needing a reply first in the default view', function (): void {
    $user = User::factory()->create();
    $project = portalProject();

    Ticket::factory()->create([
        'project_id' => $project->id, 'reporter_id' => $user->id, 'requester_id' => $user->id,
        'title' => 'Permintaan biasa', 'status' => TicketStatus::New, 'created_at' => now(),
    ]);
    Ticket::factory()->create([
        'project_id' => $project->id, 'reporter_id' => $user->id, 'requester_id' => $user->id,
        'title' => 'Permintaan yang menunggu balasan', 'status' => TicketStatus::WaitingRequester,
        'created_at' => now()->subDay(),
    ]);

    $this->actingAs($user)->get(route('requester.requests'))
        ->assertOk()
        ->assertViewHas('tickets', static function ($tickets): bool {
            $titles = $tickets->pluck('title')->all();

            return $titles[0] === 'Permintaan yang menunggu balasan'
                && $titles[1] === 'Permintaan biasa';
        });
});

it('gates the agent queues and supports bulk assign and status changes', function (): void {
    $requester = User::factory()->create();
    $agent = User::factory()->agent()->create();
    $project = portalProject();
    $project->members()->attach($agent->id, ['role' => ProjectRole::Member->value]);

    $ticket = Ticket::factory()->create([
        'project_id' => $project->id, 'reporter_id' => $requester->id, 'requester_id' => $requester->id,
        'status' => TicketStatus::New, 'priority' => Priority::Normal,
    ]);

    $this->actingAs($requester)->get(route('agent.queue'))->assertForbidden();
    $this->actingAs($agent)->get(route('agent.queue'))->assertOk()
        ->assertSee((string) __('portal.queues.all_open'))
        ->assertSee((string) __('portal.queues.unassigned'));

    $this->actingAs($agent)->post(route('agent.queue.bulk.assign'), [
        'action' => 'assign', 'tickets' => [$ticket->id], 'assignee_id' => $agent->id,
    ])->assertRedirect();

    expect($ticket->fresh()->assignee_id)->toBe($agent->id);

    $this->actingAs($agent)->post(route('agent.queue.bulk.status'), [
        'action' => 'status', 'tickets' => [$ticket->id], 'status' => TicketStatus::InProgress->value,
    ])->assertRedirect();

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress);

    $this->actingAs($agent)->post(route('agent.queue.bulk.status'), [
        'action' => 'status', 'tickets' => [$ticket->id], 'status' => TicketStatus::Done->value,
    ])->assertRedirect();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Done)
        ->and($ticket->fresh()->resolved_at)->not->toBeNull();

    $this->actingAs($agent)->post(route('agent.queue.bulk.status'), [
        'action' => 'status', 'tickets' => [$ticket->id], 'status' => TicketStatus::New->value,
    ])->assertSessionHasErrors('status');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Done);

    $this->actingAs($requester)->post(route('agent.queue.bulk.assign'), [
        'action' => 'assign', 'tickets' => [$ticket->id], 'assignee_id' => $requester->id,
    ])->assertForbidden();
});

it('uses one label, icon and colour for each status category', function (): void {
    $seen = [];
    foreach (TicketStatus::cases() as $case) {
        $category = $case->category();
        $signature = [$category->labelKey(), $category->iconPath(), $category->tone()];

        if (isset($seen[$category->value])) {
            expect($signature)->toBe($seen[$category->value]);
        } else {
            $seen[$category->value] = $signature;
        }
    }

    expect($seen)->toHaveCount(4);
});
