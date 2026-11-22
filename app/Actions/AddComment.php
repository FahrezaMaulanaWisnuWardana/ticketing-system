<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TicketStatus;
use App\Events\CommentAdded;
use App\Events\TicketStatusChanged;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SlaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class AddComment
{
    /** @param array<int, UploadedFile> $files */
    public function __invoke(Ticket $ticket, User $user, string $body, bool $isInternal = false, array $files = []): Comment
    {
        $oldStatus = $ticket->status;
        $resumesWork = ! $isInternal
            && $oldStatus === TicketStatus::WaitingRequester
            && ($user->id === $ticket->requester_id || $user->id === $ticket->reporter_id);

        return DB::transaction(static function () use ($ticket, $user, $body, $isInternal, $files, $oldStatus, $resumesWork): Comment {
            if ($resumesWork) {
                app(SlaService::class)->resume($ticket);
                $ticket->update(['status' => TicketStatus::InProgress]);
            }

            $comment = Comment::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'body' => $body,
                'is_internal' => $isInternal,
            ]);

            foreach ($files as $file) {
                app(AddAttachment::class)($ticket, $user, $file, $comment);
            }

            DB::afterCommit(static function () use ($ticket, $comment, $user, $oldStatus, $resumesWork): void {
                CommentAdded::dispatch($ticket->id, $comment->id, $user->id, $comment->is_internal);
                if ($resumesWork) {
                    TicketStatusChanged::dispatch($ticket->id, $user->id, $oldStatus->value, TicketStatus::InProgress->value);
                }
            });

            return $comment->load('user', 'attachments');
        });
    }
}
