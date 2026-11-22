<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TicketKind;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class RequesterController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = (string) $request->input('filter', 'open');

        $tickets = Ticket::query()
            ->with('project:id,key,name', 'assignee:id,name')
            ->where(static function ($query) use ($user): void {
                $query->where('requester_id', $user?->id)->orWhere('reporter_id', $user?->id);
            })
            ->when($filter === 'resolved', static fn ($query) => $query->whereIn('status', [TicketStatus::Done, TicketStatus::Closed]))
            ->when($filter === 'open', static fn ($query) => $query->whereNotIn('status', [TicketStatus::Done, TicketStatus::Closed]))
            ->when($request->filled('q'), static function ($query) use ($request): void {
                $query->whereRaw('tickets.search @@ plainto_tsquery(\'simple\', ?)', [(string) $request->input('q')]);
            })
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('requester.index', ['tickets' => $tickets, 'filter' => $filter]);
    }

    public function show(Ticket $ticket): View
    {
        Gate::authorize('view', $ticket);
        if ($ticket->kind === TicketKind::Dev) {
            abort(404);
        }

        return view('requester.show', ['ticket' => $ticket]);
    }
}
