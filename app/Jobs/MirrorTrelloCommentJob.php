<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Integrations\Trello\TrelloConnectionService;
use App\Integrations\Trello\TrelloMirror;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class MirrorTrelloCommentJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public readonly int $commentId) {}

    public function uniqueId(): string
    {
        return 'trello-comment-'.$this->commentId;
    }

    public function handle(TrelloMirror $mirror, TrelloConnectionService $service): void
    {
        $comment = Comment::find($this->commentId);
        if ($comment === null) {
            return;
        }
        try {
            $mirror->mirrorComment($comment);
        } catch (\Throwable $exception) {
            $service->log(null, 'error', 'comment_mirror_failed', ['message' => $exception->getMessage()]);
            throw $exception;
        }
    }
}
