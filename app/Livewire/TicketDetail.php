<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\AddComment;
use App\Actions\AttachPending;
use App\Actions\UpdateTicket;
use App\Http\Requests\Rules;
use App\Models\Comment;
use App\Models\ExternalLink;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Watcher;
use App\Services\MarkdownService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

final class TicketDetail extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $ticketId;

    public string $title = '';

    public string $description = '';

    public string $status = '';

    public string $priority = '';

    public ?int $assigneeId = null;

    public string $newComment = '';

    /** Ids of files uploaded inside the rich-text editors, comma separated. */
    public string $descriptionAttachmentIds = '';

    public string $commentAttachmentIds = '';

    public bool $isInternal = false;

    /** @var array<int, mixed> */
    public array $files = [];

    public function mount(int $ticket): void
    {
        $this->ticketId = $ticket;
        $this->refreshFields();
    }

    public function saveDetails(UpdateTicket $update, AttachPending $attach): void
    {
        $ticket = $this->ticket();
        Gate::authorize('update', $ticket);
        $this->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:20000'],
            'priority' => ['required', 'string'],
            'assigneeId' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $update($ticket, $this->actingUser(), [
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'assignee_id' => $this->assigneeId,
        ]);
        $attach(AttachPending::parse($this->descriptionAttachmentIds), $ticket);
        $this->descriptionAttachmentIds = '';
        $this->dispatch('notify', message: __('tickets.updated'));
    }

    public function changeStatus(string $status, UpdateTicket $update): void
    {
        $ticket = $this->ticket();
        Gate::authorize('changeStatus', $ticket);
        $update($ticket, $this->actingUser(), ['status' => $status]);
        $this->refreshFields();
        $this->dispatch('notify', message: __('tickets.updated'));
    }

    public function assignToMe(UpdateTicket $update): void
    {
        $ticket = $this->ticket();
        Gate::authorize('assign', $ticket);
        $update($ticket, $this->actingUser(), ['assignee_id' => $this->actingUser()->id]);
        $this->refreshFields();
        $this->dispatch('notify', message: __('tickets.assigned_to_you'));
    }

    public function addComment(AddComment $add, AttachPending $attach): void
    {
        $ticket = $this->ticket();
        Gate::authorize('create', [Comment::class, $ticket]);
        $rules = Rules::comment();
        $this->validate([
            'newComment' => $rules['body'],
            'isInternal' => $rules['is_internal'],
            'files' => $rules['attachments'],
            'files.*' => $rules['attachments.*'],
        ]);
        if ($this->isInternal) {
            Gate::authorize('viewInternalNotes', $ticket);
        }

        $comment = $add($ticket, $this->actingUser(), $this->newComment, $this->isInternal, $this->files);
        $attach(AttachPending::parse($this->commentAttachmentIds), $ticket, $comment);
        $this->reset(['newComment', 'isInternal', 'files', 'commentAttachmentIds']);
        $this->dispatch('notify', message: __('comments.created'));
    }

    public function toggleWatch(): void
    {
        $ticket = $this->ticket();
        Gate::authorize('watch', $ticket);
        $user = $this->actingUser();
        $existing = Watcher::where('ticket_id', $ticket->id)->where('user_id', $user->id)->first();
        if ($existing !== null) {
            $existing->delete();
        } else {
            Watcher::create(['ticket_id' => $ticket->id, 'user_id' => $user->id]);
        }
    }

    public function render(MarkdownService $markdown): View
    {
        $ticket = $this->ticket();
        $canSeeInternal = Gate::allows('viewInternalNotes', $ticket);

        return view('livewire.ticket-detail', [
            'trelloLink' => config('trello.enabled')
                ? ExternalLink::where('ticket_id', $ticket->id)->where('provider', 'trello')->first()
                : null,
            'ticket' => $ticket->load('project:id,key,name', 'assignee:id,name', 'requester:id,name', 'reporter:id,name', 'category:id,name'),
            'comments' => $ticket->comments()
                ->with('user:id,name', 'attachments:id,ticket_id,comment_id,original_name,mime')
                ->when(! $canSeeInternal, static fn ($query) => $query->where('is_internal', false))
                ->latest()
                ->get(),
            'activities' => $ticket->activities()->with('user:id,name')->latest()->limit(50)->get(),
            'attachments' => $ticket->attachments()->with('user:id,name')->latest()->get(),
            'watchers' => $ticket->watchers()->with('user:id,name')->get(),
            'canSeeInternal' => $canSeeInternal,
            'markdown' => $markdown,
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

    private function refreshFields(): void
    {
        $ticket = $this->ticket();
        $this->title = $ticket->title;
        $this->description = (string) $ticket->description;
        $this->status = $ticket->status->value;
        $this->priority = $ticket->priority->value;
        $this->assigneeId = $ticket->assignee_id;
    }
}
