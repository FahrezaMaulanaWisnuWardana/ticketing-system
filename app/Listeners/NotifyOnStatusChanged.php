<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domain\StatusMapper;
use App\Enums\TicketStatus;
use App\Events\TicketStatusChanged;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Watcher;
use App\Services\NotificationDispatcher;
use App\Support\NotificationEvents;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyOnStatusChanged implements ShouldQueue
{
    public function handle(TicketStatusChanged $event): void
    {
        $ticket = Ticket::withoutGlobalScopes()->with('project')->find($event->ticketId);
        if ($ticket === null) {
            return;
        }

        $actor = User::find($event->actorId);
        $status = TicketStatus::from($event->newStatus);
        $label = __(StatusMapper::labelKey($status));
        $message = __('notifications.status_changed', ['key' => $ticket->key, 'status' => $label]);

        $ids = array_filter([$ticket->requester_id, $ticket->reporter_id, $ticket->assignee_id]);
        foreach (Watcher::where('ticket_id', $ticket->id)->pluck('user_id') as $watcherId) {
            $ids[] = (int) $watcherId;
        }

        foreach (array_unique($ids) as $id) {
            $recipient = User::find($id);
            if ($recipient !== null) {
                NotificationDispatcher::send(
                    $recipient,
                    NotificationEvents::STATUS_CHANGED,
                    $ticket,
                    $message,
                    $actor,
                    ['old_status' => $event->oldStatus, 'new_status' => $event->newStatus],
                );
            }
        }
    }
}
