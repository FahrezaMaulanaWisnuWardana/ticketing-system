<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AttachmentService;
use Illuminate\Http\UploadedFile;

final class AddAttachment
{
    public function __construct(private readonly AttachmentService $files) {}

    public function __invoke(Ticket $ticket, User $user, UploadedFile $file, ?Comment $comment = null): Attachment
    {
        $stored = $this->files->store($file);

        return Attachment::create([
            'ticket_id' => $ticket->id,
            'comment_id' => $comment?->id,
            'user_id' => $user->id,
            ...$stored,
        ]);
    }
}
