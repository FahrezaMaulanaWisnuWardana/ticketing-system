<?php

declare(strict_types=1);

namespace App\Integrations\Trello;

use Illuminate\Support\Facades\Cache;

final class TrelloRateLimiter
{
    public function check(string $apiKey, string $token): void
    {
        $this->consume('key:'.$apiKey, (int) config('trello.rate_limits.per_key.limit'), (int) config('trello.rate_limits.per_key.window'));
        $this->consume('token:'.$token, (int) config('trello.rate_limits.per_token.limit'), (int) config('trello.rate_limits.per_token.window'));
    }

    public function pause(string $token): void
    {
        Cache::put($this->pauseKey($token), true, now()->addSeconds((int) config('trello.rate_limits.per_token.window')));
    }

    public function isPaused(string $token): bool
    {
        return (bool) Cache::get($this->pauseKey($token), false);
    }

    private function consume(string $identity, int $limit, int $window): void
    {
        $bucket = (int) floor(time() / $window);
        $key = "trello.rate.{$identity}.{$bucket}";
        $count = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $count, now()->addSeconds($window));
        if ($count > $limit) {
            throw new TrelloRateLimitException('Trello rate limit exceeded for '.$identity);
        }
    }

    private function pauseKey(string $token): string
    {
        return 'trello.paused.'.md5($token);
    }
}
