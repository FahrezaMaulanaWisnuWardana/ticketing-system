<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(18));
        Vite::useCspNonce($nonce);
        view()->share('cspNonce', $nonce);

        $response = $next($request);

        if (! (bool) config('ticketing.security_headers')) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $csp = "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; "
            ."script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline'; "
            ."img-src 'self' data: blob:; font-src 'self' data:; "
            ."connect-src 'self' ws: wss:; form-action 'self'; frame-src 'self'";

        $header = config('ticketing.csp_mode') === 'enforce'
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';
        $response->headers->set($header, $csp);

        return $response;
    }
}
