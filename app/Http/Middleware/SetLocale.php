<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale') ?? $request->user()?->locale;
        if (is_string($locale) && in_array($locale, ['id', 'en'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
