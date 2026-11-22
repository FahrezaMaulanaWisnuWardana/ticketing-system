<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

final class CommentObserver
{
    public function created(Comment $comment): void
    {
        $ticket = Ticket::withoutGlobalScopes()->findOrFail($comment->ticket_id);
        ActivityLogger::log($ticket, 'commented', null, null, mb_substr($comment->body, 0, 200));

        if ($comment->is_internal || $ticket->first_response_at !== null) {
            return;
        }

        $author = User::find($comment->user_id);
        if ($author === null || ! ($author->isAgent() || $author->isAdmin())) {
            return;
        }

        DB::table('tickets')
            ->where('id', $ticket->id)
            ->whereNull('first_response_at')
            ->update(['first_response_at' => now()]);
    }
}
