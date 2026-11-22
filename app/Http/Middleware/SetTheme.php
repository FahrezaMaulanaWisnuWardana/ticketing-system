<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

final class SetTheme
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $theme = $request->session()->get('theme');
        if (! in_array($theme, ['light', 'dark'], true)) {
            $user = $request->user();
            $theme = $user instanceof User && in_array($user->theme, ['light', 'dark'], true)
                ? $user->theme
                : 'light';
            $request->session()->put('theme', $theme);
        }

        // Shared so both layouts can render <html data-theme="..."> before first paint.
        View::share('theme', $theme);

        return $next($request);
    }
}
