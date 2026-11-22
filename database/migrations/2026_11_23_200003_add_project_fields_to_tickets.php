<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', static function (Blueprint $table): void {
            $table->foreignId('epic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained()->nullOnDelete();
        });
        Schema::table('tickets', static function (Blueprint $table): void {
            $table->index('epic_id');
            $table->index('milestone_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', static function (Blueprint $table): void {
            $table->dropIndex(['epic_id']);
            $table->dropIndex(['milestone_id']);
            $table->dropForeign(['epic_id']);
            $table->dropForeign(['milestone_id']);
            $table->dropColumn(['epic_id', 'milestone_id']);
        });
    }
};
