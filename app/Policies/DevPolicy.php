<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Label;
use App\Models\Project;
use App\Models\SavedFilter;
use App\Models\Sprint;
use App\Models\User;

final class DevPolicy
{
    public function manageSprints(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->owner_id === $user->id || $user->isLeadOf($project);
    }

    public function viewSprint(User $user, Sprint $sprint): bool
    {
        $project = Project::find($sprint->project_id);

        return $project !== null && app(ProjectPolicy::class)->view($user, $project);
    }

    public function manageSprint(User $user, Sprint $sprint): bool
    {
        $project = Project::find($sprint->project_id);

        return $project !== null && $this->manageSprints($user, $project);
    }

    public function manageLabels(User $user, Project $project): bool
    {
        return $this->manageSprints($user, $project);
    }

    public function manageLabel(User $user, Label $label): bool
    {
        $project = Project::find($label->project_id);

        return $project !== null && $this->manageSprints($user, $project);
    }

    public function manageFilter(User $user, SavedFilter $filter): bool
    {
        return $filter->user_id === $user->id;
    }
}
