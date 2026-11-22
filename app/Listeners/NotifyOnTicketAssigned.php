<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Support\NotificationEvents;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyOnTicketAssigned implements ShouldQueue
{
    public function handle(TicketAssigned $event): void
    {
        $ticket = Ticket::withoutGlobalScopes()->with('project')->find($event->ticketId);
        $assignee = User::find($event->assigneeId);
        if ($ticket === null || $assignee === null) {
            return;
        }

        NotificationDispatcher::send(
            $assignee,
            NotificationEvents::ASSIGNED,
            $ticket,
            __('notifications.assigned', ['key' => $ticket->key]),
            User::find($event->actorId),
        );
    }
}
