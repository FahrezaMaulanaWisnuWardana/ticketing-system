<?php

declare(strict_types=1);

namespace App\Domain;

use App\Enums\Priority;
use Carbon\CarbonImmutable;

/**
 * Pure business-hours calculator. All inputs/outputs are CarbonImmutable in
 * the configured display timezone; callers convert from/to UTC storage.
 */
final class SlaCalculator
{
    public function __construct(private readonly SlaConfiguration $config) {}

    public function responseDue(Priority $priority, CarbonImmutable $from): CarbonImmutable
    {
        $minutes = $this->config->targetsFor($priority)['response'];

        return $this->addMinutes($from, $minutes, $this->config->is24x7($priority));
    }

    public function resolveDue(Priority $priority, CarbonImmutable $from): CarbonImmutable
    {
        $minutes = $this->config->targetsFor($priority)['resolve'];

        return $this->addMinutes($from, $minutes, $this->config->is24x7($priority));
    }

    public function addMinutes(CarbonImmutable $from, int $minutes, bool $continuous): CarbonImmutable
    {
        $cursor = $from->setTimezone($this->config->timezone);
        if ($continuous || $minutes <= 0) {
            return $continuous ? $cursor->addMinutes($minutes) : $cursor;
        }

        while ($minutes > 0) {
            if (! $this->isBusinessDay($cursor)) {
                $cursor = $cursor->addDay()->setTime($this->config->startHour, 0);

                continue;
            }

            $start = $cursor->copy()->setTime($this->config->startHour, 0);
            $end = $cursor->copy()->setTime($this->config->endHour, 0);
            if ($cursor->lt($start)) {
                $cursor = $start;
            }
            if ($cursor->gte($end)) {
                $cursor = $cursor->addDay()->setTime($this->config->startHour, 0);

                continue;
            }

            $available = (int) $cursor->diffInMinutes($end);
            if ($available >= $minutes) {
                return $cursor->addMinutes($minutes);
            }
            $minutes -= $available;
            $cursor = $cursor->addDay()->setTime($this->config->startHour, 0);
        }

        return $cursor;
    }

    public function pausedSeconds(CarbonImmutable $from, CarbonImmutable $to, bool $continuous): int
    {
        if ($to->lte($from)) {
            return 0;
        }
        $start = $from->setTimezone($this->config->timezone);
        $end = $to->setTimezone($this->config->timezone);
        if ($continuous) {
            return (int) $start->diffInSeconds($end);
        }

        $total = 0;
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lt($end)) {
            if ($this->isBusinessDay($cursor)) {
                $dayStart = $cursor->copy()->setTime($this->config->startHour, 0)->max($start);
                $dayEnd = $cursor->copy()->setTime($this->config->endHour, 0)->min($end);
                if ($dayEnd->gt($dayStart)) {
                    $total += (int) $dayStart->diffInSeconds($dayEnd);
                }
            }
            $cursor = $cursor->addDay();
        }

        return $total;
    }

    private function isBusinessDay(CarbonImmutable $date): bool
    {
        return in_array($date->dayOfWeekIso, $this->config->workdays, true)
            && ! in_array($date->toDateString(), $this->config->holidays, true);
    }
}
