<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Notifications\TicketEventNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

final class SendDebouncedTicketNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly int $userId,
        public readonly string $cacheKey,
    ) {}

    public function handle(): void
    {
        $data = Cache::get($this->cacheKey);
        if (! is_array($data)) {
            return;
        }
        Cache::forget($this->cacheKey);

        $user = User::find($this->userId);
        if ($user === null || ! isset($data['messages']) || ! is_array($data['messages']) || $data['messages'] === []) {
            return;
        }

        /** @var array<string, mixed> $payload */
        $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];
        $notification = new TicketEventNotification(
            event: (string) ($payload['event'] ?? 'ticket.updated'),
            messages: array_values(array_map('strval', $data['messages'])),
            ticketKey: (string) ($payload['ticket_key'] ?? ''),
            ticketTitle: (string) ($payload['ticket_title'] ?? ''),
            url: (string) ($payload['url'] ?? ''),
            excerpt: isset($payload['excerpt']) ? (string) $payload['excerpt'] : null,
            ticketId: isset($payload['ticket_id']) ? (int) $payload['ticket_id'] : null,
        );

        Notification::send($user, $notification);
    }
}
