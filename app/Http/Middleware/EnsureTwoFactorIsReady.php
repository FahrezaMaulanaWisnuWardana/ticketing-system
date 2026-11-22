<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTwoFactorIsReady
{
    private const ALLOWED = [
        'logout',
        '2fa.setup',
        '2fa.enable',
        '2fa.challenge',
        '2fa.verify',
        'locale.switch',
    ];

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName() ?? '';
        if (in_array($routeName, self::ALLOWED, true)) {
            return $next($request);
        }

        $pendingId = $request->session()->get('auth.2fa_user_id');
        if ($pendingId !== null) {
            return redirect()->route('2fa.challenge');
        }

        $user = Auth::guard('web')->user();
        if ($user instanceof User
            && $user->isAdmin()
            && (bool) config('ticketing.require_admin_2fa')
            && ! $user->hasTwoFactor()
        ) {
            return redirect()->route('2fa.setup');
        }

        return $next($request);
    }
}
