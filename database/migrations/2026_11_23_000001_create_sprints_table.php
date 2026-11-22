<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprints', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('goal')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('state', 16)->default('planned');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestampsTz();
            $table->index(['project_id', 'state']);
        });
        DB::statement("CREATE UNIQUE INDEX sprints_one_active_idx ON sprints (project_id) WHERE state = 'active'");
    }

    public function down(): void
    {
        Schema::dropIfExists('sprints');
    }
};
