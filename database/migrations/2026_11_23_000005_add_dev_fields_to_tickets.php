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
            $table->unsignedSmallInteger('estimate')->nullable()->after('description');
            $table->foreignId('sprint_id')->nullable()->after('estimate')->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->after('sprint_id')->constrained('tickets')->nullOnDelete();
        });
        Schema::table('tickets', static function (Blueprint $table): void {
            $table->index(['sprint_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', static function (Blueprint $table): void {
            $table->dropIndex(['sprint_id', 'rank']);
            $table->dropForeign(['sprint_id']);
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['estimate', 'sprint_id', 'parent_id']);
        });
    }
};
