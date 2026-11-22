<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CommentAdded;
use App\Jobs\MirrorTrelloCommentJob;
use Illuminate\Contracts\Queue\ShouldQueue;

final class MirrorTrelloComment implements ShouldQueue
{
    public function handle(CommentAdded $event): void
    {
        if (config('trello.enabled') !== true) {
            return;
        }
        MirrorTrelloCommentJob::dispatch($event->commentId)
            ->onQueue('integrations')
            ->delay(now()->addSeconds(10));
    }
}
