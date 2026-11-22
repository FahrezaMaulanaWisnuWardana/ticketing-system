<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class TicketSearch
{
    /**
     * Exact key matches first, then full-text (titles weighted A, descriptions B, comments C), then fuzzy title.
     *
     * @return LengthAwarePaginator<int, Ticket>
     */
    public static function run(string $term): LengthAwarePaginator
    {
        $term = trim($term);
        if ($term === '') {
            return Ticket::query()->orderByDesc('updated_at')->paginate(25);
        }

        $key = strtoupper($term);
        $matches = [];
        $isExactKey = preg_match('/^([A-Z]{2,10})-(\d+)$/', $key, $matches) === 1;

        return Ticket::query()
            ->select('tickets.*')
            ->with('project:id,key,name')
            ->join('projects', 'projects.id', '=', 'tickets.project_id')
            ->where(static function (Builder $query) use ($term, $key, $isExactKey, $matches): void {
                if ($isExactKey) {
                    $query->where('projects.key', $matches[1])
                        ->where('tickets.number', (int) $matches[2]);

                    return;
                }

                $query->whereRaw("tickets.search @@ plainto_tsquery('simple', ?)", [$term])
                    ->orWhereRaw('tickets.title ILIKE ?', ['%'.$term.'%'])
                    ->orWhereRaw('tickets.title % ?', [$term])
                    ->orWhereRaw("projects.key || '-' || tickets.number = ?", [$key]);
            })
            ->orderByRaw("case when projects.key || '-' || tickets.number = ? then 0 else 1 end", [$key])
            ->orderByRaw('similarity(tickets.title, ?) desc', [$term])
            ->orderByDesc('tickets.updated_at')
            ->paginate(25);
    }
}
