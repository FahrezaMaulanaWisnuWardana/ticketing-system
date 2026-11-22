<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\Rank;
use App\Domain\TicketNumbering;
use App\Domain\TicketWorkflow;
use App\Enums\TicketKind;
use App\Models\Ticket;
use App\Models\TicketLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateDevTicketFromRequest
{
    public function __invoke(Ticket $request, User $actor): Ticket
    {
        if ($request->kind->value !== 'request') {
            throw new \InvalidArgumentException(__('dev.only_requests'));
        }

        return DB::transaction(static function () use ($request, $actor): Ticket {
            $number = TicketNumbering::next($request->project_id);
            $dev = Ticket::create([
                'project_id' => $request->project_id,
                'number' => $number,
                'kind' => TicketKind::Dev,
                'type' => 'task',
                'category_id' => $request->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'status' => TicketWorkflow::initialStatus(TicketKind::Dev),
                'priority' => $request->priority,
                'reporter_id' => $actor->id,
                'requester_id' => $request->requester_id,
                'assignee_id' => $request->assignee_id,
                'parent_id' => $request->id,
                'rank' => Rank::normalize(str_pad((string) $number, 10, '0', STR_PAD_LEFT)),
            ]);
            TicketLink::create(['from_id' => $request->id, 'to_id' => $dev->id, 'type' => 'spawned']);
            TicketLink::create(['from_id' => $dev->id, 'to_id' => $request->id, 'type' => 'caused_by']);

            return $dev->load('project');
        });
    }
}
