<?php

declare(strict_types=1);

namespace App\Domain;

use App\Enums\Priority;

final class SlaConfiguration
{
    /**
     * @param  array<int, int>  $workdays  ISO weekday numbers (1 = Monday)
     * @param  array<int, string>  $holidays  Y-m-d strings in display timezone
     * @param  array<string, array{response: int, resolve: int}>  $targets  minutes
     */
    public function __construct(
        public readonly string $timezone,
        public readonly int $startHour,
        public readonly int $endHour,
        public readonly array $workdays,
        public readonly array $holidays,
        public readonly bool $urgent24x7,
        public readonly array $targets,
    ) {}

    /** @return array{response: int, resolve: int} */
    public function targetsFor(Priority $priority): array
    {
        return $this->targets[$priority->value] ?? $this->targets[Priority::Normal->value];
    }

    public function is24x7(Priority $priority): bool
    {
        return $this->urgent24x7 && $priority === Priority::Urgent;
    }
}
