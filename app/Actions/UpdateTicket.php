<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\TicketWorkflow;
use App\Enums\Priority;
use App\Enums\TicketStatus;
use App\Events\TicketAssigned;
use App\Events\TicketMoved;
use App\Events\TicketStatusChanged;
use App\Events\TicketUpdated;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SlaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateTicket
{
    /** @param array<string, mixed> $data */
    public function __invoke(Ticket $ticket, User $actor, array $data): Ticket
    {
        $oldStatus = $ticket->status;
        $oldPriority = $ticket->priority;
        $oldAssignee = $ticket->assignee_id;
        $update = array_intersect_key($data, array_flip([
            'title', 'description', 'priority', 'severity', 'environment', 'assignee_id', 'category_id', 'blocked',
        ]));

        if (isset($data['status'])) {
            $to = $data['status'] instanceof TicketStatus ? $data['status'] : TicketStatus::from((string) $data['status']);
            if (! TicketWorkflow::canTransition($ticket->status, $to)) {
                throw ValidationException::withMessages(['status' => __('tickets.invalid_transition')]);
            }
            $update['status'] = $to;

            if ($to->isFinal() && $ticket->resolved_at === null) {
                $update['resolved_at'] = now();
                $update['closed_at'] = $to->group() === TicketStatus::Done ? now() : null;
            }
            if (! $to->isFinal()) {
                $update['resolved_at'] = null;
                $update['closed_at'] = null;
            }
        }

        if ($update !== []) {
            $ticket->update($update);
        }

        $sla = app(SlaService::class);
        if (isset($data['status'])) {
            $to = $data['status'] instanceof TicketStatus ? $data['status'] : TicketStatus::from((string) $data['status']);
            if ($to === TicketStatus::WaitingRequester) {
                $sla->pause($ticket);
            } elseif ($oldStatus === TicketStatus::WaitingRequester) {
                $sla->resume($ticket);
            }
        }
        if (isset($data['priority'])) {
            $priority = $data['priority'] instanceof Priority
                ? $data['priority']
                : Priority::from((string) $data['priority']);
            if ($priority !== $oldPriority) {
                $sla->apply($ticket, $priority);
            }
        }

        $newStatus = $ticket->status;
        $newAssignee = $ticket->assignee_id;
        DB::afterCommit(static function () use ($ticket, $actor, $oldStatus, $newStatus, $oldAssignee, $newAssignee, $update): void {
            if ($oldStatus !== $newStatus) {
                TicketStatusChanged::dispatch($ticket->id, $actor->id, $oldStatus->value, $newStatus->value);
                TicketMoved::dispatch($ticket->id, $ticket->project_id, $newStatus->value, (string) $ticket->rank);
            }
            if ($oldAssignee !== $newAssignee && $newAssignee !== null) {
                TicketAssigned::dispatch($ticket->id, $actor->id, $newAssignee);
            }
            if (array_diff_key($update, array_flip(['status'])) !== []) {
                TicketUpdated::dispatch($ticket->id, $actor->id, array_keys($update));
            }
        });

        return $ticket->refresh()->load('project', 'reporter', 'requester', 'assignee', 'category');
    }
}
