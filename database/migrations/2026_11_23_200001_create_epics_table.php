<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epics', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('key', 16);
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 24)->default('active');
            $table->string('color', 16)->default('indigo');
            $table->softDeletesTz();
            $table->timestampsTz();
            $table->unique(['project_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epics');
    }
};
