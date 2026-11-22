<?php

declare(strict_types=1);

namespace App\Domain;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class TicketNumbering
{
    /**
     * Atomically reserve the next ticket number for a project.
     * Must be called inside a database transaction.
     */
    public static function next(int $projectId): int
    {
        $rows = DB::select(
            'UPDATE projects SET ticket_seq = ticket_seq + 1, updated_at = now() WHERE id = ? RETURNING ticket_seq',
            [$projectId],
        );

        if ($rows === []) {
            throw new RuntimeException("Project {$projectId} does not exist.");
        }

        return (int) $rows[0]->ticket_seq;
    }
}
