<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Sprint> */
final class SprintFactory extends Factory
{
    protected $model = Sprint::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => 'Sprint '.fake()->unique()->numberBetween(1, 9999),
            'goal' => fake()->sentence(),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(13)->toDateString(),
            'state' => 'planned',
            'capacity' => 40,
        ];
    }
}
