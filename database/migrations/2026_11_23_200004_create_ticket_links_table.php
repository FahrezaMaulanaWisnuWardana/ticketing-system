<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_links', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('from_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('to_id')->constrained('tickets')->cascadeOnDelete();
            $table->string('type', 24);
            $table->timestampsTz();
            $table->unique(['from_id', 'to_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_links');
    }
};
