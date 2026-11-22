<?php

declare(strict_types=1);

namespace App\Integrations\Trello;

use App\Models\IntegrationConnection;
use App\Models\IntegrationLog;
use Illuminate\Support\Facades\Cache;

final class TrelloConnectionService
{
    /** @return array{key: string, token: string, board_id: string} */
    public function credentials(IntegrationConnection $connection): array
    {
        return [
            'key' => (string) $connection->api_key_encrypted,
            'token' => (string) $connection->token_encrypted,
            'board_id' => $connection->trello_board_id,
        ];
    }

    /** @param array<string, mixed> $payload */
    public function log(?IntegrationConnection $connection, string $level, string $event, array $payload): void
    {
        IntegrationLog::create([
            'connection_id' => $connection?->id,
            'level' => $level,
            'event' => $event,
            'payload' => self::redact($payload),
        ]);
    }

    public function recordFailure(IntegrationConnection $connection, string $message): void
    {
        $key = "trello.failures.{$connection->id}";
        $count = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $count, now()->addMinutes(10));
        $connection->forceFill(['last_error' => mb_substr($message, 0, 500)])->save();
        $this->log($connection, 'error', 'failure', ['message' => $message, 'count' => $count]);

        if ($count >= (int) config('trello.circuit_breaker_failures')) {
            $connection->forceFill(['status' => 'error'])->save();
            $this->log($connection, 'error', 'circuit_breaker_open', ['count' => $count]);
        }
    }

    public function markSynced(IntegrationConnection $connection): void
    {
        Cache::forget("trello.failures.{$connection->id}");
        $connection->forceFill(['status' => 'active', 'last_synced_at' => now(), 'last_error' => null])->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private static function redact(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), ['key', 'token', 'api_key', 'secret', 'authorization'], true)) {
                $payload[$key] = '[redacted]';
            }
        }

        return $payload;
    }
}
