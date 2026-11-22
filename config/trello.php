<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('INTEGRATION_TRELLO_ENABLED', false),

    'api_url' => 'https://api.trello.com/1',
    'allowed_hosts' => ['api.trello.com'],

    'connect_timeout' => 5,
    'timeout' => 15,
    'retries' => 2,

    'rate_limits' => [
        'per_key' => ['limit' => (int) env('TRELLO_RATE_KEY', 240), 'window' => 10],
        'per_token' => ['limit' => (int) env('TRELLO_RATE_TOKEN', 80), 'window' => 10],
    ],

    'webhook_secret' => env('TRELLO_WEBHOOK_SECRET', ''),
    'callback_url' => env('TRELLO_CALLBACK_URL', env('APP_URL').'/webhooks/trello'),
    'max_cards' => (int) env('TRELLO_MAX_CARDS', 5000),
    'auto_create_labels' => (bool) env('TRELLO_AUTO_CREATE_LABELS', false),
    'log_retention_days' => 30,
    'max_sync_writes_per_minute' => 3,
    'circuit_breaker_failures' => 10,
];
