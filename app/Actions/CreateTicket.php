<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\Rank;
use App\Domain\TicketNumbering;
use App\Domain\TicketWorkflow;
use App\Enums\TicketKind;
use App\Events\TicketCreated;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SlaService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class CreateTicket
{
    /** @param array<string, mixed> $data */
    public function __invoke(User $actor, Project $project, array $data): Ticket
    {
        $kind = TicketKind::from((string) $data['kind']);
        if ($kind === TicketKind::Dev && ! $actor->isAgent()) {
            throw new AuthorizationException(__('tickets.cannot_create_dev'));
        }

        return DB::transaction(static function () use ($actor, $project, $data, $kind): Ticket {
            $number = TicketNumbering::next($project->id);
            $status = TicketWorkflow::initialStatus($kind);

            $ticket = Ticket::create([
                'project_id' => $project->id,
                'number' => $number,
                'kind' => $kind,
                'type' => $data['type'] ?? 'task',
                'category_id' => $data['category_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => $status,
                'priority' => $data['priority'] ?? 'normal',
                'severity' => $data['severity'] ?? null,
                'environment' => $data['environment'] ?? null,
                'reporter_id' => $actor->id,
                'requester_id' => $data['requester_id'] ?? $actor->id,
                'assignee_id' => $data['assignee_id'] ?? $project->categories()
                    ->whereKey($data['category_id'] ?? 0)->value('default_assignee_id'),
                'rank' => Rank::normalize(str_pad((string) $number, 10, '0', STR_PAD_LEFT)),
            ]);

            app(SlaService::class)->apply($ticket);
            $ticket->load('project', 'reporter', 'requester', 'assignee');
            DB::afterCommit(static function () use ($ticket, $actor): void {
                TicketCreated::dispatch($ticket->id, $actor->id);
            });

            return $ticket;
        });
    }
}
