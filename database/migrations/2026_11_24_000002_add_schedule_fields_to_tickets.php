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
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
        });
        Schema::table('tickets', static function (Blueprint $table): void {
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', static function (Blueprint $table): void {
            $table->dropIndex(['due_date']);
            $table->dropColumn(['start_date', 'due_date']);
        });
    }
};
