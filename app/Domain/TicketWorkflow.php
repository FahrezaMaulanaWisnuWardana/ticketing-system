<?php

declare(strict_types=1);

namespace App\Domain;

use App\Enums\TicketKind;
use App\Enums\TicketStatus;
use InvalidArgumentException;

final class TicketWorkflow
{
    /**
     * Transitions between the five canonical statuses. Legacy values are
     * normalised via group() so old data keeps behaving the same way.
     *
     * @var array<string, array<int, TicketStatus>>
     */
    private const TRANSITIONS = [
        'new' => [TicketStatus::InProgress, TicketStatus::WaitingRequester, TicketStatus::Done],
        'in_progress' => [TicketStatus::InReview, TicketStatus::WaitingRequester, TicketStatus::Done],
        'in_review' => [TicketStatus::InProgress, TicketStatus::Done, TicketStatus::WaitingRequester],
        'waiting_requester' => [TicketStatus::InProgress, TicketStatus::Done],
        'done' => [TicketStatus::InProgress],
    ];

    public static function initialStatus(TicketKind $kind): TicketStatus
    {
        return match ($kind) {
            TicketKind::Request => TicketStatus::New,
            TicketKind::Dev => TicketStatus::InProgress,
        };
    }

    public static function canTransition(TicketStatus $from, TicketStatus $to): bool
    {
        $fromGroup = $from->group();
        $toGroup = $to->group();

        return $fromGroup === $toGroup || in_array($toGroup, self::TRANSITIONS[$fromGroup->value], true);
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
        return self::TRANSITIONS[$from->group()->value];
    }
}
