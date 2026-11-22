<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\AddComment;
use App\Enums\TicketKind;
use App\Enums\TicketStatus;
use App\Events\TicketStatusChanged;
use App\Models\Ticket;
use App\Models\TicketLink;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyRequestWhenDevDone implements ShouldQueue
{
    public function handle(TicketStatusChanged $event): void
    {
        if ($event->newStatus !== TicketStatus::Done->value) {
            return;
        }
        $dev = Ticket::withoutGlobalScopes()->find($event->ticketId);
        if ($dev === null || $dev->kind !== TicketKind::Dev) {
            return;
        }

        $link = TicketLink::where('to_id', $dev->id)->where('type', 'spawned')->first();
        if ($link === null) {
            return;
        }
        $request = Ticket::withoutGlobalScopes()->find($link->from_id);
        if ($request === null) {
            return;
        }

        $dev->loadMissing('project');
        $actor = User::find($event->actorId) ?? User::find($request->assignee_id);
        if ($actor === null) {
            return;
        }
        $note = __('pm.dev_completed_note', ['key' => $dev->key]);
        if (! $request->comments()->where('body', $note)->exists()) {
            app(AddComment::class)($request, $actor, $note, true);
        }
    }
}
