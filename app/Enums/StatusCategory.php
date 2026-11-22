<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The four Jira-style status categories used by every ticket list.
 *
 * A category owns exactly one label, one icon and one colour. Individual
 * database statuses are mapped onto a category by TicketStatus::category(),
 * so the same text can never be rendered with two different icons.
 */
enum StatusCategory: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Waiting = 'waiting';
    case Done = 'done';

    public function labelKey(): string
    {
        return 'portal.status.'.$this->value;
    }

    /** Badge colour token (see x-badge). */
    public function tone(): string
    {
        return match ($this) {
            self::Todo => 'slate',
            self::InProgress => 'blue',
            self::Waiting => 'amber',
            self::Done => 'emerald',
        };
    }

    /**
     * Single outline icon for this category, rendered inside a 24x24 SVG.
     * Colour is never the only signal: the icon and the text travel together.
     */
    public function iconPath(): string
    {
        return match ($this) {
            self::Todo => '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v4m0 4h.01"/>',
            self::InProgress => '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="m10 8.5 5 3.5-5 3.5v-7Z"/>',
            self::Waiting => '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7.5V12l3 1.75"/>',
            self::Done => '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 12.25 2.5 2.5 5-5"/>',
        };
    }

    public function isFinal(): bool
    {
        return $this === self::Done;
    }
}
