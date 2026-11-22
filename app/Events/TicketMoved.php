<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TicketMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $ticketId,
        public readonly int $projectId,
        public readonly string $status,
        public readonly string $rank,
    ) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('project.'.$this->projectId)];
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'status' => $this->status,
            'rank' => $this->rank,
        ];
    }
}
