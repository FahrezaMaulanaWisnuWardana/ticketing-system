<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketStatus: string
{
    private const SORT_CASE = "CASE WHEN status IN ('new') THEN 5 WHEN status IN ('in_progress', 'backlog', 'todo') THEN 4 WHEN status IN ('in_review') THEN 3 WHEN status IN ('waiting_requester') THEN 2 WHEN status IN ('done', 'closed') THEN 1 ELSE 0 END";

    public const SORT_DESC = self::SORT_CASE.' DESC';

    public const SORT_ASC = self::SORT_CASE.' ASC';

    case New = 'new';
    case Backlog = 'backlog';
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case WaitingRequester = 'waiting_requester';
    case Done = 'done';
    case Closed = 'closed';

    /**
     * The five statuses shown in the UI. Other cases are kept for backwards
     * compatibility with the board/workflow and are folded into a group below.
     *
     * @return array<int, self>
     */
    public static function canonicalCases(): array
    {
        return [self::New, self::InProgress, self::InReview, self::WaitingRequester, self::Done];
    }

    /**
     * Legacy database value => canonical database value.
     * Kept in sync with the status merge migration.
     *
     * @return array<string, string>
     */
    public static function legacyMap(): array
    {
        return [
            'backlog' => self::InProgress->value,
            'todo' => self::InProgress->value,
            'closed' => self::Done->value,
        ];
    }

    /**
     * The canonical status this value is displayed as.
     *
     * @phpstan-return self::New|self::InProgress|self::InReview|self::WaitingRequester|self::Done
     */
    public function group(): self
    {
        return match ($this) {
            self::Backlog, self::Todo => self::InProgress,
            self::Closed => self::Done,
            default => $this,
        };
    }

    /**
     * All database values that belong to this status (including legacy ones).
     * Used so filtering/sorting also works for rows written before the migration
     * or by features that still produce a legacy value.
     *
     * @return array<int, string>
     */
    public function groupValues(): array
    {
        $values = [$this->value];

        foreach (self::legacyMap() as $legacy => $canonical) {
            if ($canonical === $this->value) {
                $values[] = $legacy;
            }
        }

        return $values;
    }

    public function labelKey(): string
    {
        return 'status.'.$this->group()->value;
    }

    /** Badge colour token. */
    public function tone(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Backlog, self::Todo, self::InProgress => 'amber',
            self::InReview => 'purple',
            self::WaitingRequester => 'orange',
            self::Done, self::Closed => 'emerald',
        };
    }

    /**
     * Small icon path so status never relies on colour alone.
     * Rendered inside a 24x24 outline SVG by the status badge component.
     */
    public function iconPath(): string
    {
        return match ($this) {
            self::New => '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v8m-4-4h8"/>',
            self::Backlog, self::Todo, self::InProgress => '<path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 13.5 7.5-13.5 7.5v-15Z"/>',
            self::InReview => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75 9.75 6.75 9.75 6.75-3.75 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="2.25"/>',
            self::WaitingRequester => '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7.5V12l3 1.5"/>',
            self::Done, self::Closed => '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 12.25 2.5 2.5 5-5"/>',
        };
    }

    /** Higher weight = closer to the top of a descending status sort. */
    public function sortWeight(): int
    {
        return match ($this) {
            self::New => 5,
            self::Backlog, self::Todo, self::InProgress => 4,
            self::InReview => 3,
            self::WaitingRequester => 2,
            self::Done, self::Closed => 1,
        };
    }

    /** Chart colour matching tone(), centralised for reports and overviews. */
    public function colorHex(): string
    {
        return match ($this) {
            self::New => '#3b82f6',
            self::Backlog, self::Todo, self::InProgress => '#f59e0b',
            self::InReview => '#a855f7',
            self::WaitingRequester => '#f97316',
            self::Done, self::Closed => '#10b981',
        };
    }

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
