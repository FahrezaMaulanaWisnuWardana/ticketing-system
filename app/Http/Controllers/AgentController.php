<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class AgentController extends Controller
{
    public function queue(Request $request): View
    {
        if (! $request->user()?->isAgent() && ! $request->user()?->isAdmin()) {
            abort(403);
        }

        return view('agent.queue');
    }
}
