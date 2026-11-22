<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCannedReplyRequest;
use App\Http\Requests\UpdateCannedReplyRequest;
use App\Models\CannedReply;
use App\Models\Project;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class CannedReplyController
{
    public function store(StoreCannedReplyRequest $request, Project $project): RedirectResponse
    {
        $reply = $project->cannedReplies()->create($request->validated());
        AuditService::record('canned_reply.created', $reply);

        return back()->with('status', __('canned.created'));
    }

    public function update(UpdateCannedReplyRequest $request, Project $project, CannedReply $reply): RedirectResponse
    {
        $reply->update($request->validated());
        AuditService::record('canned_reply.updated', $reply);

        return back()->with('status', __('canned.updated'));
    }

    public function destroy(Project $project, CannedReply $reply): RedirectResponse
    {
        Gate::authorize('delete', $reply);
        AuditService::record('canned_reply.deleted', $reply);
        $reply->delete();

        return back()->with('status', __('canned.deleted'));
    }
}
