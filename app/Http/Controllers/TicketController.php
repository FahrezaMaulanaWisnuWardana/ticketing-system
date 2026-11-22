<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AddAttachment;
use App\Actions\AttachPending;
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
use App\Support\RequestTypeCatalog;
use App\Support\SlaTargets;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class TicketController
{
    public function index(): View
    {
        // The single global search (q) and all filters are handled by the
        // TicketList component, which keeps them in the URL query string.
        return view('tickets.index');
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Ticket::class);
        $projects = Project::visible()->with('categories')->get();
        $requestedType = trim((string) $request->query('type', ''));
        $type = RequestTypeCatalog::isValid($requestedType) ? $requestedType : null;

        return view('tickets.create', [
            'projects' => $projects,
            'type' => $type,
            'typeLabel' => $type !== null ? RequestTypeCatalog::label($type) : null,
        ]);
    }

    public function store(StoreTicketRequest $request, CreateTicket $create, AttachPending $attach): RedirectResponse
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
        $attach(AttachPending::parse($request->input('attachment_ids', '')), $ticket);

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

    public function quickCreate(StoreTicketRequest $request, CreateTicket $create, AttachPending $attach): RedirectResponse
    {
        return $this->store($request, $create, $attach);
    }
}
