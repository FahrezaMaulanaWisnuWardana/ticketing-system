<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\AddComment;
use App\Actions\AttachPending;
use App\Actions\UpdateTicket;
use App\Enums\Priority;
use App\Http\Requests\Rules;
use App\Models\CannedReply;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

final class HelpdeskQueue extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public ?int $activeTicketId = null;

    public string $replyBody = '';

    /** Ids of files uploaded inside the rich-text editor, comma separated. */
    public string $replyAttachmentIds = '';

    public int $cannedReplyId = 0;

    /** @var array<int, mixed> */
    public array $files = [];

    public function assign(int $ticketId, UpdateTicket $update): void
    {
        $ticket = Ticket::findOrFail($ticketId);
        Gate::authorize('assign', $ticket);
        $user = $this->actingUser();
        $update($ticket, $user, ['assignee_id' => $user->id]);
        $this->dispatch('notify', message: __('tickets.assigned_to_you'));
    }

    public function selectCanned(int $replyId): void
    {
        $reply = CannedReply::find($replyId);
        if ($reply !== null) {
            $this->replyBody = $reply->body;
        }
    }

    public function send(AddComment $add, AttachPending $attach): void
    {
        $ticket = Ticket::findOrFail($this->activeTicketId ?? 0);
        Gate::authorize('create', [Comment::class, $ticket]);
        $rules = Rules::comment();
        $this->validate([
            'replyBody' => $rules['body'],
            'files' => $rules['attachments'],
        ]);

        $comment = $add($ticket, $this->actingUser(), $this->replyBody, false, $this->files);
        $attach(AttachPending::parse($this->replyAttachmentIds), $ticket, $comment);
        $this->reset(['replyBody', 'files', 'activeTicketId', 'cannedReplyId', 'replyAttachmentIds']);
        $this->dispatch('notify', message: __('comments.created'));
    }

    public function render(): View
    {
        $user = $this->actingUser();
        $projectIds = $user->projects()->pluck('projects.id')->all();
        $search = $this->search;

        $tickets = Ticket::query()
            ->with('project:id,key,name', 'assignee:id,name', 'requester:id,name')
            ->where('kind', 'request')
            ->whereNull('assignee_id')
            ->when($projectIds !== [], static fn ($query) => $query->whereIn('project_id', $projectIds))
            ->when($search !== '', static function ($query) use ($search): void {
                $query->whereRaw("search @@ plainto_tsquery('simple', ?)", [$search]);
            })
            ->orderByRaw(Priority::SORT_DESC)
            ->orderBy('created_at')
            ->paginate(20);

        $canned = CannedReply::query()
            ->whereNull('project_id')
            ->when($projectIds !== [], static fn ($query) => $query->orWhereIn('project_id', $projectIds))
            ->orderBy('title')
            ->get();

        return view('livewire.helpdesk-queue', [
            'tickets' => $tickets,
            'canned' => $canned,
            'priorities' => Priority::cases(),
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
