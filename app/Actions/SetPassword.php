<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class SetPassword
{
    public function __invoke(User $user, string $password): User
    {
        DB::transaction(static function () use ($user, $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
            AuditService::record('password.set', $user, [], $user);
        });

        return $user->refresh();
    }
}
