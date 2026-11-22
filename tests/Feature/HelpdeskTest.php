<?php

declare(strict_types=1);

use App\Actions\AddComment;
use App\Enums\ProjectRole;
use App\Enums\TicketStatus;
use App\Jobs\SendDebouncedTicketNotification;
use App\Livewire\HelpdeskQueue;
use App\Livewire\RequesterConversation;
use App\Livewire\TicketDetail;
use App\Models\CannedReply;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Watcher;
use App\Notifications\TicketEventNotification;
use App\Services\NotificationDispatcher;
use App\Services\NotificationPreference;
use App\Support\NotificationEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
});

function helpdeskSetup(): array
{
    $requester = User::factory()->create(['name' => 'Requester One', 'email' => 'requester@example.com']);
    $agent = User::factory()->agent()->create(['name' => 'Agent One', 'email' => 'agent@example.com']);
    $project = Project::factory()->create(['key' => 'IT']);
    $project->members()->attach($agent->id, ['role' => ProjectRole::Member->value]);

    return [$requester, $agent, $project];
}

it('confirms creation with a key and expected response time', function (): void {
    [$requester, $agent, $project] = helpdeskSetup();

    $this->actingAs($requester)->post(route('tickets.store'), [
        'project_id' => $project->id,
        'kind' => 'request',
        'title' => 'Laptop will not boot',
        'priority' => 'urgent',
    ])->assertRedirect();

    $ticket = Ticket::where('title', 'Laptop will not boot')->firstOrFail();
    $this->actingAs($requester)->get(route('tickets.confirmation', $ticket))
        ->assertOk()
        ->assertSee('IT-'.$ticket->number)
        ->assertSee((string) __('sla.response.urgent'));
});

it('sends a public reply to the requester and never notifies the actor', function (): void {
    [$requester, $agent, $project] = helpdeskSetup();
    $ticket = Ticket::factory()->create([
        'project_id' => $project->id,
        'requester_id' => $requester->id,
        'reporter_id' => $requester->id,
        'assignee_id' => $agent->id,
    ]);

    app(AddComment::class)($ticket, $agent, 'We are looking into it.');

    Notification::assertSentTo($requester, TicketEventNotification::class);
    Notification::assertNotSentTo($agent, TicketEventNotification::class);
});

it('never sends internal notes to requesters even when they are mentioned', function (): void {
    [$requester, $agent, $project] = helpdeskSetup();
    $ticket = Ticket::factory()->create([
        'project_id' => $project->id,
        'requester_id' => $requester->id,
        'reporter_id' => $requester->id,
        'assignee_id' => $agent->id,
    ]);

    app(AddComment::class)($ticket, $agent, 'Internal: @requester@example.com root cause is the PSU', true);

    Notification::assertNotSentTo($requester, TicketEventNotification::class);
    Livewire::actingAs($requester)->test(RequesterConversation::class, ['ticket' => $ticket->id])
        ->assertDontSee('root cause is the PSU');
});

it('notifies watchers and mentioned agents on a public comment', function (): void {
    [$requester, $agent, $project] = helpdeskSetup();
    $watcher = User::factory()->create(['email' => 'watcher@example.com']);
    $ticket = Ticket::factory()->create(['project_id' => $project->id, 'requester_id' => $requester->id, 'reporter_id' => $requester->id]);
    Watcher::create(['ticket_id' => $ticket->id, 'user_id' => $watcher->id]);

    app(AddComment::class)($ticket, $agent, 'Fixed. cc @watcher@example.com');

    Notification::assertSentTo($watcher, TicketEventNotification::class);
    Notification::assertSentTo($requester, TicketEventNotification::class);
});

it('respects per-user notification preferences', function (): void {
    [$requester, $agent, $project] = helpdeskSetup();
    NotificationPreference::set($requester, NotificationEvents::COMMENT_PUBLIC, [
        'mail' => false, 'database' => false, 'broadcast' => false,
    ]);
    $ticket = Ticket::factory()->create([
        'project_id' => $project->id, 'requester_id' => $requester->id, 'reporter_id' => $requester->id,
    ]);

    app(AddComment::class)($ticket, $agent, 'A public reply');

    Notification::assertNotSentTo($requester, TicketEventNotification::class);
});

it('aggregates rapid updates into one debounced payload', function (): void {
    [$requester, $agent, $project] = helpdeskSetup();
    $ticket = Ticket::factory()->create([
        'project_id' => $project->id, 'requester_id' => $requester->id, 'reporter_id' => $requester->id,
    ]);

    Queue::fake();
    NotificationDispatcher::send($requester, NotificationEvents::COMMENT_PUBLIC, $ticket, 'First update', $agent);
    NotificationDispatcher::send($requester, NotificationEvents::COMMENT_PUBLIC, $ticket, 'Second update', $agent);

    $pending = Cache::get("notif:{$requester->id}:{$ticket->id}:".NotificationEvents::COMMENT_PUBLIC);
    expect($pending['messages'])->toHaveCount(2);
    Queue::assertPushed(SendDebouncedTicketNotification::class);
});

it('notifies the assignee and the requester on status changes with a friendly label', function (): void {
    [$requester, $agent, $project] = helpdeskSetup();
    $ticket = Ticket::factory()->create([
        'project_id' => $project->id, 'requester_id' => $requester->id, 'reporter_id' => $requester->id,
        'assignee_id' => $agent->id, 'status' => TicketStatus::InProgress,
    ]);

    $this->actingAs($agent)->put(route('tickets.update', $ticket), ['status' => 'waiting_requester']);

    Notification::assertSentTo($requester, TicketEventNotification::class, function (TicketEventNotification $notification): bool {
        return str_contains($notification->messages[0], (string) __('status.requester.needs_reply'));
    });
});

it('resumes work when the requester replies to a waiting ticket', function (): void {
    [$requester, $agent, $project] = helpdeskSetup();
    $ticket = Ticket::factory()->create([
        'project_id' => $project->id, 'requester_id' => $requester->id, 'reporter_id' => $requester->id,
        'status' => TicketStatus::WaitingRequester, 'sla_paused_at' => now()->subHour(),
    ]);

    Livewire::actingAs($requester)->test(RequesterConversation::class, ['ticket' => $ticket->id])
        ->set('replyBody', 'Here is the information you asked for.')
        ->call('reply')
        ->assertHasNoErrors();

    expect($ticket->refresh()->status)->toBe(TicketStatus::InProgress)
        ->and($ticket->sla_paused_at)->toBeNull();
});

it('restricts canned replies to leads and makes them usable from the queue', function (): void {
    [$requester, $agent, $project] = helpdeskSetup();

    $this->actingAs($agent)->post(route('canned.store', $project), ['title' => 'Need info', 'body' => 'Please send your asset tag.'])
        ->assertForbidden();

    $lead = User::factory()->agent()->create();
    $project->members()->syncWithoutDetaching([$lead->id => ['role' => ProjectRole::Lead->value]]);
    $this->actingAs($lead)->post(route('canned.store', $project), ['title' => 'Need info', 'body' => 'Please send your asset tag.'])
        ->assertRedirect();
    $reply = CannedReply::where('title', 'Need info')->firstOrFail();

    $ticket = Ticket::factory()->create(['project_id' => $project->id, 'kind' => 'request']);
    Livewire::actingAs($agent)->test(HelpdeskQueue::class)
        ->call('selectCanned', $reply->id)
        ->assertSet('replyBody', 'Please send your asset tag.')
        ->set('activeTicketId', $ticket->id)
        ->call('send')
        ->assertHasNoErrors();

    expect($ticket->comments()->where('body', 'Please send your asset tag.')->exists())->toBeTrue();
});

it('notifies project agents about a new request and the new assignee about assignment', function (): void {
    [$requester, $agent, $project] = helpdeskSetup();
    $otherAgent = User::factory()->agent()->create();
    $project->members()->attach($otherAgent->id, ['role' => ProjectRole::Member->value]);

    $this->actingAs($requester)->post(route('tickets.store'), [
        'project_id' => $project->id, 'kind' => 'request', 'title' => 'New queue item',
    ])->assertRedirect();
    $ticket = Ticket::where('title', 'New queue item')->firstOrFail();

    Notification::assertSentTo($agent, TicketEventNotification::class);
    Notification::assertSentTo($otherAgent, TicketEventNotification::class);

    $this->actingAs($agent)->put(route('tickets.update', $ticket), ['assignee_id' => $otherAgent->id]);
    Notification::assertSentTo($otherAgent, TicketEventNotification::class, function (TicketEventNotification $notification): bool {
        return str_contains($notification->messages[0], (string) __('notifications.assigned', ['key' => '']));
    });
});

it('renders the requester portal with friendly statuses and gates the agent queue', function (): void {
    [$requester, $agent, $project] = helpdeskSetup();
    $ticket = Ticket::factory()->create([
        'project_id' => $project->id, 'requester_id' => $requester->id, 'reporter_id' => $requester->id,
        'status' => TicketStatus::WaitingRequester,
    ]);

    $this->actingAs($requester)->get(route('requester.requests'))
        ->assertOk()->assertSee((string) __('status.requester.needs_reply'));
    $this->actingAs($requester)->get(route('agent.queue'))->assertForbidden();
    $this->actingAs($agent)->get(route('agent.queue'))->assertOk();
});

it('hides the internal activity log from requesters', function (): void {
    [$requester, $agent, $project] = helpdeskSetup();
    $ticket = Ticket::factory()->create([
        'project_id' => $project->id, 'requester_id' => $requester->id, 'reporter_id' => $requester->id,
    ]);

    Livewire::actingAs($requester)->test(TicketDetail::class, ['ticket' => $ticket->id])
        ->assertDontSee('created');
});

it('keeps comment edit and internal-note rules intact', function (): void {
    [$requester, $agent, $project] = helpdeskSetup();
    $ticket = Ticket::factory()->create(['project_id' => $project->id, 'requester_id' => $requester->id]);
    $comment = Comment::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $agent->id]);

    $this->actingAs($requester)->put(route('comments.update', [$ticket, $comment]), ['body' => 'hacked'])
        ->assertForbidden();
});
