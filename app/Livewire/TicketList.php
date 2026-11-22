<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domain\StatusMapper;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

final class TicketList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $status = $this->status;
        $search = $this->search;
        $tickets = Ticket::query()
            ->with('project:id,key,name', 'assignee:id,name', 'requester:id,name')
            ->when($status !== '', static fn ($query) => $query->where('status', $status))
            ->when($search !== '', static function ($query) use ($search): void {
                $term = '%'.mb_strtolower($search).'%';
                $query->where(static fn ($inner) => $inner
                    ->whereRaw('lower(title) like ?', [$term])
                    ->orWhereRaw('lower(description) like ?', [$term]));
            })
            ->orderBy('rank')
            ->paginate(25);

        return view('livewire.ticket-list', [
            'tickets' => $tickets,
            'statuses' => TicketStatus::cases(),
            'mapper' => new StatusMapper,
        ]);
    }
}
