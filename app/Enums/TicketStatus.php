<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case Backlog = 'backlog';
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case WaitingRequester = 'waiting_requester';
    case Done = 'done';
    case Closed = 'closed';

    public function isFinal(): bool
    {
        return $this === self::Done || $this === self::Closed;
    }

    public function isOpen(): bool
    {
        return ! $this->isFinal();
    }

    public function isVisibleToRequesterAsProgress(): bool
    {
        return in_array($this, [self::Backlog, self::Todo, self::InProgress, self::InReview], true);
    }
}
