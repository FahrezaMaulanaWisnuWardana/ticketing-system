<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Enums\ProjectTemplate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Project> */
final class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'key' => strtoupper(fake()->unique()->lexify('?????')),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'owner_id' => User::factory(),
            'status' => ProjectStatus::Active,
            'template' => ProjectTemplate::Blank,
            'ticket_seq' => 0,
        ];
    }
}
