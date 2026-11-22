<?php

declare(strict_types=1);

namespace App\Domain;

final class ProgressCalculator
{
    public static function percent(int $done, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return (int) round(($done / $total) * 100);
    }
}
