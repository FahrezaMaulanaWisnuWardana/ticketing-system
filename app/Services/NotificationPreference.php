<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class NotificationPreference
{
    public static function enabled(User $user, string $event, string $channel = 'mail'): bool
    {
        $preferences = $user->notification_prefs;
        $eventPreferences = $preferences[$event] ?? null;
        if (! is_array($eventPreferences)) {
            return true;
        }

        return (bool) ($eventPreferences[$channel] ?? true);
    }

    /** @param array<string, bool> $channels */
    public static function set(User $user, string $event, array $channels): void
    {
        $preferences = $user->notification_prefs ?? [];
        $preferences[$event] = $channels;
        $user->forceFill(['notification_prefs' => $preferences])->save();
    }
}
