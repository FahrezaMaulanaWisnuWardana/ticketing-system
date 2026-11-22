<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class TicketList extends Component
{
    use WithPagination;

    /** The top-bar search form submits this as `q`. */
    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $status = '';

    #[Url(history: true)]
    public string $project = '';

    #[Url(as: 'assignee', history: true)]
    public string $assignee = '';

    #[Url(history: true)]
    public string $priority = '';

    #[Url(history: true)]
    public string $sort = 'priority';

    #[Url(as: 'dir', history: true)]
    public string $direction = 'desc';

    #[Url(as: 'per_page', history: true)]
    public int $perPage = 25;

    public function mount(): void
    {
        if (! in_array($this->perPage, [25, 50, 100], true)) {
            $this->perPage = 25;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedProject(): void
    {
        $this->resetPage();
    }

    public function updatedAssignee(): void
    {
        $this->resetPage();
    }

    public function updatedPriority(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['priority', 'status', 'updated_at'], true)) {
            return;
        }

        if ($this->sort === $field) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->direction = 'desc';
        }

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'project', 'assignee', 'priority']);
        $this->sort = 'priority';
        $this->direction = 'desc';
        $this->perPage = 25;
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'components.pagination';
    }

    public function render(): View
    {
        $search = trim($this->search);
        $status = TicketStatus::tryFrom($this->status);
        $priority = Priority::tryFrom($this->priority);
        $projectId = (int) $this->project;
        $assigneeId = (int) $this->assignee;

        $query = Ticket::query()
            ->with([
                'project:id,key,name',
                'assignee:id,name',
                'reporter:id,name',
                'requester:id,name',
            ]);

        if ($search !== '') {
            $term = '%'.mb_strtolower($search).'%';
            $query->where(static fn (Builder $inner): Builder => $inner
                ->whereRaw('lower(tickets.title) like ?', [$term])
                ->orWhereRaw('lower(tickets.description) like ?', [$term]));
        }
        if ($status !== null) {
            $query->whereIn('tickets.status', $status->groupValues());
        }
        if ($projectId > 0) {
            $query->where('tickets.project_id', $projectId);
        }
        if ($assigneeId > 0) {
            $query->where('tickets.assignee_id', $assigneeId);
        }
        if ($priority !== null) {
            $query->where('tickets.priority', $priority->value);
        }

        $this->applySort($query);

        /** @var LengthAwarePaginator<int, Ticket> $tickets */
        $tickets = $query->orderBy('tickets.id')->paginate($this->perPage);

        return view('livewire.ticket-list', [
            'tickets' => $tickets,
            'statuses' => TicketStatus::canonicalCases(),
            'priorities' => Priority::cases(),
            'projects' => Project::visible()->orderBy('name')->get(['id', 'key', 'name']),
            'assignees' => User::query()
                ->whereIn('role', [Role::Agent->value, Role::Admin->value])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'sort' => $this->sort,
            'direction' => $this->direction,
            'hasActiveFilters' => $this->hasActiveFilters(),
        ]);
    }

    /** @param Builder<Ticket> $query */
    private function applySort(Builder $query): void
    {
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        if ($this->sort === 'status') {
            $query->orderByRaw($direction === 'asc' ? TicketStatus::SORT_ASC : TicketStatus::SORT_DESC)
                ->orderBy('tickets.updated_at', 'desc');

            return;
        }

        if ($this->sort === 'updated_at') {
            $query->orderBy('tickets.updated_at', $direction);

            return;
        }

        $query->orderByRaw($direction === 'asc' ? Priority::SORT_ASC : Priority::SORT_DESC)
            ->orderBy('tickets.updated_at', 'desc');
    }

    private function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->status !== ''
            || $this->project !== ''
            || $this->assignee !== ''
            || $this->priority !== '';
    }
}
