<?php

declare(strict_types=1);

use App\Domain\Rank;
use App\Domain\StatusMapper;
use App\Domain\TicketWorkflow;
use App\Enums\TicketKind;
use App\Enums\TicketStatus;

it('maps internal statuses to requester friendly labels', function (): void {
    expect(StatusMapper::labelKey(TicketStatus::New))->toBe('status.requester.open')
        ->and(StatusMapper::labelKey(TicketStatus::InProgress))->toBe('status.requester.in_progress')
        ->and(StatusMapper::labelKey(TicketStatus::WaitingRequester))->toBe('status.requester.needs_reply')
        ->and(StatusMapper::labelKey(TicketStatus::Closed))->toBe('status.requester.resolved');
});

it('uses the correct initial status per ticket kind', function (): void {
    expect(TicketWorkflow::initialStatus(TicketKind::Request))->toBe(TicketStatus::New)
        ->and(TicketWorkflow::initialStatus(TicketKind::Dev))->toBe(TicketStatus::InProgress);
});

it('enforces allowed transitions only', function (): void {
    expect(TicketWorkflow::canTransition(TicketStatus::New, TicketStatus::InProgress))->toBeTrue()
        ->and(TicketWorkflow::canTransition(TicketStatus::Done, TicketStatus::New))->toBeFalse()
        ->and(TicketWorkflow::canTransition(TicketStatus::InReview, TicketStatus::Done))->toBeTrue()
        ->and(TicketWorkflow::canTransition(TicketStatus::Backlog, TicketStatus::InProgress))->toBeTrue();
});

it('rejects invalid transitions', function (): void {
    TicketWorkflow::assertTransition(TicketStatus::Done, TicketStatus::New);
})->throws(InvalidArgumentException::class);

it('generates ordered ranks and keeps them between neighbours', function (): void {
    $first = Rank::first();
    $second = Rank::between($first, null);
    $middle = Rank::between($first, $second);

    expect(strcmp($first, $middle))->toBeLessThan(0)
        ->and(strcmp($middle, $second))->toBeLessThan(0)
        ->and(strlen($middle))->toBe(10)
        ->and(Rank::between(null, $first))->not->toBe($first);
});
