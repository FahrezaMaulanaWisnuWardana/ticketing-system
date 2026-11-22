<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IntegrationConnection;
use App\Models\Project;
use App\Models\User;

final class TrelloPolicy
{
    public function viewAny(User $user): bool
    {
        return config('trello.enabled') === true && $user->isAgent();
    }

    public function view(User $user, IntegrationConnection $connection): bool
    {
        $project = Project::find($connection->project_id);

        return $project !== null && ($user->isAdmin() || $project->owner_id === $user->id || $user->isLeadOf($project));
    }

    public function manage(User $user, IntegrationConnection $connection): bool
    {
        return $this->view($user, $connection);
    }

    public function connect(User $user, Project $project): bool
    {
        return config('trello.enabled') === true
            && ($user->isAdmin() || $project->owner_id === $user->id || $user->isLeadOf($project));
    }
}
