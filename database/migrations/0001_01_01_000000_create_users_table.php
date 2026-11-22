<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS citext');

        Schema::create('users', static function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestampTz('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('provider', 16)->default('local');
            $table->string('google_id')->nullable()->unique();
            $table->string('role', 16)->default('requester');
            $table->boolean('is_active')->default(true);
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->string('locale', 8)->default('id');
            $table->timestampTz('last_login_at')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->jsonb('two_factor_recovery_codes')->nullable();
            $table->timestampTz('two_factor_confirmed_at')->nullable();
            $table->jsonb('notification_prefs')->default('{}');
            $table->rememberToken();
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE users ALTER COLUMN email TYPE citext');
        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');

        Schema::create('password_reset_tokens', static function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestampTz('created_at')->nullable();
        });

        Schema::create('sessions', static function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
