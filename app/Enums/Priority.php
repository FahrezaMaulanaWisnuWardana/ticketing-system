<?php

declare(strict_types=1);

namespace App\Enums;

enum Priority: string
{
    private const SORT_CASE = "CASE priority WHEN 'urgent' THEN 4 WHEN 'high' THEN 3 WHEN 'normal' THEN 2 WHEN 'low' THEN 1 ELSE 0 END";

    public const SORT_DESC = self::SORT_CASE.' DESC';

    public const SORT_ASC = self::SORT_CASE.' ASC';

    case Urgent = 'urgent';
    case High = 'high';
    case Normal = 'normal';
    case Low = 'low';

    /** Badge colour token. */
    public function tone(): string
    {
        return match ($this) {
            self::Urgent => 'red',
            self::High => 'orange',
            self::Normal => 'slate',
            self::Low => 'muted',
        };
    }

    /**
     * Small icon path so priority never relies on colour alone.
     * Rendered inside a 24x24 outline SVG by the priority badge component.
     */
    public function iconPath(): string
    {
        return match ($this) {
            self::Urgent => '<path fill-rule="evenodd" d="M12 2.25a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-1.5 0V3a.75.75 0 0 1 .75-.75Zm0 14a1 1 0 1 1 0 2 1 1 0 0 1 0-2Z" clip-rule="evenodd"/>',
            self::High => '<path stroke-linecap="round" stroke-linejoin="round" d="m6.75 19.5 5.25-5.25 5.25 5.25M6.75 12l5.25-5.25 5.25 5.25"/>',
            self::Normal => '<path stroke-linecap="round" d="M5.25 12h13.5"/>',
            self::Low => '<path stroke-linecap="round" stroke-linejoin="round" d="m6.75 9 5.25 5.25L17.25 9"/>',
        };
    }

    public function isUrgent(): bool
    {
        return $this === self::Urgent;
    }

    /** Higher weight = closer to the top of a descending priority sort. */
    public function sortWeight(): int
    {
        return match ($this) {
            self::Urgent => 4,
            self::High => 3,
            self::Normal => 2,
            self::Low => 1,
        };
    }
}
