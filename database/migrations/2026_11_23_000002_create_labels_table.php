<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labels', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('color', 16)->default('slate');
            $table->timestampsTz();
            $table->unique(['project_id', 'name']);
        });
        Schema::create('ticket_label', static function (Blueprint $table): void {
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('label_id')->constrained()->cascadeOnDelete();
            $table->primary(['ticket_id', 'label_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_label');
        Schema::dropIfExists('labels');
    }
};
