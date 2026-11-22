<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\SlaEvent;
use App\Models\Sprint;
use App\Models\Ticket;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class ReportService
{
    /** @return array<string, mixed> */
    public function summary(Project $project): array
    {
        return Cache::remember("reports.summary.{$project->id}", now()->addMinutes(10), static function () use ($project): array {
            $status = Ticket::withoutGlobalScopes()
                ->where('project_id', $project->id)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all();

            $assignee = Ticket::withoutGlobalScopes()
                ->join('users', 'users.id', '=', 'tickets.assignee_id')
                ->where('tickets.project_id', $project->id)
                ->select('users.name', DB::raw('count(*) as total'))
                ->groupBy('users.name')
                ->pluck('total', 'name')
                ->all();

            $overdue = (int) Ticket::withoutGlobalScopes()
                ->where('project_id', $project->id)
                ->whereNotNull('due_date')
                ->where('due_date', '<', now()->toDateString())
                ->whereNotIn('status', [TicketStatus::Done, TicketStatus::Closed])
                ->count();

            $requests = (int) Ticket::withoutGlobalScopes()->where('project_id', $project->id)->where('kind', 'request')->count();
            $breached = (int) SlaEvent::whereIn('ticket_id', function ($query) use ($project): void {
                $query->select('id')->from('tickets')->where('project_id', $project->id);
            })->where('channel', 'resolve')->where('kind', 'breached')->count();

            return [
                'status' => $status,
                'assignee' => $assignee,
                'overdue' => $overdue,
                'sla_hit_rate' => $requests > 0 ? (int) round((1 - ($breached / $requests)) * 100) : 100,
                'avg_first_response_minutes' => (int) round((float) (DB::table('tickets')
                    ->where('project_id', $project->id)
                    ->whereNotNull('first_response_at')
                    ->selectRaw('avg(extract(epoch from (first_response_at - created_at))/60) as avg')
                    ->value('avg') ?? 0)),
                'avg_resolve_minutes' => (int) round((float) (DB::table('tickets')
                    ->where('project_id', $project->id)
                    ->whereNotNull('resolved_at')
                    ->selectRaw('avg(extract(epoch from (resolved_at - created_at))/60) as avg')
                    ->value('avg') ?? 0)),
            ];
        });
    }

    /** @return array<int, array{date: string, remaining_points: int}> */
    public function burndown(Sprint $sprint): array
    {
        $rows = [];
        foreach ($sprint->snapshots()->orderBy('date')->get() as $snapshot) {
            $rows[] = [
                'date' => (string) $snapshot->date,
                'remaining_points' => $snapshot->remaining_points,
            ];
        }

        return $rows;
    }

    /** @return array<int, array{name: string, points: int}> */
    public function velocity(Project $project): array
    {
        return Sprint::where('project_id', $project->id)
            ->where('state', 'closed')
            ->orderByDesc('start_date')
            ->limit(6)
            ->get()
            ->map(static fn (Sprint $sprint): array => [
                'name' => $sprint->name,
                'points' => (int) $sprint->tickets()->whereIn('status', [TicketStatus::Done, TicketStatus::Closed])->sum('estimate'),
            ])->all();
    }
}
