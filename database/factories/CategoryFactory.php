<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TicketKind;
use App\Models\Category;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
final class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->word(),
            'kind' => TicketKind::Request,
            'form_schema' => [],
            'is_active' => true,
            'sort' => 0,
        ];
    }
}
