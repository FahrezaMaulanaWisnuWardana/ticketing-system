<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canned_replies', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('body');
            $table->timestampsTz();
            $table->index(['project_id', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canned_replies');
    }
};
