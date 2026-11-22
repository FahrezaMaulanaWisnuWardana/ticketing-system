<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendDebouncedTicketNotification;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

final class NotificationDispatcher
{
    /**
     * Collapse multiple changes to the same ticket + event within 60 seconds
     * into a single notification. Never notify the actor.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function send(
        User $recipient,
        string $event,
        Ticket $ticket,
        string $message,
        ?User $actor = null,
        array $payload = [],
    ): void {
        if ($actor !== null && $actor->id === $recipient->id) {
            return;
        }

        $channels = ['mail', 'database', 'broadcast'];
        $enabled = false;
        foreach ($channels as $channel) {
            if (NotificationPreference::enabled($recipient, $event, $channel)) {
                $enabled = true;
                break;
            }
        }
        if (! $enabled) {
            return;
        }

        $ticket->loadMissing('project');
        $payload = array_merge([
            'event' => $event,
            'ticket_id' => $ticket->id,
            'ticket_key' => $ticket->key,
            'ticket_title' => $ticket->title,
            'url' => route('tickets.show', $ticket),
        ], $payload);

        $key = "notif:{$recipient->id}:{$ticket->id}:{$event}";
        /** @var array{messages: array<int, string>, payload: array<string, mixed>} $pending */
        $pending = Cache::get($key, ['messages' => [], 'payload' => []]);
        if (in_array($message, $pending['messages'], true)) {
            return;
        }

        $pending['messages'][] = $message;
        $pending['payload'] = array_merge($pending['payload'], $payload);
        Cache::put($key, $pending, now()->addSeconds(60));

        SendDebouncedTicketNotification::dispatch($recipient->id, $key)
            ->onQueue('notifications')
            ->delay(now()->addSeconds(60));
    }
}
