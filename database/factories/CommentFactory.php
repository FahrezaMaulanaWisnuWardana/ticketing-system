<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Comment> */
final class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'is_internal' => false,
        ];
    }

    public function internal(): self
    {
        return $this->state(fn (): array => ['is_internal' => true]);
    }
}
