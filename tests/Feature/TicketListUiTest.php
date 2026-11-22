<?php

declare(strict_types=1);

use App\Enums\TicketStatus;
use App\Livewire\TicketList;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('uses the top bar q parameter as the single ticket list search', function (): void {
    $admin = User::factory()->admin()->create();
    $project = Project::factory()->create();
    Ticket::factory()->create(['project_id' => $project->id, 'title' => 'Printer rusak']);
    Ticket::factory()->create(['project_id' => $project->id, 'title' => 'Keyboard rusak']);

    $this->actingAs($admin)
        ->get(route('tickets.index', ['q' => 'Printer']))
        ->assertOk()
        ->assertSee('Printer rusak')
        ->assertDontSee('Keyboard rusak');
});

it('groups legacy statuses into the five canonical statuses', function (): void {
    expect(TicketStatus::canonicalCases())->toHaveCount(5)
        ->and(TicketStatus::Backlog->group())->toBe(TicketStatus::InProgress)
        ->and(TicketStatus::Todo->group())->toBe(TicketStatus::InProgress)
        ->and(TicketStatus::Closed->group())->toBe(TicketStatus::Done)
        ->and(TicketStatus::legacyMap())->toBe([
            'backlog' => 'in_progress',
            'todo' => 'in_progress',
            'closed' => 'done',
        ]);
});

it('includes legacy status values when filtering by a canonical status', function (): void {
    $admin = User::factory()->admin()->create();
    $project = Project::factory()->create();
    Ticket::factory()->create([
        'project_id' => $project->id, 'status' => TicketStatus::Backlog, 'title' => 'Tiket antrean lama',
    ]);
    Ticket::factory()->create([
        'project_id' => $project->id, 'status' => TicketStatus::New, 'title' => 'Tiket baru sekali',
    ]);

    Livewire::actingAs($admin)->test(TicketList::class)
        ->set('status', TicketStatus::InProgress->value)
        ->assertSee('Tiket antrean lama')
        ->assertDontSee('Tiket baru sekali');
});

it('sorts by priority descending and then by most recently updated by default', function (): void {
    $admin = User::factory()->admin()->create();
    $project = Project::factory()->create();
    Ticket::factory()->create([
        'project_id' => $project->id, 'priority' => 'normal', 'title' => 'Tiket normal terbaru',
        'updated_at' => now(),
    ]);
    Ticket::factory()->create([
        'project_id' => $project->id, 'priority' => 'urgent', 'title' => 'Tiket mendesak lama',
        'updated_at' => now()->subDay(),
    ]);

    Livewire::actingAs($admin)->test(TicketList::class)
        ->assertSeeHtmlInOrder(['Tiket mendesak lama', 'Tiket normal terbaru']);
});

it('paginates server side with a selectable per page count', function (): void {
    $admin = User::factory()->admin()->create();
    $project = Project::factory()->create();
    Ticket::factory()->count(30)->create(['project_id' => $project->id]);

    Livewire::actingAs($admin)->test(TicketList::class)
        ->assertSeeHtml('Menampilkan 1-25 dari 30 tiket')
        ->set('perPage', 50)
        ->assertSeeHtml('Menampilkan 1-30 dari 30 tiket');
});

it('clears every filter and restores the default sort on reset', function (): void {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)->test(TicketList::class)
        ->set('search', 'printer')
        ->set('status', 'new')
        ->set('priority', 'urgent')
        ->set('sort', 'updated_at')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('status', '')
        ->assertSet('priority', '')
        ->assertSet('sort', 'priority')
        ->assertSet('direction', 'desc')
        ->assertSet('perPage', 25);
});
