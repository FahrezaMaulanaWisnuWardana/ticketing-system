<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CompleteSprint;
use App\Actions\StartSprint;
use App\Http\Requests\SaveFilterRequest;
use App\Http\Requests\StoreLabelRequest;
use App\Http\Requests\StoreSprintRequest;
use App\Models\Label;
use App\Models\Project;
use App\Models\SavedFilter;
use App\Models\Sprint;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class DevController extends Controller
{
    public function board(Project $project): View
    {
        Gate::authorize('view', $project);

        return view('dev.board', ['project' => $project]);
    }

    public function backlog(Project $project): View
    {
        Gate::authorize('view', $project);

        return view('dev.backlog', ['project' => $project]);
    }

    public function list(Project $project): View
    {
        Gate::authorize('view', $project);

        return view('dev.list', ['project' => $project]);
    }

    public function sprints(Project $project): View
    {
        Gate::authorize('view', $project);

        return view('dev.sprints', [
            'project' => $project,
            'sprints' => Sprint::where('project_id', $project->id)->orderByDesc('start_date')->get(),
        ]);
    }

    public function storeSprint(StoreSprintRequest $request, Project $project): RedirectResponse
    {
        $sprint = $project->sprints()->create($request->validated() + ['state' => 'planned']);
        AuditService::record('sprint.created', $sprint);

        return back()->with('status', __('dev.sprint_created'));
    }

    public function startSprint(Project $project, Sprint $sprint, StartSprint $start): RedirectResponse
    {
        Gate::authorize('manageSprint', $sprint);
        $start($project, $sprint);
        AuditService::record('sprint.started', $sprint);

        return back()->with('status', __('dev.sprint_started'));
    }

    public function completeSprint(Project $project, Sprint $sprint, CompleteSprint $complete): RedirectResponse
    {
        Gate::authorize('manageSprint', $sprint);
        $complete($sprint);
        AuditService::record('sprint.completed', $sprint);

        return back()->with('status', __('dev.sprint_completed'));
    }

    public function storeLabel(StoreLabelRequest $request, Project $project): RedirectResponse
    {
        $project->labels()->create($request->validated());

        return back()->with('status', __('dev.label_created'));
    }

    public function destroyLabel(Project $project, Label $label): RedirectResponse
    {
        Gate::authorize('manageLabel', $label);
        $label->delete();

        return back()->with('status', __('dev.label_deleted'));
    }

    public function saveFilter(SaveFilterRequest $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }
        SavedFilter::updateOrCreate(
            ['user_id' => $user->id, 'name' => (string) $request->input('name')],
            ['filters' => $request->input('filters')],
        );

        return back()->with('status', __('dev.filter_saved'));
    }

    public function destroyFilter(SavedFilter $filter): RedirectResponse
    {
        Gate::authorize('manageFilter', $filter);
        $filter->delete();

        return back()->with('status', __('dev.filter_deleted'));
    }
}
