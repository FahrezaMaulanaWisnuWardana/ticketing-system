<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AddAttachment;
use App\Actions\CreateTicket;
use App\Actions\UpdateTicket;
use App\Enums\TicketKind;
use App\Enums\TicketStatus;
use App\Events\TicketReopened;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditService;
use App\Support\SlaTargets;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class TicketController
{
    public function index(Request $request): View
    {
        $tickets = Ticket::query()
            ->with('project:id,key,name', 'assignee:id,name', 'requester:id,name', 'category:id,name')
            ->when($request->filled('status'), static fn ($query) => $query->where('status', (string) $request->input('status')))
            ->when($request->filled('kind'), static fn ($query) => $query->where('kind', (string) $request->input('kind')))
            ->when($request->filled('assignee_id'), static fn ($query) => $query->where('assignee_id', (int) $request->input('assignee_id')))
            ->when($request->filled('q'), static function ($query) use ($request): void {
                $term = '%'.mb_strtolower((string) $request->input('q')).'%';
                $query->where(static fn ($inner) => $inner
                    ->whereRaw('lower(title) like ?', [$term])
                    ->orWhereRaw('lower(description) like ?', [$term]));
            })
            ->orderBy('rank')
            ->paginate(25)
            ->withQueryString();

        return view('tickets.index', ['tickets' => $tickets]);
    }

    public function create(): View
    {
        Gate::authorize('create', Ticket::class);
        $projects = Project::visible()->with('categories')->get();

        return view('tickets.create', ['projects' => $projects]);
    }

    public function store(StoreTicketRequest $request, CreateTicket $create): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }
        $data = $request->validated();
        $project = Project::findOrFail((int) $data['project_id']);
        $ticket = $create($user, $project, $data);

        foreach ($request->file('attachments', []) as $file) {
            app(AddAttachment::class)($ticket, $user, $file);
        }

        AuditService::record('ticket.created', $ticket);

        return redirect()->route('tickets.confirmation', $ticket);
    }

    public function show(Request $request, Ticket $ticket): View
    {
        Gate::authorize('view', $ticket);
        if ($request->user()?->isRequester() && $ticket->kind === TicketKind::Dev) {
            abort(404);
        }

        return view('tickets.show', ['ticket' => $ticket]);
    }

    public function confirmation(Ticket $ticket): View
    {
        Gate::authorize('view', $ticket);

        return view('tickets.confirmation', [
            'ticket' => $ticket,
            'responseTarget' => SlaTargets::responseLabel($ticket->priority),
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket, UpdateTicket $update): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }
        $update($ticket, $user, $request->validated());

        return back()->with('status', __('tickets.updated'));
    }

    public function reopen(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('reopen', $ticket);
        $ticket->update([
            'status' => TicketStatus::InProgress,
            'resolved_at' => null,
            'closed_at' => null,
        ]);
        AuditService::record('ticket.reopened', $ticket);
        TicketReopened::dispatch($ticket->id, (int) $request->user()?->id);

        return back()->with('status', __('tickets.reopened'));
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('delete', $ticket);
        $ticket->delete();
        AuditService::record('ticket.deleted', $ticket);

        return redirect()->route('tickets.index')->with('status', __('tickets.deleted'));
    }

    public function quickCreate(StoreTicketRequest $request, CreateTicket $create): RedirectResponse
    {
        return $this->store($request, $create);
    }
}
