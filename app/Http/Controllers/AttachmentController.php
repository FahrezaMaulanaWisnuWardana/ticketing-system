<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AddAttachment;
use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class AttachmentController
{
    public function store(StoreAttachmentRequest $request, Ticket $ticket, AddAttachment $add): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }
        $attachment = $add($ticket, $user, $request->file('file'));
        AuditService::record('attachment.created', $attachment);

        return back()->with('status', __('attachments.created'));
    }

    public function show(Attachment $attachment): Response
    {
        Gate::authorize('view', $attachment);

        $inline = str_starts_with($attachment->mime, 'image/');

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_name,
            ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'],
            $inline ? 'inline' : 'attachment',
        );
    }

    public function destroy(Attachment $attachment): RedirectResponse
    {
        Gate::authorize('delete', $attachment);
        AuditService::record('attachment.deleted', $attachment);
        $attachment->delete();

        return back()->with('status', __('attachments.deleted'));
    }
}
