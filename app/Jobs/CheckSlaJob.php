<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\TicketStatus;
use App\Models\SlaEvent;
use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Services\SlaService;
use App\Support\NotificationEvents;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CheckSlaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SlaService $sla): void
    {
        Ticket::withoutGlobalScopes()
            ->with('project')
            ->whereNotIn('status', [TicketStatus::Done, TicketStatus::Closed])
            ->chunkById(200, static function ($tickets) use ($sla): void {
                foreach ($tickets as $ticket) {
                    foreach (['response', 'resolve'] as $channel) {
                        self::checkChannel($ticket, $channel, $sla);
                    }
                }
            });
    }

    private static function checkChannel(Ticket $ticket, string $channel, SlaService $sla): void
    {
        if ($channel === 'response' && $ticket->first_response_at !== null) {
            return;
        }
        $due = $channel === 'response' ? $ticket->sla_response_due_at : $ticket->sla_resolve_due_at;
        if ($due === null) {
            return;
        }
        $total = (int) $due->diffInMinutes($ticket->created_at);
        $threshold = (int) ceil($total * 0.2);
        if ($due->lessThanOrEqualTo(now())) {
            $kind = 'breached';
        } elseif ($due->lessThanOrEqualTo(now()->addMinutes($threshold))) {
            $kind = 'at_risk';
        } else {
            return;
        }
        $event = SlaEvent::firstOrCreate([
            'ticket_id' => $ticket->id,
            'kind' => $kind,
            'channel' => $channel,
        ], ['fired_at' => now()]);
        if (! $event->wasRecentlyCreated) {
            return;
        }

        $message = $kind === 'breached'
            ? __("notifications.sla_{$kind}", ['key' => $ticket->key, 'channel' => $channel])
            : __('notifications.sla_at_risk', ['key' => $ticket->key, 'channel' => $channel]);
        $recipients = User::query()
            ->where('is_active', true)
            ->where(static function ($query) use ($ticket): void {
                $query->where('id', $ticket->assignee_id)
                    ->orWhereHas('projects', static fn ($projects) => $projects->where('projects.id', $ticket->project_id));
            })
            ->get();

        foreach ($recipients as $recipient) {
            NotificationDispatcher::send(
                $recipient,
                $kind === 'breached' ? NotificationEvents::SLA_BREACHED : NotificationEvents::SLA_AT_RISK,
                $ticket,
                $message,
                null,
                ['sla_channel' => $channel],
            );
        }
    }
}
