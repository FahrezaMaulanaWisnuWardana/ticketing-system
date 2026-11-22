<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_connections', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('provider', 16)->default('trello');
            $table->string('trello_board_id');
            $table->string('trello_board_name')->nullable();
            $table->text('api_key_encrypted');
            $table->text('token_encrypted');
            $table->string('mode', 24)->default('import_only');
            $table->string('status', 24)->default('active');
            $table->timestampTz('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->string('webhook_id')->nullable();
            $table->timestampsTz();
            $table->unique(['project_id', 'provider']);
        });

        Schema::create('external_links', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 16)->default('trello');
            $table->string('external_id');
            $table->string('external_url', 500)->nullable();
            $table->string('last_synced_hash', 64)->nullable();
            $table->timestampTz('last_synced_at')->nullable();
            $table->string('external_state', 24)->default('ok');
            $table->jsonb('field_hashes')->default('{}');
            $table->timestampTz('last_inbound_at')->nullable();
            $table->timestampTz('last_outbound_at')->nullable();
            $table->timestampsTz();
            $table->unique(['provider', 'external_id']);
            $table->unique(['ticket_id', 'provider']);
        });

        Schema::create('status_mappings', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('connection_id')->constrained('integration_connections')->cascadeOnDelete();
            $table->string('trello_list_id');
            $table->string('trello_list_name')->nullable();
            $table->string('ticket_status', 32);
            $table->string('direction', 24)->default('both');
            $table->timestampsTz();
            $table->unique(['connection_id', 'trello_list_id']);
        });

        Schema::create('import_runs', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('connection_id')->constrained('integration_connections')->cascadeOnDelete();
            $table->foreignId('started_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->default('pending');
            $table->jsonb('counts')->default('{}');
            $table->jsonb('errors')->default('[]');
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('member_mappings', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('connection_id')->constrained('integration_connections')->cascadeOnDelete();
            $table->string('trello_member_id');
            $table->string('trello_username')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['connection_id', 'trello_member_id']);
        });

        Schema::create('integration_logs', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('connection_id')->nullable()->constrained('integration_connections')->cascadeOnDelete();
            $table->string('level', 16);
            $table->string('event', 100);
            $table->jsonb('payload')->default('{}');
            $table->timestampTz('created_at')->nullable();
            $table->index(['connection_id', 'created_at']);
        });

        Schema::create('sync_conflicts', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('connection_id')->constrained('integration_connections')->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->string('field', 64);
            $table->text('app_value')->nullable();
            $table->text('trello_value')->nullable();
            $table->string('resolution', 24)->default('app_wins');
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampTz('created_at')->nullable();
            $table->index(['connection_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_conflicts');
        Schema::dropIfExists('integration_logs');
        Schema::dropIfExists('member_mappings');
        Schema::dropIfExists('import_runs');
        Schema::dropIfExists('status_mappings');
        Schema::dropIfExists('external_links');
        Schema::dropIfExists('integration_connections');
    }
};
