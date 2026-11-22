<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\Project;
use App\Models\User;

final class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return true;
    }

    public function create(User $user, ?Project $project = null): bool
    {
        return $user->isAdmin()
            || ($project !== null && ($project->owner_id === $user->id || $user->isLeadOf($project)));
    }

    public function update(User $user, Category $category): bool
    {
        $project = Project::find($category->project_id);

        return $user->isAdmin()
            || ($project !== null && ($project->owner_id === $user->id || $user->isLeadOf($project)));
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->update($user, $category);
    }
}
