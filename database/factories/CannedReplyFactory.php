<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CannedReply;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CannedReply> */
final class CannedReplyFactory extends Factory
{
    protected $model = CannedReply::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->words(2, true),
            'body' => fake()->sentence(),
        ];
    }
}
