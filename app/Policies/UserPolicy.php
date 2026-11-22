<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAgent();
    }

    public function view(User $user, User $subject): bool
    {
        return $user->isAdmin() || $user->isAgent() || $user->id === $subject->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $subject): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $subject): bool
    {
        return $user->isAdmin() && $user->id !== $subject->id;
    }

    public function activate(User $user, User $subject): bool
    {
        return $user->isAdmin();
    }

    public function changeRole(User $user, User $subject): bool
    {
        return $user->isAdmin() && $user->id !== $subject->id;
    }

    public function resetTwoFactor(User $user, User $subject): bool
    {
        return $user->isAdmin();
    }

    public function import(User $user): bool
    {
        return $user->isAdmin();
    }
}
