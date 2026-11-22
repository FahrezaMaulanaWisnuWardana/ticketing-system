<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;

final class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($ticket->requester_id === $user->id || $ticket->reporter_id === $user->id) {
            return true;
        }

        return $this->canManage($user, $ticket);
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function createDev(User $user): bool
    {
        return $user->isAgent();
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isRequester()) {
            return $ticket->kind->value === 'request'
                && ($ticket->requester_id === $user->id || $ticket->reporter_id === $user->id)
                && $ticket->status->isOpen();
        }

        return $this->canManage($user, $ticket);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        $project = Project::find($ticket->project_id);

        return $user->isAdmin() || ($project !== null && $user->isLeadOf($project));
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $this->canManage($user, $ticket);
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $this->canManage($user, $ticket);
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && $ticket->status->isOpen();
    }

    public function viewInternalNotes(User $user, Ticket $ticket): bool
    {
        return $this->canManage($user, $ticket);
    }

    public function attach(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function deleteAttachment(User $user, Ticket $ticket): bool
    {
        return $this->canManage($user, $ticket);
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        if ($ticket->kind->value !== 'request'
            || ! $ticket->status->isFinal()
            || ($ticket->requester_id !== $user->id && $ticket->reporter_id !== $user->id)
        ) {
            return false;
        }

        $resolvedAt = $ticket->closed_at ?? $ticket->resolved_at;

        return $resolvedAt !== null && $resolvedAt->gte(now()->subDays((int) config('ticketing.reopen_days')));
    }

    public function watch(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    private function canManage(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if (! $user->isAgent()) {
            return false;
        }
        if ($ticket->assignee_id === $user->id) {
            return true;
        }

        $project = Project::find($ticket->project_id);

        return $project !== null && $user->isMemberOf($project);
    }
}
