<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\TrelloExternalState;
use App\Enums\TrelloMode;
use App\Integrations\Contracts\IntegrationAdapter;
use App\Integrations\Trello\TrelloConnectionService;
use App\Models\ExternalLink;
use App\Models\IntegrationConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

final class ReconcileTrelloJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly ?int $connectionId = null) {}

    public function handle(TrelloConnectionService $service): void
    {
        if (config('trello.enabled') !== true) {
            return;
        }

        $connectionId = $this->connectionId;
        $connections = IntegrationConnection::query()
            ->when($connectionId !== null, static fn ($query) => $query->where('id', $connectionId))
            ->where('mode', TrelloMode::TwoWay)
            ->where('status', 'active')
            ->get();

        foreach ($connections as $connection) {
            $cursor = (int) Cache::get("trello.reconcile.cursor.{$connection->id}", 0);
            try {
                $board = app(IntegrationAdapter::class)->fetchBoard(
                    $connection->trello_board_id,
                    $service->credentials($connection),
                );
                $cards = array_slice(array_values((array) ($board['cards'] ?? [])), $cursor, 50);
                foreach ($cards as $card) {
                    if (! is_array($card)) {
                        continue;
                    }
                    $externalId = (string) ($card['id'] ?? '');
                    $link = ExternalLink::where('provider', 'trello')->where('external_id', $externalId)->first();
                    if ($link === null) {
                        $project = $connection->project;
                        $ticket = $project?->tickets()->where('title', (string) ($card['name'] ?? ''))->first();
                        if ($ticket !== null) {
                            ExternalLink::create([
                                'ticket_id' => $ticket->id, 'provider' => 'trello', 'external_id' => $externalId,
                                'external_url' => $card['url'] ?? null, 'external_state' => TrelloExternalState::Ok,
                            ]);
                        }
                    } elseif (! empty($card['closed'])) {
                        $link->forceFill(['external_state' => TrelloExternalState::Archived])->save();
                    }
                    $cursor++;
                }
                Cache::put("trello.reconcile.cursor.{$connection->id}", $cursor >= count((array) ($board['cards'] ?? [])) ? 0 : $cursor, now()->addDay());
                $service->markSynced($connection);
            } catch (\Throwable $exception) {
                $service->recordFailure($connection, $exception->getMessage());
            }
        }
    }
}
