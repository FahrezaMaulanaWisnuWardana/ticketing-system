<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors App\Enums\TicketStatus::legacyMap(). Kept local so the migration
     * stays self-contained and does not depend on future application changes.
     *
     * @var array<string, string>
     */
    private const LEGACY_TO_CANONICAL = [
        'backlog' => 'in_progress', // Antrean + Akan dikerjakan + Dalam proses -> Dikerjakan
        'todo' => 'in_progress',
        'closed' => 'done',         // Ditutup -> Selesai
    ];

    public function up(): void
    {
        // Keep the original value so down() can restore it exactly (no data loss).
        Schema::table('tickets', static function (Blueprint $table): void {
            $table->string('status_legacy', 32)->nullable()->after('status');
        });

        DB::table('tickets')
            ->whereIn('status', array_keys(self::LEGACY_TO_CANONICAL))
            ->update(['status_legacy' => DB::raw('status')]);

        foreach (self::LEGACY_TO_CANONICAL as $from => $to) {
            DB::table('tickets')->where('status', $from)->update(['status' => $to]);
        }

        // Columns used by the ticket list filters and sort order.
        Schema::table('tickets', static function (Blueprint $table): void {
            $table->index('status', 'tickets_status_idx');
            $table->index('priority', 'tickets_priority_idx');
            $table->index('updated_at', 'tickets_updated_at_idx');
            $table->index(['status', 'priority'], 'tickets_status_priority_idx');
            $table->index(['priority', 'updated_at'], 'tickets_priority_updated_idx');
        });
    }

    public function down(): void
    {
        DB::table('tickets')
            ->whereNotNull('status_legacy')
            ->update(['status' => DB::raw('status_legacy')]);

        Schema::table('tickets', static function (Blueprint $table): void {
            $table->dropIndex('tickets_status_idx');
            $table->dropIndex('tickets_priority_idx');
            $table->dropIndex('tickets_updated_at_idx');
            $table->dropIndex('tickets_status_priority_idx');
            $table->dropIndex('tickets_priority_updated_idx');
            $table->dropColumn('status_legacy');
        });
    }
};
