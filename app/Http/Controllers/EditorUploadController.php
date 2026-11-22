<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

/**
 * Handles files dropped, pasted or picked inside the rich-text editor.
 *
 * Files are validated and stored through the same private pipeline as regular
 * attachments, but stay "pending" (ticket_id = null) until the surrounding form
 * is submitted and claims them (see AttachPending). Anything left unclaimed is
 * removed by PruneOldDataJob.
 */
final class EditorUploadController
{
    public function store(Request $request, AttachmentService $files): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }
        Gate::authorize('create', Ticket::class);

        $validated = $request->validate([
            'file' => [
                'required', 'file', 'max:'.(int) config('ticketing.uploads.max_kb'),
                static function (string $attribute, mixed $value, callable $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }
                    if (in_array(strtolower((string) $value->getClientOriginalExtension()), (array) config('ticketing.uploads.blocked_extensions'), true)) {
                        $fail(__('attachments.blocked_extension'));
                    }
                },
            ],
        ]);

        $stored = $files->store($validated['file']);
        $attachment = Attachment::create([
            'ticket_id' => null,
            'comment_id' => null,
            'user_id' => $user->id,
            ...$stored,
        ]);

        return response()->json([
            'id' => $attachment->id,
            'url' => route('attachments.show', $attachment),
            'name' => $attachment->original_name,
            'mime' => $attachment->mime,
            'size' => $attachment->size,
        ], 201);
    }
}
