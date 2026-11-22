<?php

declare(strict_types=1);

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Horizon\Http\Middleware\Authenticate;

return [
    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),
    'use' => 'default',
    'prefix' => env('HORIZON_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'),

    'route_middleware' => [
        'web' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            SecurityHeaders::class,
        ],
        'horizon' => [
            StartSession::class,
            ShareErrorsFromSession::class,
            Authenticate::class,
        ],
    ],

    'silence' => (array) env('HORIZON_SILENCE', []),
    'waits' => ['redis:60'],
    'retry_after' => 90,
    'trim_snapshots' => ['job' => 24, 'monitor' => 24],
    'fast_termination' => false,
    'memory_limit' => 256,

    'defaults' => [
        'supervisor-default' => [
            'connection' => 'redis', 'queue' => ['default'], 'balance' => 'auto',
            'autoScalingStrategy' => 'time', 'maxProcesses' => 2, 'maxTime' => 0,
            'maxJobs' => 0, 'memory' => 128, 'tries' => 3, 'timeout' => 120, 'nice' => 0,
        ],
        'supervisor-notifications' => [
            'connection' => 'redis', 'queue' => ['notifications'], 'balance' => 'simple',
            'maxProcesses' => 2, 'memory' => 128, 'tries' => 3, 'timeout' => 60,
        ],
        'supervisor-integrations' => [
            'connection' => 'redis', 'queue' => ['integrations'], 'balance' => 'simple',
            'maxProcesses' => 2, 'memory' => 128, 'tries' => 5, 'timeout' => 120,
        ],
        'supervisor-reports' => [
            'connection' => 'redis', 'queue' => ['reports', 'mail-ingest'], 'balance' => 'simple',
            'maxProcesses' => 1, 'memory' => 256, 'tries' => 3, 'timeout' => 300,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-default' => ['maxProcesses' => 8],
            'supervisor-notifications' => ['maxProcesses' => 4],
            'supervisor-integrations' => ['maxProcesses' => 2],
            'supervisor-reports' => ['maxProcesses' => 2],
        ],
        'local' => [
            'supervisor-default' => ['maxProcesses' => 2],
            'supervisor-notifications' => ['maxProcesses' => 1],
            'supervisor-integrations' => ['maxProcesses' => 1],
            'supervisor-reports' => ['maxProcesses' => 1],
        ],
    ],

    'watch' => [
        'app', 'bootstrap', 'config/**/*.php', 'database/**/*.php',
        'public/**/*.php', 'resources/**/*.php', 'routes/**/*.php',
    ],
];
