<?php

declare(strict_types=1);

namespace App\Integrations\Trello;

use App\Enums\TrelloConnectionStatus;
use App\Integrations\Contracts\IntegrationAdapter;
use App\Models\ExternalLink;
use App\Models\IntegrationConnection;
use Illuminate\Support\Facades\DB;

final class DisconnectTrello
{
    public function __construct(private readonly TrelloConnectionService $service) {}

    public function __invoke(IntegrationConnection $connection, bool $forgetLinks = false): void
    {
        $credentials = $this->service->credentials($connection);
        if ($connection->webhook_id !== null) {
            try {
                app(IntegrationAdapter::class)->deleteWebhook($connection->webhook_id, $credentials);
            } catch (\Throwable $exception) {
                $this->service->log($connection, 'warning', 'webhook_delete_failed', ['message' => $exception->getMessage()]);
            }
        }

        $project = $connection->project;
        if ($project === null) {
            return;
        }
        DB::transaction(static function () use ($connection, $forgetLinks, $project): void {
            if ($forgetLinks) {
                ExternalLink::whereIn('ticket_id', $project->tickets()->pluck('id'))->delete();
            }
            $connection->forceFill([
                'api_key_encrypted' => '',
                'token_encrypted' => '',
                'webhook_id' => null,
                'status' => TrelloConnectionStatus::Disabled,
            ])->save();
        });
        $this->service->log($connection, 'info', 'disconnected', ['forget_links' => $forgetLinks]);
    }
}
