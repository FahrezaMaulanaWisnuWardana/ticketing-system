<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketReopened;
use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Support\NotificationEvents;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyOnTicketReopened implements ShouldQueue
{
    public function handle(TicketReopened $event): void
    {
        $ticket = Ticket::withoutGlobalScopes()->with('project')->find($event->ticketId);
        if ($ticket === null || $ticket->assignee_id === null) {
            return;
        }

        $assignee = User::find($ticket->assignee_id);
        if ($assignee === null) {
            return;
        }

        NotificationDispatcher::send(
            $assignee,
            NotificationEvents::REOPENED,
            $ticket,
            __('notifications.reopened', ['key' => $ticket->key]),
            User::find($event->actorId),
        );
    }
}
