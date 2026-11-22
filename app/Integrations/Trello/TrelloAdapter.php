<?php

declare(strict_types=1);

namespace App\Integrations\Trello;

use App\Integrations\Contracts\IntegrationAdapter;

final class TrelloAdapter implements IntegrationAdapter
{
    public function __construct(private readonly TrelloClient $client) {}

    /** @return array<string, mixed> */
    public function testConnection(array $credentials): array
    {
        $user = $this->client->request('GET', '/members/me', $credentials, ['fields' => 'id,fullName,username']);

        return [
            'id' => (string) ($user['id'] ?? ''),
            'name' => (string) ($user['fullName'] ?? ''),
            'username' => (string) ($user['username'] ?? ''),
        ];
    }

    /** @return array<string, mixed> */
    public function fetchBoard(string $boardId, array $credentials): array
    {
        return $this->client->request('GET', "/boards/{$boardId}", $credentials, [
            'fields' => 'id,name',
            'lists' => 'all',
            'cards' => 'all',
            'card_fields' => 'all',
            'members' => 'all',
            'labels' => 'all',
        ]);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function createCard(array $data, array $credentials): array
    {
        return $this->client->request('POST', '/cards', $credentials, [], $data);
    }

    /** @param array<string, mixed> $data */
    public function updateCard(string $cardId, array $data, array $credentials): void
    {
        $this->client->request('PUT', "/cards/{$cardId}", $credentials, [], $data);
    }

    public function addComment(string $cardId, string $body, array $credentials): void
    {
        $this->client->request('POST', "/cards/{$cardId}/actions/comments", $credentials, [], ['text' => $body]);
    }

    public function archiveCard(string $cardId, array $credentials): void
    {
        $this->client->request('PUT', "/cards/{$cardId}", $credentials, [], ['closed' => true]);
    }

    /** @return array<string, mixed> */
    public function registerWebhook(string $callbackUrl, array $credentials): array
    {
        $result = $this->client->request('POST', '/webhooks', $credentials, [], [
            'callbackURL' => $callbackUrl,
            'idModel' => $credentials['board_id'] ?? '',
        ]);

        return ['id' => (string) ($result['id'] ?? '')];
    }

    public function deleteWebhook(string $webhookId, array $credentials): void
    {
        $this->client->request('DELETE', "/webhooks/{$webhookId}", $credentials);
    }
}
