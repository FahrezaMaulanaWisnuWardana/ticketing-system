<?php

declare(strict_types=1);

use App\Domain\SlaCalculator;
use App\Domain\SlaConfiguration;
use App\Enums\Priority;
use Carbon\CarbonImmutable;

function configFor(array $holidays = [], bool $urgent24x7 = true): SlaConfiguration
{
    return new SlaConfiguration(
        timezone: 'Asia/Jakarta',
        startHour: 8,
        endHour: 17,
        workdays: [1, 2, 3, 4, 5],
        holidays: $holidays,
        urgent24x7: $urgent24x7,
        targets: [
            'urgent' => ['response' => 30, 'resolve' => 240],
            'high' => ['response' => 120, 'resolve' => 480],
            'normal' => ['response' => 480, 'resolve' => 1440],
            'low' => ['response' => 960, 'resolve' => 2400],
        ],
    );
}

it('adds business minutes within a working day', function (): void {
    $calculator = new SlaCalculator(configFor());
    $from = CarbonImmutable::parse('2026-11-23 08:00:00', 'Asia/Jakarta');

    expect($calculator->responseDue(Priority::High, $from)->format('Y-m-d H:i'))->toBe('2026-11-23 10:00');
});

it('crosses weekends and holidays for normal SLA', function (): void {
    $calculator = new SlaCalculator(configFor(['2026-11-25']));
    $friday = CarbonImmutable::parse('2026-11-27 16:00:00', 'Asia/Jakarta');
    // 2h remaining on Friday (1h) + Monday (1h) because Sat/Sun are non-working.
    expect($calculator->addMinutes($friday, 120, false)->format('Y-m-d H:i'))->toBe('2026-11-30 09:00');

    $from = CarbonImmutable::parse('2026-11-24 16:00:00', 'Asia/Jakarta');
    // Wednesday 16:00 + 2h => Thursday 09:00 when Wednesday 25th is a holiday.
    expect($calculator->responseDue(Priority::High, $from)->format('Y-m-d H:i'))->toBe('2026-11-26 09:00');
});

it('runs urgent SLA continuously when configured', function (): void {
    $calculator = new SlaCalculator(configFor());
    $from = CarbonImmutable::parse('2026-11-28 23:00:00', 'Asia/Jakarta');

    expect($calculator->responseDue(Priority::Urgent, $from)->format('Y-m-d H:i'))->toBe('2026-11-28 23:30');
    expect($calculator->responseDue(Priority::High, $from)->format('Y-m-d H:i'))->toBe('2026-11-30 10:00');
});

it('counts only business time while paused', function (): void {
    $calculator = new SlaCalculator(configFor());
    $from = CarbonImmutable::parse('2026-11-23 16:00:00', 'Asia/Jakarta');
    $to = CarbonImmutable::parse('2026-11-24 09:30:00', 'Asia/Jakarta');

    expect($calculator->pausedSeconds($from, $to, false))->toBe(9000)
        ->and($calculator->pausedSeconds($from, $to, true))->toBe((int) $from->diffInSeconds($to));
});
