<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\Ticket;
use App\Models\User;

final class ActivityLogger
{
    public static function log(
        Ticket $ticket,
        string $event,
        ?string $field = null,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?User $actor = null,
    ): Activity {
        return Activity::create([
            'ticket_id' => $ticket->id,
            'user_id' => ($actor ?? auth('web')->user())?->id,
            'event' => $event,
            'field' => $field,
            'old_value' => $oldValue === null ? null : mb_substr($oldValue, 0, 1000),
            'new_value' => $newValue === null ? null : mb_substr($newValue, 0, 1000),
        ]);
    }
}
