<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SuggestRequest;
use App\Support\RequestTypeCatalog;
use Illuminate\Http\JsonResponse;

final class PortalController extends Controller
{
    public function suggest(SuggestRequest $request): JsonResponse
    {
        $term = (string) ($request->validated('q') ?? '');

        return response()->json(RequestTypeCatalog::search($term));
    }
}
