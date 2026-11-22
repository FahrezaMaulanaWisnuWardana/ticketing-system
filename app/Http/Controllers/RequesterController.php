<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TicketKind;
use App\Http\Requests\ListMyRequestsRequest;
use App\Models\Ticket;
use App\Support\Queries\MyRequestsQuery;
use App\Support\RequestTypeCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class RequesterController extends Controller
{
    public function index(ListMyRequestsRequest $request): View
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }
        $filters = $request->validated();

        $tickets = (new MyRequestsQuery($user, $filters))->run();

        return view('requester.index', [
            'tickets' => $tickets,
            'filters' => $filters,
            'canSeeAll' => $user->hasOrganizationAccess(),
            'typeOptions' => RequestTypeCatalog::options(),
            'showCreator' => $user->hasOrganizationAccess(),
        ]);
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
