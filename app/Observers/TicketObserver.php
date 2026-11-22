<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Ticket;
use App\Services\ActivityLogger;
use App\Services\ProjectMetricsService;

final class TicketObserver
{
    public function created(Ticket $ticket): void
    {
        ProjectMetricsService::forget($ticket->project_id);
        ActivityLogger::log($ticket, 'created', null, null, $ticket->title);
    }

    public function updated(Ticket $ticket): void
    {
        ProjectMetricsService::forget($ticket->project_id);
        $dirty = array_diff_key($ticket->getDirty(), array_flip(['updated_at', 'deleted_at']));
        if ($dirty === []) {
            return;
        }

        foreach ($dirty as $field => $value) {
            ActivityLogger::log(
                $ticket,
                $field === 'status' ? 'status_changed' : 'field_changed',
                $field,
                self::stringify($ticket->getOriginal($field)),
                self::stringify($value),
            );
        }
    }

    private static function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }
        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
