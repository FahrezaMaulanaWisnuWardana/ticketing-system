<?php

declare(strict_types=1);

namespace App\Support\Queries;

use App\Enums\TicketKind;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dedicated query object for "Permintaan saya". The controller only passes
 * validated filter values; nothing from the query string reaches Eloquent
 * without going through ListMyRequestsRequest first.
 */
final class MyRequestsQuery
{
    /** @param array<string, mixed> $filters */
    public function __construct(
        private readonly User $user,
        private readonly array $filters,
    ) {}

    /** @return LengthAwarePaginator<int, Ticket> */
    public function run(): LengthAwarePaginator
    {
        $status = (string) $this->filters['status'];
        $sort = (string) $this->filters['sort'];
        $direction = $this->filters['dir'] === 'asc' ? 'asc' : 'desc';
        $needle = $this->filters['q'] === null ? null : (string) $this->filters['q'];
        $type = $this->filters['type'] === null ? null : (string) $this->filters['type'];
        $userId = $this->user->id;

        $query = Ticket::query()
            ->where('tickets.kind', TicketKind::Request)
            ->with([
                'project:id,key,name',
                'reporter:id,name',
                'requester:id,name',
                'assignee:id,name',
                'requestType:id,name,kind',
            ])
            ->when($this->filters['creator'] === 'mine', static fn (Builder $q) => $q
                ->where('tickets.reporter_id', $userId))
            ->when($status === 'open', static fn (Builder $q) => $q
                ->whereNotIn('tickets.status', TicketStatus::finalValues()))
            ->when($status === 'done', static fn (Builder $q) => $q
                ->whereIn('tickets.status', TicketStatus::finalValues()))
            ->when($type !== null, static fn (Builder $q) => $q
                ->where('tickets.type', $type))
            ->when($needle !== null, static function (Builder $q) use ($needle): void {
                $like = '%'.addcslashes((string) $needle, '%_\\').'%';
                $q->where(static function (Builder $inner) use ($needle, $like): void {
                    $inner->whereRaw("tickets.search @@ plainto_tsquery('simple', ?)", [$needle])
                        ->orWhereRaw('tickets.title ILIKE ?', [$like])
                        ->orWhereRaw("tickets.project_id::text || '-' || tickets.number::text ILIKE ?", [$like]);
                });
            });

        $this->applySort($query, $sort, $direction, $status, $needle);

        return $query->paginate(20)->withQueryString();
    }

    /**
     * @param  Builder<Ticket>  $query
     * @param  'asc'|'desc'  $direction
     */
    private function applySort(Builder $query, string $sort, string $direction, string $status, ?string $term): void
    {
        // Default view: tickets that are waiting for the viewer's reply float
        // to the top. This is an ordering rule, not a new status or filter.
        $defaultView = $status === 'open'
            && ! (bool) ($this->filters['explicit_sort'] ?? false)
            && $term === null
            && $this->filters['type'] === null;

        if ($defaultView) {
            $query->orderByRaw("CASE WHEN tickets.status = 'waiting_requester' THEN 0 ELSE 1 END");
        }

        $column = match ($sort) {
            'summary' => 'tickets.title',
            'created_at' => 'tickets.created_at',
            default => null,
        };

        if ($column !== null) {
            $query->orderBy($column, $direction);
        } else {
            $query->orderByRaw(self::statusOrder($direction));
        }

        $query->orderBy('tickets.id', $direction);
    }

    /** @return literal-string */
    private static function statusOrder(string $direction): string
    {
        $case = "CASE WHEN tickets.status IN ('done','closed') THEN 3 "
            ."WHEN tickets.status = 'waiting_requester' THEN 2 "
            ."WHEN tickets.status IN ('backlog','todo','in_progress','in_review') THEN 1 ELSE 0 END";

        return $case.' '.($direction === 'asc' ? 'ASC' : 'DESC');
    }
}
