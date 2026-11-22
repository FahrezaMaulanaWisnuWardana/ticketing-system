<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', static function (Blueprint $table): void {
            // Rich-text editors upload files before the ticket/reply exists. Those
            // rows are "pending" until the form is submitted and claimed by it.
            $table->dropForeign(['ticket_id']);
            $table->foreignId('ticket_id')->nullable()->change();
            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attachments', static function (Blueprint $table): void {
            $table->dropForeign(['ticket_id']);
            $table->foreignId('ticket_id')->nullable(false)->change();
            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
        });
    }
};
