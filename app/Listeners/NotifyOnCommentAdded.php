<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CommentAdded;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Watcher;
use App\Services\MentionParser;
use App\Services\NotificationDispatcher;
use App\Support\NotificationEvents;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

final class NotifyOnCommentAdded implements ShouldQueue
{
    public function handle(CommentAdded $event): void
    {
        $ticket = Ticket::withoutGlobalScopes()->with('project')->find($event->ticketId);
        $comment = Comment::with('user')->find($event->commentId);
        if ($ticket === null || $comment === null) {
            return;
        }

        $actor = User::find($event->actorId);
        $fromRequester = $comment->user_id === $ticket->requester_id || $comment->user_id === $ticket->reporter_id;
        $excerpt = Str::limit(strip_tags($comment->body), 200);
        $eventName = $fromRequester ? NotificationEvents::REQUESTER_REPLIED : NotificationEvents::COMMENT_PUBLIC;
        $message = $fromRequester
            ? __('notifications.requester_replied', ['key' => $ticket->key])
            : __('notifications.public_reply', ['key' => $ticket->key]);

        if (! $comment->is_internal) {
            foreach ($this->recipients($ticket, $event->actorId) as $recipient) {
                NotificationDispatcher::send($recipient, $eventName, $ticket, $message, $actor, ['excerpt' => $excerpt]);
            }
        }

        foreach ((new MentionParser)->parse($comment->body) as $mentioned) {
            if ($comment->is_internal && ! $mentioned->isAgent() && ! $mentioned->isAdmin()) {
                continue;
            }
            NotificationDispatcher::send(
                $mentioned,
                NotificationEvents::MENTIONED,
                $ticket,
                __('notifications.mentioned', ['key' => $ticket->key]),
                $actor,
                ['excerpt' => $comment->is_internal ? __('notifications.internal_mention_excerpt') : $excerpt],
            );
        }
    }

    /** @return array<int, User> */
    private function recipients(Ticket $ticket, int $actorId): array
    {
        $ids = [$ticket->requester_id, $ticket->assignee_id];
        if ($ticket->reporter_id > 0) {
            $ids[] = $ticket->reporter_id;
        }
        foreach (Watcher::where('ticket_id', $ticket->id)->pluck('user_id') as $watcherId) {
            $ids[] = (int) $watcherId;
        }

        $users = [];
        foreach (array_unique(array_filter($ids)) as $id) {
            if ($id === $actorId) {
                continue;
            }
            $user = User::find($id);
            if ($user instanceof User && ! isset($users[$user->id])) {
                $users[$user->id] = $user;
            }
        }

        return array_values($users);
    }
}
