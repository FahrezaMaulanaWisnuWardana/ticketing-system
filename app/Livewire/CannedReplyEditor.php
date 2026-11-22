<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\CannedReply;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class CannedReplyEditor extends Component
{
    public int $projectId;

    public string $title = '';

    public string $body = '';

    public function mount(int $project): void
    {
        $this->projectId = $project;
    }

    public function save(): void
    {
        $project = Project::findOrFail($this->projectId);
        Gate::authorize('create', [CannedReply::class, $project]);
        $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:20000'],
        ]);

        $project->cannedReplies()->create(['title' => $this->title, 'body' => $this->body]);
        $this->reset(['title', 'body']);
        $this->dispatch('notify', message: __('canned.created'));
    }

    public function delete(int $replyId): void
    {
        $reply = CannedReply::findOrFail($replyId);
        Gate::authorize('delete', $reply);
        $reply->delete();
        $this->dispatch('notify', message: __('canned.deleted'));
    }

    public function render(): View
    {
        return view('livewire.canned-reply-editor', [
            'replies' => CannedReply::where('project_id', $this->projectId)->orderBy('title')->get(),
        ]);
    }
}
