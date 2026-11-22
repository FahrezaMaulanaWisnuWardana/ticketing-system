<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('git_links', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 16);
            $table->string('ref', 200)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('state', 16)->default('open');
            $table->string('external_id', 100);
            $table->timestampsTz();
            $table->unique(['provider', 'external_id']);
            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('git_links');
    }
};
