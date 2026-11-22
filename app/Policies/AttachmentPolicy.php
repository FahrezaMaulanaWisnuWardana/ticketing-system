<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\User;

final class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        return app(TicketPolicy::class)->view($user, Ticket::withoutGlobalScopes()->findOrFail($attachment->ticket_id));
    }

    public function create(User $user, Ticket $ticket): bool
    {
        return app(TicketPolicy::class)->attach($user, $ticket);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        if ($user->isAdmin() || $attachment->user_id === $user->id) {
            return true;
        }

        return app(TicketPolicy::class)->deleteAttachment(
            $user,
            Ticket::withoutGlobalScopes()->findOrFail($attachment->ticket_id),
        );
    }
}
