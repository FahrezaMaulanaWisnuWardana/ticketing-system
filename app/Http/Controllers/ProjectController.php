<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateProject;
use App\Enums\ProjectRole;
use App\Enums\TrelloPrompt;
use App\Http\Requests\ProjectMemberRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectMemberRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::visible()
            ->with('owner:id,name', 'members:id,name')
            ->withCount('tickets')
            ->orderBy('name')
            ->paginate(15);

        return view('projects.index', ['projects' => $projects]);
    }

    public function create(): View
    {
        Gate::authorize('create', Project::class);

        return view('projects.create');
    }

    public function store(StoreProjectRequest $request, CreateProject $create): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }
        $project = $create($user, $request->validated());
        AuditService::record('project.created', $project);

        if (config('trello.enabled')) {
            $choice = (string) $request->input('trello_choice', 'unasked');
            $prompt = match ($choice) {
                'no' => TrelloPrompt::No,
                'later' => TrelloPrompt::Later,
                'yes' => TrelloPrompt::Connected,
                default => TrelloPrompt::Unasked,
            };
            $project->forceFill(['trello_prompt' => $prompt, 'trello_prompt_at' => now()])->save();
            if ($choice === 'yes') {
                return redirect()->route('trello.connect', $project);
            }
        }

        return redirect()->route('projects.show', $project)->with('status', __('projects.created'));
    }

    public function show(Project $project): View
    {
        Gate::authorize('view', $project);

        $tickets = $project->tickets()
            ->with('assignee:id,name', 'requester:id,name', 'category:id,name')
            ->orderBy('rank')
            ->paginate(25);

        return view('projects.show', [
            'project' => $project->load('owner:id,name', 'members:id,name'),
            'tickets' => $tickets,
        ]);
    }

    public function edit(Project $project): View
    {
        Gate::authorize('update', $project);

        return view('projects.edit', ['project' => $project]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());
        AuditService::record('project.updated', $project);

        return redirect()->route('projects.show', $project)->with('status', __('projects.updated'));
    }

    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);
        $project->delete();
        AuditService::record('project.deleted', $project);

        return redirect()->route('projects.index')->with('status', __('projects.deleted'));
    }

    public function addMember(ProjectMemberRequest $request, Project $project): RedirectResponse
    {
        $validated = $request->validated();
        $project->members()->syncWithoutDetaching([
            (int) $validated['user_id'] => ['role' => $validated['role']],
        ]);
        AuditService::record('project.member_added', $project, ['user_id' => $validated['user_id']]);

        return back()->with('status', __('projects.member_added'));
    }

    public function removeMember(Project $project, User $member): RedirectResponse
    {
        Gate::authorize('manageMembers', $project);
        $project->members()->detach($member->id);
        AuditService::record('project.member_removed', $project, ['user_id' => $member->id]);

        return back()->with('status', __('projects.member_removed'));
    }

    public function setMemberRole(UpdateProjectMemberRequest $request, Project $project, User $member): RedirectResponse
    {
        $validated = $request->validated();
        $project->members()->updateExistingPivot($member->id, ['role' => ProjectRole::from((string) $validated['role'])->value]);

        return back()->with('status', __('projects.updated'));
    }
}
