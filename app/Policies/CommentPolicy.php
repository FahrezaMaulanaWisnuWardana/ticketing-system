<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;

final class CommentPolicy
{
    public function view(User $user, Comment $comment): bool
    {
        $ticket = Ticket::withoutGlobalScopes()->findOrFail($comment->ticket_id);
        if (! app(TicketPolicy::class)->view($user, $ticket)) {
            return false;
        }

        return ! $comment->is_internal || app(TicketPolicy::class)->viewInternalNotes($user, $ticket);
    }

    public function create(User $user, Ticket $ticket): bool
    {
        return app(TicketPolicy::class)->comment($user, $ticket);
    }

    public function update(User $user, Comment $comment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($comment->user_id !== $user->id || $comment->created_at?->lt(now()->subMinutes(15))) {
            return false;
        }

        return app(TicketPolicy::class)->view($user, Ticket::withoutGlobalScopes()->findOrFail($comment->ticket_id));
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        $ticket = Ticket::withoutGlobalScopes()->findOrFail($comment->ticket_id);
        $project = Project::find($ticket->project_id);

        return ($comment->user_id === $user->id && $comment->created_at?->gte(now()->subMinutes(15)))
            || ($project !== null && $user->isLeadOf($project));
    }
}
