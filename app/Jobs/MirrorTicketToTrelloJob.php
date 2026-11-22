<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Integrations\Trello\TrelloConnectionService;
use App\Integrations\Trello\TrelloMirror;
use App\Models\IntegrationConnection;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class MirrorTicketToTrelloJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60, 120];

    public function __construct(public readonly int $ticketId) {}

    public function uniqueId(): string
    {
        return 'trello-mirror-'.$this->ticketId;
    }

    public function handle(TrelloMirror $mirror, TrelloConnectionService $service): void
    {
        $ticket = Ticket::withoutGlobalScopes()->find($this->ticketId);
        if ($ticket === null) {
            return;
        }
        try {
            $mirror->sync($ticket);
        } catch (\Throwable $exception) {
            $connection = IntegrationConnection::where('project_id', $ticket->project_id)->first();
            if ($connection !== null) {
                $service->recordFailure($connection, $exception->getMessage());
            }
            throw $exception;
        }
    }
}
