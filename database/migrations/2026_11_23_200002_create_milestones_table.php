<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestones', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->date('due_date');
            $table->string('state', 16)->default('open');
            $table->timestampsTz();
            $table->index(['project_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};
