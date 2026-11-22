<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateTicket;
use App\Enums\TicketStatus;
use App\Http\Requests\AgentQueueRequest;
use App\Http\Requests\BulkActionRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Queries\AgentQueueQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class AgentController extends Controller
{
    public function __construct(private readonly UpdateTicket $updateTicket) {}

    public function queue(AgentQueueRequest $request): View
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        $filters = $request->validated();
        $query = new AgentQueueQuery($user, $filters);

        return view('agent.queue', [
            'tickets' => $query->run(),
            'counts' => $query->counts(),
            'filters' => $filters,
            'assignees' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'statuses' => TicketStatus::canonicalCases(),
            'agents' => true,
        ]);
    }

    public function bulkAssign(BulkActionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $tickets = $this->tickets($data);
        $actor = $this->actor($request);

        foreach ($tickets as $ticket) {
            Gate::authorize('assign', $ticket);
        }

        $count = 0;
        DB::transaction(function () use ($tickets, $actor, $data, &$count): void {
            foreach ($tickets as $ticket) {
                ($this->updateTicket)($ticket, $actor, ['assignee_id' => (int) $data['assignee_id']]);
                $count++;
            }
        });

        return back()->with('status', __('portal.queues.assigned', ['count' => $count]));
    }

    public function bulkStatus(BulkActionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $tickets = $this->tickets($data);
        $actor = $this->actor($request);
        $status = TicketStatus::from((string) $data['status']);

        foreach ($tickets as $ticket) {
            Gate::authorize('changeStatus', $ticket);
        }

        $count = 0;
        DB::transaction(function () use ($tickets, $actor, $status, &$count): void {
            foreach ($tickets as $ticket) {
                ($this->updateTicket)($ticket, $actor, ['status' => $status]);
                $count++;
            }
        });

        return back()->with('status', __('portal.queues.status_changed', ['count' => $count]));
    }

    /** The FormRequest guarantees an authenticated agent or admin. */
    private function actor(BulkActionRequest $request): User
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, Ticket>
     */
    private function tickets(array $data): Collection
    {
        /** @var array<int, int> $ids */
        $ids = array_values(array_unique(array_map('intval', $data['tickets'])));

        return Ticket::query()->whereKey($ids)->get();
    }
}
