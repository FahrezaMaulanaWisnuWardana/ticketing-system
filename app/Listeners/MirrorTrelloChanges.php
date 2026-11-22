<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use App\Events\TicketUpdated;
use App\Jobs\MirrorTicketToTrelloJob;
use Illuminate\Contracts\Queue\ShouldQueue;

final class MirrorTrelloChanges implements ShouldQueue
{
    public function handle(TicketCreated|TicketStatusChanged|TicketAssigned|TicketUpdated $event): void
    {
        if (config('trello.enabled') !== true) {
            return;
        }
        MirrorTicketToTrelloJob::dispatch($event->ticketId)
            ->onQueue('integrations')
            ->delay(now()->addSeconds(10));
    }
}
