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
        Schema::create('tickets', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('number');
            $table->string('kind', 16)->default('request');
            $table->string('type', 32)->default('task');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('status', 32)->default('new');
            $table->string('priority', 16)->default('normal');
            $table->string('severity', 16)->nullable();
            $table->string('environment', 64)->nullable();
            $table->foreignId('reporter_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rank', 50)->default('5555555555');
            $table->boolean('blocked')->default(false);
            $table->timestampTz('first_response_at')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampTz('closed_at')->nullable();
            $table->timestampTz('sla_response_due_at')->nullable();
            $table->timestampTz('sla_resolve_due_at')->nullable();
            $table->timestampTz('sla_paused_at')->nullable();
            $table->integer('sla_paused_seconds')->default(0);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->unique(['project_id', 'number']);
            $table->index(['project_id', 'status']);
            $table->index(['assignee_id', 'status']);
            $table->index('requester_id');
        });

        DB::statement('ALTER TABLE tickets ALTER COLUMN rank TYPE varchar(50) COLLATE "C"');
        DB::statement('CREATE INDEX tickets_rank_idx ON tickets (rank)');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX tickets_title_trgm_idx ON tickets USING gin (title gin_trgm_ops)');
        DB::statement("CREATE INDEX tickets_sla_resolve_due_idx ON tickets (sla_resolve_due_at) WHERE status NOT IN ('done', 'closed')");
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
