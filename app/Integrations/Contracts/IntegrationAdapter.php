<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

interface IntegrationAdapter
{
    /**
     * @param  array<string, string>  $credentials
     * @return array<string, mixed>
     */
    public function testConnection(array $credentials): array;

    /**
     * @param  array<string, string>  $credentials
     * @return array<string, mixed>
     */
    public function fetchBoard(string $boardId, array $credentials): array;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $credentials
     * @return array<string, mixed>
     */
    public function createCard(array $data, array $credentials): array;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $credentials
     */
    public function updateCard(string $cardId, array $data, array $credentials): void;

    /** @param array<string, string> $credentials */
    public function addComment(string $cardId, string $body, array $credentials): void;

    /** @param array<string, string> $credentials */
    public function archiveCard(string $cardId, array $credentials): void;

    /**
     * @param  array<string, string>  $credentials
     * @return array<string, mixed>
     */
    public function registerWebhook(string $callbackUrl, array $credentials): array;

    /** @param array<string, string> $credentials */
    public function deleteWebhook(string $webhookId, array $credentials): void;
}
