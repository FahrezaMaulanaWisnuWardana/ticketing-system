<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Attachment> */
final class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'disk' => 'attachments',
            'path' => 'tickets/'.fake()->uuid().'.txt',
            'original_name' => fake()->word().'.txt',
            'mime' => 'text/plain',
            'size' => fake()->numberBetween(10, 1000),
            'sha256' => str_repeat('a', 64),
        ];
    }
}
