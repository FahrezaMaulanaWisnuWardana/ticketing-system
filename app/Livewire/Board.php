<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\UpdateTicket;
use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

final class Board extends Component
{
    public int $projectId;

    public ?int $sprintId = null;

    public function mount(int $project): void
    {
        $this->projectId = $project;
        $this->sprintId = Sprint::where('project_id', $project)->where('state', 'active')->value('id');
    }

    #[On('echo:project.{projectId},TicketMoved')]
    public function refreshFromBroadcast(): void
    {
        // Re-render with fresh state after a realtime update.
    }

    public function moveTicket(int $ticketId, string $status, UpdateTicket $update): void
    {
        $ticket = Ticket::withoutGlobalScopes()->findOrFail($ticketId);
        Gate::authorize('changeStatus', $ticket);
        $update($ticket, $this->actingUser(), ['status' => $status]);
        $this->dispatch('notify', message: __('tickets.updated'));
    }

    public function render(): View
    {
        $projectId = $this->projectId;
        $sprintId = $this->sprintId;
        $tickets = Ticket::query()
            ->with('project:id,key,name', 'assignee:id,name', 'labels:id,name,color')
            ->where('project_id', $projectId)
            ->when($sprintId !== null, static fn ($query) => $query->where('sprint_id', $sprintId))
            ->whereIn('status', [TicketStatus::Todo, TicketStatus::InProgress, TicketStatus::InReview, TicketStatus::Done])
            ->orderBy('rank')
            ->get();

        return view('livewire.board', [
            'tickets' => $tickets->groupBy(static fn (Ticket $ticket): string => $ticket->status->value),
            'columns' => [TicketStatus::Todo, TicketStatus::InProgress, TicketStatus::InReview, TicketStatus::Done],
            'sprints' => Project::findOrFail($this->projectId)->sprints()->orderByDesc('start_date')->get(),
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
