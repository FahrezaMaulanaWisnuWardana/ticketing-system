<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateDevTicketFromRequest;
use App\Http\Requests\StoreEpicRequest;
use App\Http\Requests\StoreMilestoneRequest;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Ticket;
use App\Services\AuditService;
use App\Services\ProjectMetricsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ProjectManagementController extends Controller
{
    public function overview(Project $project): View
    {
        Gate::authorize('view', $project);

        return view('projects.overview', [
            'project' => $project->load('owner:id,name', 'members:id,name'),
            'metrics' => app(ProjectMetricsService::class)->for($project),
            'milestones' => $project->milestones()->orderBy('due_date')->get(),
            'epics' => $project->epics()->withCount('tickets')->get(),
            'workload' => $this->workloadData($project),
        ]);
    }

    public function epics(Project $project): View
    {
        Gate::authorize('view', $project);

        return view('projects.epics', [
            'project' => $project,
            'epics' => $project->epics()->withCount('tickets')->get(),
        ]);
    }

    public function storeEpic(StoreEpicRequest $request, Project $project): RedirectResponse
    {
        $project->epics()->create($request->validated());

        return back()->with('status', __('pm.epic_created'));
    }

    public function storeMilestone(StoreMilestoneRequest $request, Project $project): RedirectResponse
    {
        $project->milestones()->create($request->validated());
        ProjectMetricsService::forget($project->id);

        return back()->with('status', __('pm.milestone_created'));
    }

    public function timeline(Project $project): View
    {
        Gate::authorize('view', $project);
        $tasks = collect();
        foreach ($project->epics()->get() as $epic) {
            $tasks->push([
                'id' => 'epic-'.$epic->id, 'name' => $epic->name,
                'start' => $epic->start_date?->toDateString() ?? now()->toDateString(),
                'end' => $epic->due_date?->toDateString() ?? now()->addDays(7)->toDateString(),
                'progress' => app(ProjectMetricsService::class)->for($project)['progress'],
                'type' => 'epic',
            ]);
        }
        foreach ($project->milestones()->get() as $milestone) {
            $tasks->push([
                'id' => 'milestone-'.$milestone->id, 'name' => $milestone->name,
                'start' => $milestone->due_date?->toDateString() ?? now()->toDateString(), 'end' => $milestone->due_date?->toDateString() ?? now()->toDateString(),
                'progress' => $milestone->state === 'reached' ? 100 : 0, 'type' => 'milestone',
            ]);
        }

        return view('projects.timeline', ['project' => $project, 'tasks' => $tasks->all()]);
    }

    public function workload(Project $project): View
    {
        Gate::authorize('view', $project);

        return view('projects.workload', [
            'project' => $project,
            'workload' => $this->workloadData($project),
        ]);
    }

    public function createDevTicket(Ticket $ticket, CreateDevTicketFromRequest $create): RedirectResponse
    {
        Gate::authorize('view', $ticket);
        if (! auth()->user()?->isAgent()) {
            abort(403);
        }
        $dev = $create($ticket, auth()->user());
        AuditService::record('ticket.dev_created', $dev, ['from' => $ticket->id]);

        return redirect()->route('tickets.show', $dev)->with('status', __('pm.dev_ticket_created'));
    }

    /** @return array<int, array{name: string, count: int, points: int}> */
    private function workloadData(Project $project): array
    {
        $sprint = Sprint::where('project_id', $project->id)->where('state', 'active')->first();
        $tickets = Ticket::withoutGlobalScopes()
            ->with('assignee:id,name')
            ->where('project_id', $project->id)
            ->when($sprint !== null, static fn ($query) => $query->where('sprint_id', $sprint?->id))
            ->whereNotNull('assignee_id')
            ->get();

        $data = [];
        foreach ($tickets->groupBy('assignee_id') as $rows) {
            $user = $rows->first()?->assignee;
            if ($user === null) {
                continue;
            }
            $data[] = [
                'name' => $user->name,
                'count' => $rows->count(),
                'points' => (int) $rows->sum('estimate'),
            ];
        }

        return $data;
    }
}
