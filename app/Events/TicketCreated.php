<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class TicketCreated
{
    use Dispatchable;

    public function __construct(
        public readonly int $ticketId,
        public readonly int $actorId,
    ) {}
}
