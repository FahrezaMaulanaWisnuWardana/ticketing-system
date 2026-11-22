<?php

declare(strict_types=1);

namespace App\Integrations\Trello;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * The ONLY place that calls the Trello API. All credentials are sent as query
 * parameters (required by Trello) and never logged.
 */
final class TrelloClient
{
    public function __construct(private readonly TrelloRateLimiter $limiter) {}

    /** @param array<string, string> $credentials @return array<string, mixed> */
    /**
     * @param  array<string, string>  $credentials
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    public function request(string $method, string $path, array $credentials, array $query = [], ?array $body = null): array
    {
        if (! $this->limiter->isPaused($credentials['token'] ?? '')) {
            $this->limiter->check((string) ($credentials['key'] ?? ''), (string) ($credentials['token'] ?? ''));
        } else {
            throw new TrelloRateLimitException('Trello queue paused after a 429 response.');
        }

        try {
            $response = Http::baseUrl((string) config('trello.api_url'))
                ->connectTimeout((int) config('trello.connect_timeout'))
                ->timeout((int) config('trello.timeout'))
                ->withQueryParameters(array_merge([
                    'key' => $credentials['key'] ?? '',
                    'token' => $credentials['token'] ?? '',
                ], $query))
                ->retry((int) config('trello.retries'), 250, static function (\Throwable $exception): bool {
                    return $exception instanceof RequestException
                        && in_array($exception->response->status(), [429, 500, 502, 503, 504], true);
                })
                ->send($method, ltrim($path, '/'), $body === null ? [] : ['json' => $body]);

            if ($response->status() === 429) {
                $this->limiter->pause((string) ($credentials['token'] ?? ''));
            }
            $response->throw();

            $data = $response->json();

            return is_array($data) ? $data : [];
        } catch (TrelloRateLimitException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new TrelloException('Trello request failed: '.$exception->getMessage(), 0, $exception);
        }
    }

    /** @param array<string, string> $credentials */
    public function downloadAttachment(string $url, array $credentials): string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        if (! in_array($host, (array) config('trello.allowed_hosts'), true)) {
            throw new TrelloException(__('trello.ssrf_blocked'));
        }

        $response = Http::baseUrl('https://'.$host)
            ->connectTimeout((int) config('trello.connect_timeout'))
            ->timeout((int) config('trello.timeout'))
            ->withOptions(['allow_redirects' => false])
            ->withQueryParameters(['key' => $credentials['key'] ?? '', 'token' => $credentials['token'] ?? ''])
            ->get((string) parse_url($url, PHP_URL_PATH));
        $response->throw();

        return $response->body();
    }
}
