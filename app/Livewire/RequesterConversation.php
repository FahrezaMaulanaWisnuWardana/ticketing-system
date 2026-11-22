<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\AddComment;
use App\Http\Requests\Rules;
use App\Models\Ticket;
use App\Models\User;
use App\Services\MarkdownService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;

final class RequesterConversation extends Component
{
    use WithFileUploads;

    public int $ticketId;

    public string $replyBody = '';

    /** @var array<int, mixed> */
    public array $files = [];

    public function mount(int $ticket): void
    {
        $this->ticketId = $ticket;
    }

    public function reply(AddComment $add): void
    {
        $ticket = $this->ticket();
        Gate::authorize('comment', $ticket);
        $rules = Rules::comment();
        $this->validate([
            'replyBody' => $rules['body'],
            'files' => $rules['attachments'],
        ]);

        $add($ticket, $this->actingUser(), $this->replyBody, false, $this->files);
        $this->reset(['replyBody', 'files']);
        $this->dispatch('notify', message: __('comments.created'));
    }

    public function render(MarkdownService $markdown): View
    {
        $ticket = $this->ticket();

        return view('livewire.requester-conversation', [
            'ticket' => $ticket->load('project:id,key,name', 'assignee:id,name'),
            'comments' => $ticket->comments()
                ->with('user:id,name', 'attachments:id,ticket_id,comment_id,original_name')
                ->where('is_internal', false)
                ->oldest()
                ->get(),
            'attachments' => $ticket->attachments()->latest()->get(),
            'markdown' => $markdown,
            'canReopen' => Gate::allows('reopen', $ticket),
        ]);
    }

    private function ticket(): Ticket
    {
        $ticket = Ticket::withoutGlobalScopes()->findOrFail($this->ticketId);
        Gate::authorize('view', $ticket);

        return $ticket;
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
