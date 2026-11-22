<?php

declare(strict_types=1);

namespace App\Support\Queries;

use App\Enums\Priority;
use App\Enums\TicketKind;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/** Queue counts and listing for the agent view. */
final class AgentQueueQuery
{
    public const QUEUES = ['all_open', 'mine', 'unassigned', 'new', 'recently_resolved'];

    /** @param array<string, mixed> $filters */
    public function __construct(
        private readonly User $user,
        private readonly array $filters,
    ) {}

    /** @var array<int, array<string, int>> */
    private static array $countCache = [];

    /**
     * Cached per request so the sidebar does not add five queries per page.
     *
     * @return array<string, int>
     */
    public static function countsFor(User $user): array
    {
        return self::$countCache[$user->id] ??= (new self($user, ['queue' => 'all_open', 'q' => null]))->counts();
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        $counts = [];
        foreach (self::QUEUES as $queue) {
            $counts[$queue] = $this->scope(Ticket::query(), $queue)->count();
        }

        return $counts;
    }

    /** @return LengthAwarePaginator<int, Ticket> */
    public function run(): LengthAwarePaginator
    {
        $queue = (string) $this->filters['queue'];
        $needle = $this->filters['q'] === null ? null : (string) $this->filters['q'];

        $query = $this->scope(Ticket::query(), $queue)
            ->with([
                'project:id,key,name',
                'reporter:id,name',
                'requester:id,name',
                'assignee:id,name',
                'requestType:id,name,kind',
            ])
            ->when($needle !== null, static function (Builder $q) use ($needle): void {
                $like = '%'.addcslashes((string) $needle, '%_\\').'%';
                $q->where(static function (Builder $inner) use ($needle, $like): void {
                    $inner->whereRaw("tickets.search @@ plainto_tsquery('simple', ?)", [$needle])
                        ->orWhereRaw('tickets.title ILIKE ?', [$like])
                        ->orWhereRaw("tickets.project_id::text || '-' || tickets.number::text ILIKE ?", [$like]);
                });
            });

        $this->applySort($query, $queue);

        return $query->paginate(20)->withQueryString();
    }

    /** @param Builder<Ticket> $query */
    private function applySort(Builder $query, string $queue): void
    {
        if (! (bool) ($this->filters['explicit_sort'] ?? false)) {
            if ($queue === 'recently_resolved') {
                $query->orderByDesc('tickets.updated_at');
            } else {
                $query->orderByRaw("CASE tickets.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
                    ->orderBy('tickets.created_at');
            }

            return;
        }

        $sort = (string) $this->filters['sort'];
        $direction = $this->filters['dir'] === 'asc' ? 'asc' : 'desc';
        $column = match ($sort) {
            'summary' => 'tickets.title',
            'created_at', 'updated_at' => 'tickets.'.$sort,
            default => null,
        };

        if ($column !== null) {
            $query->orderBy($column, $direction);
        } elseif ($sort === 'priority') {
            $query->orderByRaw($direction === 'asc' ? Priority::SORT_ASC : Priority::SORT_DESC);
        } else {
            $case = "CASE WHEN tickets.status IN ('done','closed') THEN 3 "
                ."WHEN tickets.status = 'waiting_requester' THEN 2 "
                ."WHEN tickets.status IN ('backlog','todo','in_progress','in_review') THEN 1 ELSE 0 END";
            $query->orderByRaw($case.' '.($direction === 'asc' ? 'ASC' : 'DESC'));
        }

        $query->orderBy('tickets.id', $direction);
    }

    /**
     * Shared queue scoping so the badge counts and the listing agree.
     *
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    private function scope(Builder $query, string $queue): Builder
    {
        $query->where('tickets.kind', TicketKind::Request);

        return match ($queue) {
            'mine' => $query->where('tickets.assignee_id', $this->user->id)
                ->whereNotIn('tickets.status', TicketStatus::finalValues()),
            'unassigned' => $query->whereNull('tickets.assignee_id')
                ->whereNotIn('tickets.status', TicketStatus::finalValues()),
            'new' => $query->where('tickets.status', TicketStatus::New),
            'recently_resolved' => $query->whereIn('tickets.status', TicketStatus::finalValues())
                ->where('tickets.updated_at', '>=', now()->subDays(7)),
            default => $query->whereNotIn('tickets.status', TicketStatus::finalValues()),
        };
    }
}
