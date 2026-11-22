<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\Rank;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ChangeRank
{
    /** @param array<int, int> $orderedIds */
    public function __invoke(User $actor, array $orderedIds, ?int $sprintId = null): void
    {
        if (count($orderedIds) === 0 || count($orderedIds) > 500) {
            throw ValidationException::withMessages(['order' => __('dev.invalid_order')]);
        }

        DB::transaction(static function () use ($actor, $orderedIds, $sprintId): void {
            $tickets = Ticket::whereIn('id', $orderedIds)->get()->keyBy('id');
            if ($tickets->count() !== count($orderedIds)) {
                throw ValidationException::withMessages(['order' => __('dev.invalid_order')]);
            }
            foreach ($orderedIds as $id) {
                $ticket = $tickets->get($id);
                if (! $ticket instanceof Ticket) {
                    continue;
                }
                Gate::forUser($actor)->authorize('update', $ticket);
            }

            foreach (array_values($orderedIds) as $index => $id) {
                Ticket::withoutGlobalScopes()->where('id', $id)->update([
                    'rank' => Rank::normalize((string) (($index + 1) * 10)),
                    'sprint_id' => $sprintId,
                ]);
            }
        });
    }
}
