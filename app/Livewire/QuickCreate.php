<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CreateTicket;
use App\Enums\TicketKind;
use App\Http\Requests\Rules;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

final class QuickCreate extends Component
{
    public bool $open = false;

    public int $projectId = 0;

    public string $kind = 'request';

    public string $title = '';

    public string $description = '';

    #[On('open-quick-create')]
    public function show(): void
    {
        $this->open = true;
    }

    public function create(CreateTicket $create): RedirectResponse
    {
        Gate::authorize('create', Ticket::class);
        $this->validate([
            'projectId' => Rules::ticketCreate()['project_id'],
            'kind' => Rules::ticketCreate()['kind'],
            'title' => Rules::ticketCreate()['title'],
            'description' => Rules::ticketCreate()['description'],
        ]);

        $project = Project::findOrFail($this->projectId);
        Gate::authorize('create', $project);
        if ($this->kind === TicketKind::Dev->value) {
            Gate::authorize('createDev', Ticket::class);
        }

        $user = auth('web')->user();
        if (! $user instanceof User) {
            abort(401);
        }
        $ticket = $create($user, $project, [
            'project_id' => $project->id,
            'kind' => $this->kind,
            'title' => $this->title,
            'description' => $this->description,
        ]);

        return redirect()->route('tickets.show', $ticket);
    }

    public function render(): View
    {
        return view('livewire.quick-create', [
            'projects' => Project::visible()->orderBy('name')->get(),
        ]);
    }
}
