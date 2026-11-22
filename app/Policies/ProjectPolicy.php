<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

final class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $user->isAdmin()
            || $project->owner_id === $user->id
            || $user->isMemberOf($project);
    }

    public function create(User $user): bool
    {
        return $user->isAgent();
    }

    public function update(User $user, Project $project): bool
    {
        return $this->isLeadOrOwner($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $this->isLeadOrOwner($user, $project);
    }

    public function manageCategories(User $user, Project $project): bool
    {
        return $this->isLeadOrOwner($user, $project);
    }

    public function viewMembers(User $user, Project $project): bool
    {
        return $this->view($user, $project);
    }

    private function isLeadOrOwner(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->owner_id === $user->id || $user->isLeadOf($project);
    }
}
