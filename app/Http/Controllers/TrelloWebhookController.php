<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\HandleTrelloWebhookJob;
use App\Models\IntegrationConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

final class TrelloWebhookController
{
    public function verify(IntegrationConnection $connection): Response
    {
        abort_unless(config('trello.enabled') === true, 404);

        return response('', 200);
    }

    public function handle(Request $request, IntegrationConnection $connection): JsonResponse
    {
        abort_unless(config('trello.enabled') === true, 404);

        $body = $request->getContent();
        $signature = (string) $request->header('X-Trello-Webhook');
        $callback = (string) config('trello.callback_url').'/'.$connection->id;
        $expected = hash_hmac('sha256', $callback.$body, (string) config('trello.webhook_secret'));
        if (! hash_equals($expected, $signature)) {
            abort(403);
        }

        $payload = $request->all();
        $actionId = (string) (($payload['action']['id'] ?? ''));
        if ($actionId !== '') {
            $key = "trello.action.{$actionId}";
            if (! Cache::add($key, true, now()->addHours(24))) {
                return response()->json(['status' => 'duplicate'], 202);
            }
        }

        HandleTrelloWebhookJob::dispatch($connection->id, $payload)
            ->onQueue('integrations')
            ->delay(now()->addSeconds(10));

        return response()->json(['status' => 'queued'], 202);
    }
}
