<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TicketStatus;
use App\Models\Sprint;
use App\Models\SprintSnapshot;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

final class CompleteSprint
{
    public function __invoke(Sprint $sprint): Sprint
    {
        return DB::transaction(static function () use ($sprint): Sprint {
            $next = Sprint::where('project_id', $sprint->project_id)
                ->where('state', 'planned')
                ->where('id', '!=', $sprint->id)
                ->orderBy('start_date')
                ->first();

            $remaining = (int) $sprint->tickets()->whereNotIn('status', [TicketStatus::Done, TicketStatus::Closed])->sum('estimate');
            $count = (int) $sprint->tickets()->whereNotIn('status', [TicketStatus::Done, TicketStatus::Closed])->count();
            SprintSnapshot::updateOrCreate(
                ['sprint_id' => $sprint->id, 'date' => now()->toDateString()],
                ['remaining_points' => $remaining, 'remaining_count' => $count],
            );

            Ticket::withoutGlobalScopes()
                ->where('sprint_id', $sprint->id)
                ->whereNotIn('status', [TicketStatus::Done, TicketStatus::Closed])
                ->update(['sprint_id' => $next?->id]);

            $sprint->update(['state' => 'closed']);

            return $sprint->refresh();
        });
    }
}
