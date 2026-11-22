<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('comment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('disk', 32);
            $table->string('path', 512);
            $table->string('original_name', 255);
            $table->string('mime', 128);
            $table->bigInteger('size');
            $table->string('sha256', 64);
            $table->softDeletesTz();
            $table->timestampsTz();
            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
