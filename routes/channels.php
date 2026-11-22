<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('project.{project}', static function (User $user, Project $project): bool {
    return $user->isAdmin()
        || $project->owner_id === $user->id
        || $user->isMemberOf($project);
});

Broadcast::channel('user.{user}', static function (User $user, User $subject): bool {
    return $user->id === $subject->id;
});
