<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class TicketStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly int $ticketId,
        public readonly int $actorId,
        public readonly string $oldStatus,
        public readonly string $newStatus,
    ) {}
}
