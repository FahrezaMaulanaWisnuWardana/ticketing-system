<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Provider;
use App\Enums\Role;
use App\Models\User;
use App\Notifications\InviteUser;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

final class CreateUser
{
    /** @param array<string, mixed> $data */
    public function __invoke(User $actor, array $data): User
    {
        return DB::transaction(static function () use ($actor, $data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'role' => Role::from($data['role']),
                'provider' => Provider::Local,
                'is_active' => true,
                'password' => null,
            ]);

            $url = URL::temporarySignedRoute(
                'invitations.edit',
                now()->addHours((int) config('ticketing.invite_ttl_hours')),
                ['user' => $user->id],
            );

            $user->notify(new InviteUser($url));
            AuditService::record('invite.sent', $user, [], $actor);

            return $user;
        });
    }
}
