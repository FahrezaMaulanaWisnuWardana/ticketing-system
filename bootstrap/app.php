<?php

declare(strict_types=1);

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureTwoFactorIsReady;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTheme;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        channels: __DIR__.'/../routes/channels.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            AssignRequestId::class,
            SetLocale::class,
            SetTheme::class,
            SecurityHeaders::class,
            EnsureUserIsActive::class,
            EnsureTwoFactorIsReady::class,
        ]);
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            '2fa' => EnsureTwoFactorIsReady::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);
        $middleware->trustProxies(
            at: explode(',', (string) env('TRUSTED_PROXIES', '*')),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
