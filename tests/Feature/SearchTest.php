<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Livewire\GlobalSearch;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('finds tickets by title, description and comments with the policy scope applied', function (): void {
    $requester = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->create(['key' => 'ABC']);

    $mine = Ticket::factory()->create([
        'project_id' => $project->id, 'requester_id' => $requester->id, 'reporter_id' => $requester->id,
        'title' => 'Unique printer problem', 'description' => 'Makes a grinding sound',
    ]);
    $hidden = Ticket::factory()->create([
        'project_id' => $project->id, 'requester_id' => $other->id, 'reporter_id' => $other->id,
        'title' => 'Unique printer problem',
    ]);

    Comment::create([
        'ticket_id' => $mine->id, 'user_id' => $requester->id, 'body' => 'Toner cartridge is empty',
    ]);

    $this->actingAs($requester);
    $byTitle = TicketSearch::run('grinding');
    expect($byTitle->total())->toBe(1)
        ->and($byTitle->first()->id)->toBe($mine->id);

    $byComment = TicketSearch::run('cartridge');
    expect($byComment->total())->toBe(1)
        ->and($byComment->first()->id)->toBe($mine->id);

    expect(TicketSearch::run('Unique')->total())->toBe(1);
});

it('returns an exact key match first', function (): void {
    $admin = User::factory()->admin()->create();
    $project = Project::factory()->create(['key' => 'IT']);
    $first = Ticket::factory()->create(['project_id' => $project->id, 'title' => 'IT-1 related words']);
    $exact = Ticket::factory()->create(['project_id' => $project->id, 'title' => 'Another IT-1 related words']);

    $this->actingAs($admin);
    $results = TicketSearch::run('IT-'.$exact->number);

    expect($results->first()->id)->toBe($exact->id);
});

it('hides other projects tickets from non-member agents in search', function (): void {
    $agent = User::factory()->agent()->create();
    $project = Project::factory()->create();
    Ticket::factory()->create(['project_id' => $project->id, 'title' => 'Confidential network outage']);

    $this->actingAs($agent);
    expect(TicketSearch::run('outage')->total())->toBe(0);

    $project->members()->attach($agent->id, ['role' => ProjectRole::Member->value]);
    expect(TicketSearch::run('outage')->total())->toBe(1);
});

it('renders debounced search results in the global search component', function (): void {
    $requester = User::factory()->create();
    $project = Project::factory()->create();
    Ticket::factory()->create([
        'project_id' => $project->id, 'requester_id' => $requester->id, 'reporter_id' => $requester->id,
        'title' => 'Searchable keyboard issue',
    ]);

    $this->actingAs($requester)->get(route('search'))->assertOk();
    Livewire::actingAs($requester)->test(GlobalSearch::class)
        ->set('term', 'keyboard')
        ->assertSee('Searchable keyboard issue');
});
