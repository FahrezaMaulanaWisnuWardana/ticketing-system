<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_events', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 24);
            $table->string('channel', 24);
            $table->timestampTz('fired_at');
            $table->timestampsTz();
            $table->unique(['ticket_id', 'kind', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_events');
    }
};
