<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\ChangeRank;
use App\Enums\TicketStatus;
use App\Models\Sprint;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class Backlog extends Component
{
    public int $projectId;

    public function mount(int $project): void
    {
        $this->projectId = $project;
    }

    /** @param array<int, int> $orderedIds */
    public function updateRanks(array $orderedIds, ChangeRank $change): void
    {
        $change($this->actingUser(), array_values(array_map('intval', $orderedIds)));
        $this->dispatch('notify', message: __('dev.rank_updated'));
    }

    public function moveToSprint(int $ticketId): void
    {
        $ticket = Ticket::withoutGlobalScopes()->findOrFail($ticketId);
        Gate::authorize('update', $ticket);
        $sprint = Sprint::where('project_id', $this->projectId)->where('state', 'active')->firstOrFail();
        $ticket->update(['sprint_id' => $sprint->id]);
        $this->dispatch('notify', message: __('dev.moved_to_sprint'));
    }

    public function render(): View
    {
        $tickets = Ticket::query()
            ->with('project:id,key,name', 'assignee:id,name')
            ->where('project_id', $this->projectId)
            ->whereNull('sprint_id')
            ->whereIn('status', TicketStatus::InProgress->groupValues())
            ->orderBy('rank')
            ->get();

        return view('livewire.backlog', [
            'tickets' => $tickets,
            'points' => (int) $tickets->sum('estimate'),
            'sprint' => Sprint::where('project_id', $this->projectId)->where('state', 'active')->first(),
        ]);
    }

    private function actingUser(): User
    {
        $user = auth('web')->user();
        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
