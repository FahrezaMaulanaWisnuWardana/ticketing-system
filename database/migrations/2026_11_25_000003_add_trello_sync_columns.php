<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_connections', static function (Blueprint $table): void {
            $table->boolean('mirror_helpdesk')->default(false);
        });
        Schema::table('activities', static function (Blueprint $table): void {
            $table->string('source', 24)->default('app');
            $table->jsonb('metadata')->default('{}');
        });
    }

    public function down(): void
    {
        Schema::table('activities', static function (Blueprint $table): void {
            $table->dropColumn(['source', 'metadata']);
        });
        Schema::table('integration_connections', static function (Blueprint $table): void {
            $table->dropColumn('mirror_helpdesk');
        });
    }
};
