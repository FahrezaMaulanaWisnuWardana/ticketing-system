<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CannedReply;
use App\Models\Project;
use App\Models\User;

final class CannedReplyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAgent();
    }

    public function view(User $user, CannedReply $reply): bool
    {
        return $user->isAgent();
    }

    public function create(User $user, ?Project $project = null): bool
    {
        return $user->isAdmin()
            || ($project !== null && ($project->owner_id === $user->id || $user->isLeadOf($project)));
    }

    public function update(User $user, CannedReply $reply): bool
    {
        return $this->manage($user, $reply);
    }

    public function delete(User $user, CannedReply $reply): bool
    {
        return $this->manage($user, $reply);
    }

    private function manage(User $user, CannedReply $reply): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($reply->project_id === null) {
            return false;
        }
        $project = Project::find($reply->project_id);

        return $project !== null && ($project->owner_id === $user->id || $user->isLeadOf($project));
    }
}
