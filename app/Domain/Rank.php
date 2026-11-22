<?php

declare(strict_types=1);

namespace App\Domain;

use RuntimeException;

/**
 * Fixed-width decimal lexorank. All ranks are exactly 10 digits, so bytewise
 * ordering matches numeric ordering. Strings grow no further; overflow throws
 * so the `tickets:rebalance-ranks` command can be run (added with the board).
 */
final class Rank
{
    private const WIDTH = 10;

    public static function first(): string
    {
        return '1'.str_repeat('0', self::WIDTH - 1);
    }

    public static function between(?string $before, ?string $after): string
    {
        if ($before === null && $after === null) {
            return self::first();
        }
        if ($before === null) {
            return self::decrement((string) $after);
        }
        if ($after === null) {
            return self::increment($before);
        }

        $mid = self::average($before, $after);

        return $mid === $before || $mid === $after ? self::increment($before) : $mid;
    }

    public static function normalize(string $rank): string
    {
        return substr(str_pad($rank, self::WIDTH, '0'), 0, self::WIDTH);
    }

    private static function average(string $left, string $right): string
    {
        $left = self::normalize($left);
        $right = self::normalize($right);
        $sum = [];
        $carry = 0;

        for ($i = self::WIDTH - 1; $i >= 0; $i--) {
            $value = (int) $left[$i] + (int) $right[$i] + $carry;
            $sum[$i] = $value % 10;
            $carry = intdiv($value, 10);
        }

        $result = '';
        $remainder = 0;
        for ($i = 0; $i < self::WIDTH; $i++) {
            $current = $remainder * 10 + $sum[$i];
            $result .= (string) intdiv($current, 2);
            $remainder = $current % 2;
        }

        return $result;
    }

    private static function increment(string $rank): string
    {
        $chars = str_split(self::normalize($rank));
        for ($i = 0; $i < self::WIDTH; $i++) {
            if ($chars[$i] !== '9') {
                $chars[$i] = (string) ((int) $chars[$i] + 1);
                for ($j = $i + 1; $j < self::WIDTH; $j++) {
                    $chars[$j] = '0';
                }

                return implode('', $chars);
            }
        }

        throw new RuntimeException('Rank space exhausted; run tickets:rebalance-ranks.');
    }

    private static function decrement(string $rank): string
    {
        $chars = str_split(self::normalize($rank));
        for ($i = 0; $i < self::WIDTH; $i++) {
            if ($chars[$i] !== '0') {
                $chars[$i] = (string) ((int) $chars[$i] - 1);
                for ($j = $i + 1; $j < self::WIDTH; $j++) {
                    $chars[$j] = '9';
                }

                return implode('', $chars);
            }
        }

        return str_repeat('0', self::WIDTH);
    }
}
