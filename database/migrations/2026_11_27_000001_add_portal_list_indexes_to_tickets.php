<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Indexes used by the portal list filters and sorts. Status, priority and
 * updated_at indexes already exist from the status-merge migration, so this
 * only adds what the new queries actually need. IF NOT EXISTS keeps the
 * migration safe on databases that already carry equivalent indexes.
 */
return new class extends Migration
{
    /**
     * CONCURRENTLY keeps writes flowing while the indexes are built. It cannot
     * run inside a transaction, so the whole migration opts out of one.
     */
    public $withinTransaction = false;

    /** @return array<int, array{name: string, sql: string}> */
    private function indexes(): array
    {
        return [
            ['name' => 'tickets_reporter_id_idx', 'sql' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS tickets_reporter_id_idx ON tickets (reporter_id)'],
            ['name' => 'tickets_assignee_created_idx', 'sql' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS tickets_assignee_created_idx ON tickets (assignee_id, created_at)'],
            ['name' => 'tickets_created_at_idx', 'sql' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS tickets_created_at_idx ON tickets (created_at)'],
            ['name' => 'tickets_type_idx', 'sql' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS tickets_type_idx ON tickets (type)'],
            ['name' => 'tickets_status_created_idx', 'sql' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS tickets_status_created_idx ON tickets (status, created_at)'],
        ];
    }

    public function up(): void
    {
        foreach ($this->indexes() as $index) {
            DB::statement($index['sql']);
        }
    }

    public function down(): void
    {
        foreach ($this->indexes() as $index) {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS '.$index['name']);
        }
    }
};
