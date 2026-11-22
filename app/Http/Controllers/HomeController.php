<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

final class HomeController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        if ($user === null) {
            return view('welcome');
        }

        $tickets = Ticket::query()
            ->with('project', 'assignee', 'requester', 'reporter:id,name')
            ->when($user->isRequester(), static fn ($query) => $query->where(static fn ($inner) => $inner
                ->where('requester_id', $user->id)
                ->orWhere('reporter_id', $user->id)))
            ->when($user->isAgent(), static fn ($query) => $query->where('assignee_id', $user->id))
            ->orderByRaw('rank asc')
            ->paginate(20);

        return view('home', ['tickets' => $tickets]);
    }
}
