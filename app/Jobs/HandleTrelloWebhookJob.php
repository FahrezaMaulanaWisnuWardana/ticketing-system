<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Integrations\Trello\TrelloConnectionService;
use App\Integrations\Trello\TrelloWebhookHandler;
use App\Models\IntegrationConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class HandleTrelloWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly int $connectionId,
        public readonly array $payload,
    ) {}

    public function handle(TrelloWebhookHandler $handler, TrelloConnectionService $service): void
    {
        try {
            $handler->handle($this->connectionId, $this->payload);
        } catch (\Throwable $exception) {
            $connection = IntegrationConnection::find($this->connectionId);
            if ($connection !== null) {
                $service->recordFailure($connection, $exception->getMessage());
            }
            throw $exception;
        }
    }
}
