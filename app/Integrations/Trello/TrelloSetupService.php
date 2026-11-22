<?php

declare(strict_types=1);

namespace App\Integrations\Trello;

use App\Integrations\Contracts\IntegrationAdapter;
use App\Jobs\ImportTrelloBoardJob;
use App\Jobs\PushTrelloBoardJob;
use App\Models\ExternalLink;
use App\Models\ImportRun;
use App\Models\IntegrationConnection;
use App\Models\Ticket;
use App\Models\User;

final class TrelloSetupService
{
    public function __construct(
        private readonly TrelloConnectionService $service,
    ) {}

    public function applyExistingChoice(IntegrationConnection $connection, string $choice, User $actor): void
    {
        match ($choice) {
            'import' => $this->import($connection, $actor),
            'push' => PushTrelloBoardJob::dispatch($connection->id)->onQueue('integrations'),
            'link' => $this->link($connection),
            default => null,
        };
    }

    private function import(IntegrationConnection $connection, User $actor): void
    {
        $run = ImportRun::create([
            'connection_id' => $connection->id, 'started_by' => $actor->id, 'status' => 'pending',
        ]);
        ImportTrelloBoardJob::dispatch($run->id)->onQueue('integrations');
    }

    private function link(IntegrationConnection $connection): void
    {
        $board = app(IntegrationAdapter::class)->fetchBoard(
            $connection->trello_board_id,
            $this->service->credentials($connection),
        );
        foreach ((array) ($board['cards'] ?? []) as $card) {
            if (! is_array($card)) {
                continue;
            }
            $ticket = Ticket::withoutGlobalScopes()
                ->where('project_id', $connection->project_id)
                ->whereRaw('lower(title) = ?', [mb_strtolower((string) ($card['name'] ?? ''))])
                ->first();
            if ($ticket === null) {
                continue;
            }
            ExternalLink::updateOrCreate(
                ['provider' => 'trello', 'external_id' => (string) ($card['id'] ?? '')],
                ['ticket_id' => $ticket->id, 'external_state' => 'ok', 'external_url' => $card['url'] ?? null],
            );
        }
    }
}
