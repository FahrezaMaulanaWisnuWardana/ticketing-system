<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class AssignRequestId
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->headers->get('X-Request-Id');
        $id = is_string($id) && preg_match('/^[a-zA-Z0-9\-]{1,64}$/', $id) === 1 ? $id : (string) Str::uuid();

        $request->headers->set('X-Request-Id', $id);
        App::instance('request.id', $id);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $id);

        return $response;
    }
}
