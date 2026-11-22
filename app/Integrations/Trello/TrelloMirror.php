<?php

declare(strict_types=1);

namespace App\Integrations\Trello;

use App\Enums\TicketStatus;
use App\Enums\TrelloConnectionStatus;
use App\Enums\TrelloMode;
use App\Integrations\Contracts\IntegrationAdapter;
use App\Models\Comment;
use App\Models\ExternalLink;
use App\Models\IntegrationConnection;
use App\Models\StatusMapping;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

final class TrelloMirror
{
    public function __construct(
        private readonly TrelloConnectionService $service,
    ) {}

    public function sync(Ticket $ticket, bool $force = false): void
    {
        if (config('trello.enabled') !== true) {
            return;
        }
        $ticket->loadMissing('project', 'assignee');
        $connection = IntegrationConnection::where('project_id', $ticket->project_id)->where('provider', 'trello')->first();
        if ($connection === null || $connection->status->value !== 'active') {
            return;
        }
        if ($connection->mode === TrelloMode::ImportOnly) {
            return;
        }
        if ($ticket->kind->value === 'request' && ! $connection->mirror_helpdesk) {
            return;
        }

        $credentials = $this->service->credentials($connection);
        $fields = $this->fields($ticket, $connection);
        $hash = hash('sha256', json_encode($fields, JSON_THROW_ON_ERROR));

        DB::transaction(function () use ($ticket, $connection, $credentials, $fields, $hash, $force): void {
            $link = ExternalLink::where('ticket_id', $ticket->id)->where('provider', 'trello')->lockForUpdate()->first();
            if ($link !== null && ! $force && $link->last_synced_hash === $hash) {
                return;
            }

            if ($link === null) {
                $card = app(IntegrationAdapter::class)->createCard($fields + ['idList' => $this->listId($connection, $ticket->status)], $credentials);
                $externalId = (string) ($card['id'] ?? '');
                if ($externalId === '') {
                    throw new TrelloException('Trello did not return a card id.');
                }
                $link = ExternalLink::create([
                    'ticket_id' => $ticket->id, 'provider' => 'trello',
                    'external_id' => $externalId, 'external_url' => $card['url'] ?? null,
                    'last_synced_hash' => $hash, 'field_hashes' => self::fieldHashes($fields),
                    'last_synced_at' => now(), 'last_outbound_at' => now(),
                    'external_state' => 'ok',
                ]);
            } else {
                app(IntegrationAdapter::class)->updateCard($link->external_id, $fields + ['idList' => $this->listId($connection, $ticket->status)], $credentials);
                $link->forceFill([
                    'last_synced_hash' => $hash,
                    'field_hashes' => self::fieldHashes($fields),
                    'last_synced_at' => now(),
                    'last_outbound_at' => now(),
                    'external_state' => 'ok',
                ])->save();
            }
        });

        $this->service->markSynced($connection);
    }

    public function mirrorComment(Comment $comment): void
    {
        if (config('trello.enabled') !== true || $comment->is_internal) {
            return;
        }
        $ticket = Ticket::withoutGlobalScopes()->find($comment->ticket_id);
        if ($ticket === null) {
            return;
        }
        $connection = IntegrationConnection::where('project_id', $ticket->project_id)->first();
        if ($connection === null || $connection->status === TrelloConnectionStatus::Paused || $connection->mode === TrelloMode::ImportOnly) {
            return;
        }
        if ($ticket->kind->value === 'request' && ! $connection->mirror_helpdesk) {
            return;
        }
        $link = ExternalLink::where('ticket_id', $ticket->id)->where('provider', 'trello')->first();
        if ($link === null) {
            return;
        }
        $comment->loadMissing('user');
        app(IntegrationAdapter::class)->addComment(
            $link->external_id,
            ((string) $comment->user?->name ?: 'User').': '.strip_tags($comment->body),
            $this->service->credentials($connection),
        );
        $link->forceFill(['last_outbound_at' => now()])->save();
    }

    public function archive(Ticket $ticket): void
    {
        $link = ExternalLink::where('ticket_id', $ticket->id)->where('provider', 'trello')->first();
        if ($link === null) {
            return;
        }
        $connection = IntegrationConnection::where('project_id', $ticket->project_id)->first();
        if ($connection === null) {
            return;
        }
        app(IntegrationAdapter::class)->archiveCard($link->external_id, $this->service->credentials($connection));
        $link->forceFill(['external_state' => 'archived'])->save();
    }

    /** @return array<string, string|null> */
    private function fields(Ticket $ticket, IntegrationConnection $connection): array
    {
        $url = route('tickets.show', $ticket);

        return [
            'name' => $ticket->title,
            'desc' => strip_tags((string) $ticket->description)."\n\n".__('trello.card_footer', ['url' => $url]),
            'due' => $ticket->due_date?->toIso8601String() ?? null,
            'assignee' => (string) $ticket->assignee?->name,
        ];
    }

    private function listId(IntegrationConnection $connection, TicketStatus $status): string
    {
        return (string) (StatusMapping::where('connection_id', $connection->id)
            ->where('ticket_status', $status)->value('trello_list_id') ?? '');
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, string>
     */
    public static function fieldHashes(array $fields): array
    {
        $hashes = [];
        foreach ($fields as $key => $value) {
            $hashes[$key] = hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
        }

        return $hashes;
    }
}
