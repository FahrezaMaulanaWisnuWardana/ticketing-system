<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\TicketStatus;
use App\Models\Sprint;
use App\Models\SprintSnapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class TakeSprintSnapshotsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Sprint::where('state', 'active')->chunkById(50, static function ($sprints): void {
            foreach ($sprints as $sprint) {
                $remainingPoints = (int) $sprint->tickets()->whereNotIn('status', [TicketStatus::Done, TicketStatus::Closed])->sum('estimate');
                $remainingCount = (int) $sprint->tickets()->whereNotIn('status', [TicketStatus::Done, TicketStatus::Closed])->count();
                SprintSnapshot::updateOrCreate(
                    ['sprint_id' => $sprint->id, 'date' => now()->toDateString()],
                    ['remaining_points' => $remainingPoints, 'remaining_count' => $remainingCount],
                );
            }
        });
    }
}
