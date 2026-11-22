<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;
use IntlDateFormatter;

final class DateFormat
{
    /** Friendly relative time for recent activity, absolute localized date for older items. */
    public static function format(?CarbonInterface $date): string
    {
        if ($date === null) {
            return '—';
        }
        if ($date->between(now()->subDays(7), now()->addDay())) {
            return $date->diffForHumans();
        }

        $formatter = new IntlDateFormatter(
            app()->getLocale(),
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::SHORT,
            (string) config('ticketing.display_timezone'),
        );
        $formatted = $formatter->format($date->getTimestamp());

        return $formatted === false ? $date->toDateTimeString() : $formatted;
    }

    /** Full local date and time, used for hover titles/tooltips. */
    public static function dateTime(?CarbonInterface $date): string
    {
        if ($date === null) {
            return '—';
        }

        $formatter = new IntlDateFormatter(
            app()->getLocale(),
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::SHORT,
            (string) config('ticketing.display_timezone'),
        );
        $formatted = $formatter->format($date->getTimestamp());

        return $formatted === false ? $date->toDateTimeString() : $formatted;
    }

    public static function date(?CarbonInterface $date): string
    {
        if ($date === null) {
            return '—';
        }

        $formatter = new IntlDateFormatter(
            app()->getLocale(),
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::NONE,
            (string) config('ticketing.display_timezone'),
        );
        $formatted = $formatter->format($date->getTimestamp());

        return $formatted === false ? $date->toDateString() : $formatted;
    }
}
