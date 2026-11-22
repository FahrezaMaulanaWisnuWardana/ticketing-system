<?php

declare(strict_types=1);

namespace App\Livewire;

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
        $tickets = Ticket::query()
            ->with('project:id,key,name', 'assignee:id,name', 'labels:id,name,color')
            ->where('project_id', $projectId)
            ->when($status !== '', static fn ($query) => $query->where('status', $status))
            ->when($assignee !== '', static fn ($query) => $query->where('assignee_id', (int) $assignee))
            ->when($label !== '', static fn ($query) => $query->whereHas('labels', static fn ($inner) => $inner->where('labels.id', (int) $label)))
            ->when($search !== '', static function ($query) use ($search): void {
                $query->whereRaw("search @@ plainto_tsquery('simple', ?)", [$search]);
            })
            ->orderBy('rank')
            ->paginate(25);

        $user = auth('web')->user();

        return view('livewire.dev-list', [
            'tickets' => $tickets,
            'filters' => $user instanceof User ? SavedFilter::where('user_id', $user->id)->get() : collect(),
            'agents' => User::whereIn('role', ['agent', 'admin'])->orderBy('name')->get(),
        ]);
    }
}
