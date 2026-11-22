<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;

/**
 * Claims files uploaded by the rich-text editor for the ticket/reply that was
 * just created. Only the uploader's own, still-pending files are claimed.
 */
final class AttachPending
{
    /** @param list<int> $ids */
    public function __invoke(array $ids, Ticket $ticket, ?Comment $comment = null): int
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            abort(401);
        }

        $ids = self::parse($ids);
        if ($ids === []) {
            return 0;
        }

        return Attachment::query()
            ->whereIn('id', $ids)
            ->whereNull('ticket_id')
            ->where('user_id', $user->id)
            ->update([
                'ticket_id' => $ticket->id,
                'comment_id' => $comment?->id,
            ]);
    }

    /**
     * @param  mixed  $value  Comma-separated ids from the editor or an already-split array.
     * @return list<int>
     */
    public static function parse(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        if (! is_array($value)) {
            return [];
        }

        $ids = array_map('intval', $value);

        return array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
    }
}
