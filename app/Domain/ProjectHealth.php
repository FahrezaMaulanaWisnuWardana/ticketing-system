<?php

declare(strict_types=1);

namespace App\Domain;

use Carbon\CarbonImmutable;

final class ProjectHealth
{
    /** Health is computed from time elapsed vs. work progress and overdue milestones. */
    public static function calculate(int $progressPercent, ?CarbonImmutable $start, ?CarbonImmutable $due, bool $hasOverdueMilestone): string
    {
        if ($hasOverdueMilestone) {
            return 'late';
        }
        if ($start === null || $due === null || $due->lessThanOrEqualTo($start)) {
            return 'on_track';
        }

        $elapsed = (int) $start->diffInDays(now()->toImmutable());
        $planned = (int) $start->diffInDays($due);
        $timePercent = $planned > 0 ? (int) round(($elapsed / $planned) * 100) : 100;

        return $progressPercent >= $timePercent - 10 ? 'on_track' : 'at_risk';
    }
}
