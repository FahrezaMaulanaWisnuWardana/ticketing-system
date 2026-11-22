<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AddComment;
use App\Actions\AttachPending;
use App\Http\Requests\AddCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class CommentController
{
    public function store(AddCommentRequest $request, Ticket $ticket, AddComment $add): RedirectResponse
    {
        Gate::authorize('create', [Comment::class, $ticket]);
        $data = $request->validated();
        $internal = (bool) ($data['is_internal'] ?? false);
        if ($internal) {
            Gate::authorize('viewInternalNotes', $ticket);
        }

        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }
        $add($ticket, $user, (string) $data['body'], $internal, $request->file('attachments', []));

        return back()->with('status', __('comments.created'));
    }

    public function update(UpdateCommentRequest $request, Ticket $ticket, Comment $comment, AttachPending $attach): RedirectResponse
    {
        $comment->update(['body' => (string) $request->input('body'), 'edited_at' => now()]);
        $attach(AttachPending::parse($request->input('attachment_ids', '')), $ticket, $comment);
        AuditService::record('comment.updated', $comment);

        return back()->with('status', __('comments.updated'));
    }

    public function destroy(Ticket $ticket, Comment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);
        AuditService::record('comment.deleted', $comment, ['ticket_id' => $ticket->id]);
        $comment->delete();

        return back()->with('status', __('comments.deleted'));
    }
}
