<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Priority;

/**
 * Display-only first-response targets. Phase 3 computes real due dates from
 * business hours and holidays via SlaCalculator and admin settings.
 */
final class SlaTargets
{
    public static function responseLabel(Priority $priority): string
    {
        return __('sla.response.'.$priority->value);
    }
}
