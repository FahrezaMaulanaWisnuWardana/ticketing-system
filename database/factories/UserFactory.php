<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Provider;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
final class UserFactory extends Factory
{
    protected $model = User::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'provider' => Provider::Local,
            'google_id' => null,
            'role' => Role::Requester,
            'is_active' => true,
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id',
            'theme' => 'light',
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): self
    {
        return $this->state(fn (): array => ['role' => Role::Admin]);
    }

    public function agent(): self
    {
        return $this->state(fn (): array => ['role' => Role::Agent]);
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function google(): self
    {
        return $this->state(fn (): array => [
            'provider' => Provider::Google,
            'password' => null,
            'google_id' => (string) fake()->unique()->randomNumber(9),
        ]);
    }
}
