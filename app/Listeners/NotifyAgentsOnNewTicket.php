<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Support\NotificationEvents;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;

final class NotifyAgentsOnNewTicket implements ShouldQueue
{
    public function handle(TicketCreated $event): void
    {
        $ticket = Ticket::withoutGlobalScopes()->with('project')->find($event->ticketId);
        if ($ticket === null) {
            return;
        }
        $projectId = $ticket->project_id;
        $ownerId = $ticket->project->owner_id;
        $actor = User::find($event->actorId);
        $message = __('notifications.ticket_created', ['key' => $ticket->key]);

        $agents = (new User)->newQuery()->where('is_active', true)
            ->where(static function (Builder $query) use ($projectId, $ownerId): void {
                $query->where('id', $ownerId)
                    ->orWhere(static function (Builder $inner) use ($projectId): void {
                        $inner->whereIn('role', ['agent', 'admin'])
                            ->whereHas('projects', static fn ($projects) => $projects->where('projects.id', $projectId));
                    });
            })
            ->get();

        foreach ($agents as $agent) {
            NotificationDispatcher::send($agent, NotificationEvents::TICKET_CREATED, $ticket, $message, $actor);
        }
    }
}
