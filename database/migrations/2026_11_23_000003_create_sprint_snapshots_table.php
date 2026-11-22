<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprint_snapshots', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sprint_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedSmallInteger('remaining_points')->default(0);
            $table->unsignedSmallInteger('remaining_count')->default(0);
            $table->timestampsTz();
            $table->unique(['sprint_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sprint_snapshots');
    }
};
