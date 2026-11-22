<?php

declare(strict_types=1);

namespace App\Domain;

use App\Enums\TicketStatus;

final class StatusMapper
{
    /**
     * Plain-language status shown to requesters. Returns a translation key.
     */
    public static function labelKey(TicketStatus $status): string
    {
        return match ($status) {
            TicketStatus::New => 'status.requester.open',
            TicketStatus::Backlog, TicketStatus::Todo, TicketStatus::InProgress, TicketStatus::InReview => 'status.requester.in_progress',
            TicketStatus::WaitingRequester => 'status.requester.needs_reply',
            TicketStatus::Done, TicketStatus::Closed => 'status.requester.resolved',
        };
    }

    /**
     * Requester-facing colour token used by the badge component.
     */
    public static function tone(TicketStatus $status): string
    {
        return match ($status) {
            TicketStatus::New => 'blue',
            TicketStatus::Backlog, TicketStatus::Todo => 'slate',
            TicketStatus::InProgress, TicketStatus::InReview => 'indigo',
            TicketStatus::WaitingRequester => 'amber',
            TicketStatus::Done, TicketStatus::Closed => 'emerald',
        };
    }
}
