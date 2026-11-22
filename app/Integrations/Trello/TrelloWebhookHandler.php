<?php

declare(strict_types=1);

namespace App\Integrations\Trello;

use App\Actions\AddComment;
use App\Enums\TrelloExternalState;
use App\Models\Activity;
use App\Models\ExternalLink;
use App\Models\IntegrationConnection;
use App\Models\Ticket;
use App\Models\User;

final class TrelloWebhookHandler
{
    public function __construct(
        private readonly TrelloConnectionService $service,
        private readonly TrelloTwoWaySync $twoWay,
    ) {}

    /** @param array<string, mixed> $payload */
    public function handle(int $connectionId, array $payload): void
    {
        $connection = IntegrationConnection::find($connectionId);
        if ($connection === null || $connection->status->value === 'paused') {
            return;
        }

        $action = $payload['action'] ?? null;
        if (! is_array($action)) {
            return;
        }
        $data = $action['data'] ?? [];
        if (! is_array($data)) {
            return;
        }
        $card = $data['card'] ?? $data['old'] ?? null;
        if (! is_array($card)) {
            return;
        }
        $externalId = (string) ($card['id'] ?? '');
        $link = ExternalLink::where('provider', 'trello')->where('external_id', $externalId)->first();
        if ($link === null) {
            return;
        }

        $memberName = (string) (($action['memberCreator']['fullName'] ?? 'Trello'));
        $type = (string) ($action['type'] ?? '');
        $ticket = $link->ticket;
        if ($ticket === null) {
            return;
        }
        $ticket->loadMissing('project');

        if (in_array($type, ['deleteCard', 'archiveCard'], true) || ! empty($card['closed'])) {
            $link->forceFill(['external_state' => TrelloExternalState::Archived])->save();
            $this->log($ticket, 'trello_card_archived', $memberName, ['card' => $externalId]);
            $this->service->log($connection, 'info', 'card_archived', ['card' => $externalId]);

            return;
        }

        if ($type === 'commentCard') {
            if ($connection->mode->value !== 'two_way') {
                return;
            }
            $text = strip_tags((string) ($data['text'] ?? ''));
            if ($text === '') {
                return;
            }
            app(AddComment::class)(
                $ticket,
                $this->systemUser(),
                '_Imported from Trello — '.$memberName.'_'."\n".$text,
                false,
            );
            $this->log($ticket, 'trello_comment', $memberName, ['card' => $externalId]);
            $link->forceFill(['last_inbound_at' => now(), 'external_state' => 'ok'])->save();

            return;
        }

        $incoming = $this->incomingFields($card, $data);
        $inboundHash = hash('sha256', json_encode($incoming, JSON_THROW_ON_ERROR));
        $hashes = (array) ($link->field_hashes ?? []);
        if (($hashes['last_inbound_hash'] ?? '') === $inboundHash) {
            return;
        }
        $hashes['last_inbound_hash'] = $inboundHash;
        $link->forceFill(['field_hashes' => $hashes])->save();

        if ($connection->mode->value === 'two_way') {
            $this->twoWay->applyInbound($link, $incoming, $memberName);
            $state = $link->external_state === TrelloExternalState::ChangedExternally
                ? TrelloExternalState::ChangedExternally
                : TrelloExternalState::Ok;
            $link->forceFill(['external_state' => $state, 'last_inbound_at' => now()])->save();
        } else {
            $link->forceFill(['external_state' => TrelloExternalState::ChangedExternally, 'last_inbound_at' => now()])->save();
        }
        $this->log($ticket, 'trello_card_changed', $memberName, ['fields' => array_keys($incoming)]);
    }

    /**
     * @param  array<string, mixed>  $card
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function incomingFields(array $card, array $data): array
    {
        $fields = [];
        if (isset($card['name'])) {
            $fields['title'] = (string) $card['name'];
        }
        if (isset($card['desc'])) {
            $fields['description'] = strip_tags((string) $card['desc']);
        }
        if (array_key_exists('due', $card)) {
            $fields['due_date'] = $card['due'] === null ? '' : substr((string) $card['due'], 0, 10);
        }
        if (isset($card['idList'])) {
            $fields['status'] = (string) $card['idList'];
        }
        $old = $data['old'] ?? [];
        if (is_array($old) && isset($old['idList'])) {
            $fields['previous_list'] = (string) $old['idList'];
        }

        return $fields;
    }

    /** @param array<string, mixed> $metadata */
    private function log(Ticket $ticket, string $event, string $memberName, array $metadata): void
    {
        Activity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->systemUser()->id,
            'event' => $event,
            'source' => 'trello',
            'metadata' => $metadata + ['member' => $memberName],
        ]);
    }

    private function systemUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'trello@system.local'],
            ['name' => 'Trello', 'role' => 'agent', 'is_active' => true, 'password' => null, 'email_verified_at' => now()],
        );
    }
}
