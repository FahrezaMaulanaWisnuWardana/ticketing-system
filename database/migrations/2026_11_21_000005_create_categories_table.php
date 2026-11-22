<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('kind', 16)->default('request');
            $table->jsonb('form_schema')->default('[]');
            $table->foreignId('default_assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestampsTz();
            $table->index(['project_id', 'is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
