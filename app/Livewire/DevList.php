<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\TicketStatus;
use App\Models\SavedFilter;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

final class DevList extends Component
{
    use WithPagination;

    public int $projectId;

    public string $status = '';

    public string $assignee = '';

    public string $label = '';

    public string $search = '';

    public int $savedFilterId = 0;

    public function mount(int $project): void
    {
        $this->projectId = $project;
    }

    public function applySavedFilter(int $filterId): void
    {
        $filter = SavedFilter::findOrFail($filterId);
        Gate::authorize('manageFilter', $filter);
        foreach ((array) $filter->filters as $key => $value) {
            if (property_exists($this, (string) $key)) {
                $this->{$key} = (string) $value;
            }
        }
        $this->savedFilterId = $filterId;
    }

    public function render(): View
    {
        $projectId = $this->projectId;
        $status = $this->status;
        $assignee = $this->assignee;
        $label = $this->label;
        $search = $this->search;
        $statusCase = TicketStatus::tryFrom($status);
        $tickets = Ticket::query()
            ->with('project:id,key,name', 'assignee:id,name', 'labels:id,name,color')
            ->where('project_id', $projectId);

        if ($statusCase !== null) {
            $tickets->whereIn('status', $statusCase->groupValues());
        }
        if ($assignee !== '') {
            $tickets->where('assignee_id', (int) $assignee);
        }
        if ($label !== '') {
            $tickets->whereHas('labels', static fn ($inner) => $inner->where('labels.id', (int) $label));
        }
        if ($search !== '') {
            $tickets->whereRaw("search @@ plainto_tsquery('simple', ?)", [$search]);
        }

        $tickets = $tickets->orderBy('rank')->paginate(25);

        $user = auth('web')->user();

        return view('livewire.dev-list', [
            'tickets' => $tickets,
            'statuses' => TicketStatus::canonicalCases(),
            'filters' => $user instanceof User ? SavedFilter::where('user_id', $user->id)->get() : collect(),
            'agents' => User::whereIn('role', ['agent', 'admin'])->orderBy('name')->get(),
        ]);
    }
}
