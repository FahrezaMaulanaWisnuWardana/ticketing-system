<?php

declare(strict_types=1);

namespace App\Domain;

use App\Enums\TicketKind;
use App\Enums\TicketStatus;
use InvalidArgumentException;

final class TicketWorkflow
{
    /** @var array<string, array<int, TicketStatus>> */
    private const TRANSITIONS = [
        'new' => [TicketStatus::Backlog, TicketStatus::Todo, TicketStatus::InProgress, TicketStatus::WaitingRequester, TicketStatus::Done],
        'backlog' => [TicketStatus::Todo, TicketStatus::InProgress, TicketStatus::Done],
        'todo' => [TicketStatus::InProgress, TicketStatus::Backlog, TicketStatus::Done],
        'in_progress' => [TicketStatus::InReview, TicketStatus::WaitingRequester, TicketStatus::Todo, TicketStatus::Done],
        'in_review' => [TicketStatus::InProgress, TicketStatus::Done, TicketStatus::WaitingRequester],
        'waiting_requester' => [TicketStatus::InProgress, TicketStatus::Done],
        'done' => [TicketStatus::InProgress, TicketStatus::Closed],
        'closed' => [],
    ];

    public static function initialStatus(TicketKind $kind): TicketStatus
    {
        return match ($kind) {
            TicketKind::Request => TicketStatus::New,
            TicketKind::Dev => TicketStatus::Backlog,
        };
    }

    public static function canTransition(TicketStatus $from, TicketStatus $to): bool
    {
        return $from === $to || in_array($to, self::TRANSITIONS[$from->value], true);
    }

    public static function assertTransition(TicketStatus $from, TicketStatus $to): void
    {
        if (! self::canTransition($from, $to)) {
            throw new InvalidArgumentException("Invalid status transition: {$from->value} -> {$to->value}.");
        }
    }

    /** @return array<int, TicketStatus> */
    public static function allowedNext(TicketStatus $from): array
    {
        return self::TRANSITIONS[$from->value];
    }
}
