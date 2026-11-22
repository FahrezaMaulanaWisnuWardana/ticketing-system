<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\TicketKind;
use App\Integrations\Contracts\IntegrationAdapter;
use App\Integrations\Trello\TrelloConnectionService;
use App\Models\ExternalLink;
use App\Models\IntegrationConnection;
use App\Models\StatusMapping;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class PushTrelloBoardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $connectionId) {}

    public function handle(TrelloConnectionService $service): void
    {
        $connection = IntegrationConnection::find($this->connectionId);
        if ($connection === null) {
            return;
        }
        $credentials = $service->credentials($connection);
        Ticket::withoutGlobalScopes()
            ->where('project_id', $connection->project_id)
            ->where('kind', TicketKind::Dev)
            ->chunkById(50, static function ($tickets) use ($connection, $credentials, $service): void {
                foreach ($tickets as $ticket) {
                    if (ExternalLink::where('ticket_id', $ticket->id)->where('provider', 'trello')->exists()) {
                        continue;
                    }
                    $listId = StatusMapping::where('connection_id', $connection->id)
                        ->where('ticket_status', $ticket->status)->value('trello_list_id');
                    $card = app(IntegrationAdapter::class)->createCard([
                        'name' => $ticket->title,
                        'desc' => strip_tags((string) $ticket->description)."\n\n".route('tickets.show', $ticket),
                        'idList' => (string) ($listId ?? ''),
                    ], $credentials);
                    ExternalLink::create([
                        'ticket_id' => $ticket->id, 'provider' => 'trello',
                        'external_id' => (string) ($card['id'] ?? ''), 'external_url' => $card['url'] ?? null,
                    ]);
                }
                $service->markSynced($connection);
            });
    }
}
