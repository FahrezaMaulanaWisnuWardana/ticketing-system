<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Support\TicketSearch;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

final class GlobalSearch extends Component
{
    use WithPagination;

    public string $term = '';

    public function updatedTerm(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $results = $this->term === ''
            ? null
            : TicketSearch::run($this->term);

        return view('livewire.global-search', [
            'results' => $results,
        ]);
    }
}
