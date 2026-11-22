<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Support\DateFormat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;

/** Builds the project overview summary: period counts, status split and recent activity. */
final class ProjectSummaryService
{
    /** @var list<int> */
    public const PERIODS = [7, 30, 90];

    /**
     * @return array{
     *     days: int,
     *     periods: list<int>,
     *     completed: int,
     *     updated: int,
     *     created: int,
     *     due_soon: int,
     *     total: int,
     *     statuses: list<array{key: string, label: string, count: int, color: string, percent: float}>,
     *     gradient: string,
     *     activities: list<array{id: int, user: string, event: string, event_label: string, ticket_key: string, ticket_title: string, status: TicketStatus|null, time: string, comment: string|null}>
     * }
     */
    public function for(Project $project, int $days = 7): array
    {
        $days = in_array($days, self::PERIODS, true) ? $days : 7;
        $since = now()->subDays($days);
        $final = TicketStatus::Done->groupValues();

        $completed = $this->tickets($project)
            ->whereIn('status', $final)
            ->where(static function (Builder $query) use ($since): void {
                $query->where('resolved_at', '>=', $since)
                    ->orWhere('closed_at', '>=', $since)
                    ->orWhere(static function (Builder $query) use ($since): void {
                        $query->whereNull('resolved_at')
                            ->whereNull('closed_at')
                            ->where('updated_at', '>=', $since);
                    });
            })->count();

        $updated = $this->tickets($project)->where('updated_at', '>=', $since)->count();
        $created = $this->tickets($project)->where('created_at', '>=', $since)->count();
        $dueSoon = $this->tickets($project)
            ->whereNotIn('status', $final)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->count();

        [$statuses, $gradient] = $this->statusBreakdown($project);

        return [
            'days' => $days,
            'periods' => self::PERIODS,
            'completed' => $completed,
            'updated' => $updated,
            'created' => $created,
            'due_soon' => $dueSoon,
            'total' => array_sum(array_column($statuses, 'count')),
            'statuses' => $statuses,
            'gradient' => $gradient,
            'activities' => $this->recentActivity($project),
        ];
    }

    /**
     * @return array{0: list<array{key: string, label: string, count: int, color: string, percent: float}>, 1: string}
     */
    private function statusBreakdown(Project $project): array
    {
        $counts = $this->tickets($project)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Fold legacy values into their canonical group before charting.
        $grouped = [];
        foreach ($counts as $value => $count) {
            $status = TicketStatus::tryFrom((string) $value)?->group();
            if ($status === null) {
                continue;
            }
            $grouped[$status->value] = ($grouped[$status->value] ?? 0) + (int) $count;
        }

        $statuses = [];
        $total = 0;
        foreach (TicketStatus::canonicalCases() as $status) {
            $count = (int) ($grouped[$status->value] ?? 0);
            if ($count === 0) {
                continue;
            }
            $total += $count;
            $statuses[] = [
                'key' => $status->value,
                'label' => $this->translation($status->labelKey()),
                'count' => $count,
                'color' => $status->colorHex(),
                'percent' => 0.0,
            ];
        }

        $segments = [];
        $offset = 0.0;
        $last = count($statuses) - 1;
        foreach ($statuses as $index => $status) {
            $percent = $total > 0 ? round($status['count'] / $total * 100, 2) : 0.0;
            $end = $index === $last ? 100.0 : $offset + $percent;
            $statuses[$index]['percent'] = $percent;
            $segments[] = sprintf('%s %.2f%% %.2f%%', $status['color'], $offset, $end);
            $offset = $end;
        }

        return [$statuses, $segments === [] ? '#e2e8f0 0% 100%' : implode(', ', $segments)];
    }

    /**
     * @return list<array{id: int, user: string, event: string, event_label: string, ticket_key: string, ticket_title: string, status: TicketStatus|null, time: string, comment: string|null}>
     */
    private function recentActivity(Project $project): array
    {
        $activities = Activity::query()
            ->with(['user:id,name', 'ticket.project:id,key'])
            ->whereHas('ticket', static fn (Builder $query): Builder => $query->where('project_id', $project->id))
            ->latest()
            ->limit(6)
            ->get();

        $result = [];
        foreach ($activities as $activity) {
            $key = 'pm.summary.event.'.$activity->event;
            $user = $activity->user instanceof User ? $activity->user : null;
            $ticket = $activity->ticket instanceof Ticket ? $activity->ticket : null;

            $result[] = [
                'id' => $activity->id,
                'user' => $user->name ?? $this->translation('pm.summary.someone'),
                'event' => $activity->event,
                'event_label' => Lang::has($key) ? $this->translation($key) : $activity->event,
                'ticket_key' => $ticket->key ?? '',
                'ticket_title' => $ticket->title ?? '',
                'status' => $ticket->status ?? null,
                'time' => DateFormat::format($activity->created_at),
                'comment' => $activity->event === 'commented'
                    && $ticket instanceof Ticket
                    && Gate::allows('viewInternalNotes', $ticket)
                        ? $activity->new_value
                        : null,
            ];
        }

        return $result;
    }

    /** @param array<string, string|int> $replace */
    private function translation(string $key, array $replace = []): string
    {
        $translation = __($key, $replace);

        return is_string($translation) ? $translation : $key;
    }

    /** @return Builder<Ticket> */
    private function tickets(Project $project): Builder
    {
        return Ticket::query()->where('project_id', $project->id);
    }
}
