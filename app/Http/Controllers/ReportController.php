<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ReportService;
use App\Support\CsvExport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportController extends Controller
{
    public function index(Project $project, ReportService $reports): View
    {
        Gate::authorize('view', $project);

        $sprint = $project->sprints()->where('state', 'active')->first();

        return view('reports.index', [
            'project' => $project,
            'summary' => $reports->summary($project),
            'burndown' => $sprint !== null ? $reports->burndown($sprint) : [],
            'velocity' => $reports->velocity($project),
        ]);
    }

    public function export(Request $request, Project $project, ReportService $reports): StreamedResponse
    {
        Gate::authorize('view', $project);
        $summary = $reports->summary($project);
        $type = (string) $request->input('type', 'status');
        $rows = match ($type) {
            'assignee' => self::pairs($summary['assignee']),
            'overdue' => [['overdue', $summary['overdue']]],
            'sla' => [['hit_rate', $summary['sla_hit_rate']]],
            default => self::pairs($summary['status']),
        };

        return CsvExport::response($rows, "report-{$project->key}-{$type}.csv");
    }

    /** @param array<string, mixed> $values
     *  @return array<int, array<int, mixed>> */
    private static function pairs(array $values): array
    {
        $rows = [];
        foreach ($values as $key => $value) {
            $rows[] = [(string) $key, $value];
        }

        return $rows;
    }
}
