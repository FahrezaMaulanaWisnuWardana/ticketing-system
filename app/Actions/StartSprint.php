<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Validation\ValidationException;

final class StartSprint
{
    public function __invoke(Project $project, Sprint $sprint): Sprint
    {
        if ($sprint->project_id !== $project->id) {
            throw ValidationException::withMessages(['sprint' => __('dev.invalid_sprint')]);
        }
        if (Sprint::where('project_id', $project->id)->where('state', 'active')->exists()) {
            throw ValidationException::withMessages(['sprint' => __('dev.active_sprint_exists')]);
        }
        $sprint->update(['state' => 'active']);

        return $sprint->refresh();
    }
}
