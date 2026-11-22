<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\TrelloConnectionStatus;
use App\Integrations\Trello\TrelloClient;
use App\Integrations\Trello\TrelloConnectionService;
use App\Models\IntegrationConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CheckTrelloWebhooksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(TrelloClient $client, TrelloConnectionService $service): void
    {
        if (config('trello.enabled') !== true) {
            return;
        }
        IntegrationConnection::whereNotNull('webhook_id')
            ->where('status', TrelloConnectionStatus::Active->value)
            ->chunkById(50, static function ($connections) use ($client, $service): void {
                foreach ($connections as $connection) {
                    try {
                        $client->request('GET', '/webhooks/'.$connection->webhook_id, $service->credentials($connection));
                        $service->log($connection, 'info', 'webhook_healthy', []);
                    } catch (\Throwable $exception) {
                        $connection->forceFill(['last_error' => 'Webhook disabled or unreachable'])->save();
                        $service->log($connection, 'warning', 'webhook_unhealthy', ['message' => $exception->getMessage()]);
                    }
                }
            });
    }
}
