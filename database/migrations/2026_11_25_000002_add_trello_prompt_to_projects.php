<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', static function (Blueprint $table): void {
            $table->string('trello_prompt', 16)->default('unasked');
            $table->timestampTz('trello_prompt_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('projects', static function (Blueprint $table): void {
            $table->dropColumn(['trello_prompt', 'trello_prompt_at']);
        });
    }
};
