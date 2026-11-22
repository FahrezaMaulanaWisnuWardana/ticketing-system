<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class TicketUpdated
{
    use Dispatchable;

    /** @param array<int, string> $changedFields */
    public function __construct(
        public readonly int $ticketId,
        public readonly int $actorId,
        public readonly array $changedFields,
    ) {}
}
