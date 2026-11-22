<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\ProgressCalculator;
use App\Domain\ProjectHealth;
use App\Enums\TicketStatus;
use App\Models\Milestone;
use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

final class ProjectMetricsService
{
    /** @return array{progress: int, total: int, done: int, health: string, overdue_milestones: int} */
    public function for(Project $project): array
    {
        return Cache::remember($this->key($project->id), now()->addMinutes(10), static function () use ($project): array {
            $done = (int) $project->tickets()->whereIn('status', [TicketStatus::Done, TicketStatus::Closed])->count();
            $total = (int) $project->tickets()->count();
            $progress = ProgressCalculator::percent($done, $total);
            $overdue = (int) Milestone::where('project_id', $project->id)
                ->where('state', 'open')
                ->where('due_date', '<', now()->toDateString())
                ->count();
            $start = $project->start_date?->toImmutable();
            $due = $project->due_date?->toImmutable();

            return [
                'progress' => $progress,
                'total' => $total,
                'done' => $done,
                'health' => ProjectHealth::calculate(
                    $progress,
                    $start instanceof CarbonImmutable ? $start : null,
                    $due instanceof CarbonImmutable ? $due : null,
                    $overdue > 0,
                ),
                'overdue_milestones' => $overdue,
            ];
        });
    }

    public static function forget(int $projectId): void
    {
        Cache::forget("project.metrics.{$projectId}");
    }

    private function key(int $projectId): string
    {
        return "project.metrics.{$projectId}";
    }
}
