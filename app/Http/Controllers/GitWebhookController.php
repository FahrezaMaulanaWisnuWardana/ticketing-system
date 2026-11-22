<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessGitWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class GitWebhookController
{
    public function __invoke(Request $request, string $provider): JsonResponse
    {
        if (! in_array($provider, ['github', 'gitlab'], true)) {
            abort(404);
        }

        $body = $request->getContent();
        $secret = (string) config('services.git.webhook_secret');

        if ($provider === 'github') {
            $signature = (string) $request->header('X-Hub-Signature-256');
            $expected = 'sha256='.hash_hmac('sha256', $body, $secret);
            if (! hash_equals($expected, $signature)) {
                abort(403);
            }
        } else {
            $token = (string) $request->header('X-Gitlab-Token');
            if ($secret === '' || ! hash_equals($secret, $token)) {
                abort(403);
            }
        }

        $delivery = (string) ($request->header('X-GitHub-Delivery') ?? $request->header('X-Gitlab-Event-UUID') ?? '');
        if ($delivery !== '') {
            $key = "git.delivery.{$provider}.{$delivery}";
            if (! Cache::add($key, true, now()->addHours(24))) {
                return response()->json(['status' => 'duplicate'], 202);
            }
        }

        ProcessGitWebhookJob::dispatch($provider, $request->all(), $delivery)->onQueue('default');

        return response()->json(['status' => 'queued'], 202);
    }
}
