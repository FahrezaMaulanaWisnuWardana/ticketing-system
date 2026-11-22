<?php

declare(strict_types=1);

namespace App\Support;

use Generator;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CsvExport
{
    /** @param iterable<array<int, mixed>> $rows */
    public static function response(iterable $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(static function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }
            foreach ($rows as $row) {
                fputcsv($handle, array_map(static fn ($value): string => self::cell($value), $row));
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv', 'X-Content-Type-Options' => 'nosniff']);
    }

    public static function cell(mixed $value): string
    {
        $text = (string) $value;
        if ($text !== '' && preg_match('/^[\s]*[=+\-@\t\r]/', $text) === 1) {
            return "'".$text;
        }

        return $text;
    }

    /** @param iterable<array<int, mixed>> $rows
     *  @return Generator<int, array<int, string>> */
    public static function rows(iterable $rows): Generator
    {
        foreach ($rows as $row) {
            yield array_map(static fn ($value): string => self::cell($value), $row);
        }
    }
}
