<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', static function (Blueprint $table): void {
            $table->id();
            $table->string('key', 10)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->default('active');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('template', 24)->default('blank');
            $table->bigInteger('ticket_seq')->default(0);
            $table->timestampTz('archived_at')->nullable();
            $table->softDeletesTz();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
