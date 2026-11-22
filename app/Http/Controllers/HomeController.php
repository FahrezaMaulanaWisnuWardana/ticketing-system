<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Support\RequestTypeCatalog;
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

        // Small "recent requests" block only — the portal home deliberately
        // has no ticket table. The full list lives at /requests.
        $recent = Ticket::query()
            ->with(['project:id,key,name', 'reporter:id,name', 'assignee:id,name', 'requestType:id,name,kind'])
            ->when($user->isRequester(), static fn ($query) => $query->where('reporter_id', $user->id))
            ->when($user->isAgent(), static fn ($query) => $query->where('assignee_id', $user->id))
            ->latest()
            ->limit(5)
            ->get();

        return view('home', [
            'groupedTypes' => RequestTypeCatalog::grouped(),
            'groupLabels' => RequestTypeCatalog::groups(),
            'recent' => $recent,
        ]);
    }
}
