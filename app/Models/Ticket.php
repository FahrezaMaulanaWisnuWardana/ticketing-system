<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Priority;
use App\Enums\StatusCategory;
use App\Enums\TicketKind;
use App\Enums\TicketStatus;
use App\Support\Scopes\VisibleToUserScope;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property TicketKind $kind
 * @property TicketStatus $status
 * @property Priority $priority
 * @property Project $project
 * @property Carbon $created_at
 * @property Carbon|null $due_date
 * @property User|null $assignee
 * @property Carbon|null $first_response_at
 * @property Carbon|null $resolved_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $sla_response_due_at
 * @property Carbon|null $sla_resolve_due_at
 * @property Carbon|null $sla_paused_at
 * @property int $sla_paused_seconds
 * @property-read string $key
 */
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'project_id', 'number', 'kind', 'type', 'category_id', 'title', 'description', 'estimate', 'start_date', 'due_date', 'sprint_id', 'parent_id', 'epic_id', 'milestone_id',
        'status', 'priority', 'severity', 'environment', 'reporter_id', 'requester_id',
        'assignee_id', 'rank', 'blocked', 'first_response_at', 'resolved_at', 'closed_at',
        'sla_response_due_at', 'sla_resolve_due_at', 'sla_paused_at', 'sla_paused_seconds',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => TicketKind::class,
            'status' => TicketStatus::class,
            'priority' => Priority::class,
            'blocked' => 'bool',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'sla_response_due_at' => 'datetime',
            'sla_resolve_due_at' => 'datetime',
            'sla_paused_at' => 'datetime',
            'sla_paused_seconds' => 'integer',
            'number' => 'integer',
            'estimate' => 'integer',
            'start_date' => 'date',
            'due_date' => 'date',
            'sprint_id' => 'integer',
            'parent_id' => 'integer',
            'epic_id' => 'integer',
            'milestone_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new VisibleToUserScope);
    }

    public function getRouteKey(): string
    {
        return $this->key;
    }

    public function getKeyAttribute(): string
    {
        // The key depends on the project's key. Avoid triggering a lazy-loading
        // violation (strict mode is enabled) when the relation was not eager loaded.
        $projectKey = $this->relationLoaded('project')
            ? $this->getRelation('project')?->key
            : $this->project()->value('key');

        return $projectKey.'-'.$this->number;
    }

    /** @param mixed $value */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        if (preg_match('/^([A-Z]{2,10})-(\d+)$/', strtoupper((string) $value), $matches) !== 1) {
            return null;
        }

        return static::query()
            ->with('project')
            ->whereHas('project', static fn ($query) => $query->where('key', $matches[1]))
            ->where('number', (int) $matches[2])
            ->first();
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Request type is the user-facing alias of the existing category relation.
     * The portal catalogue (config/request-types.php) is keyed by tickets.type;
     * this relation covers tickets that still carry a legacy category.
     *
     * @return BelongsTo<Category, $this>
     */
    public function requestType(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * A ticket that is waiting on the reporter/requester needs their reply.
     * This is an indicator next to the title, never a separate status.
     */
    public function needsReplyFor(User $user): bool
    {
        return $this->status->category() === StatusCategory::Waiting
            && ($this->reporter_id === $user->id || $this->requester_id === $user->id);
    }

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** @return HasMany<Activity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /** @return HasMany<Attachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /** @return HasMany<Watcher, $this> */
    public function watchers(): HasMany
    {
        return $this->hasMany(Watcher::class);
    }

    /** @return BelongsTo<Sprint, $this> */
    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    /** @return BelongsTo<Ticket, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'parent_id');
    }

    /** @return BelongsToMany<Label, $this, Pivot, string> */
    /** @return BelongsTo<Epic, $this> */
    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class);
    }

    /** @return BelongsTo<Milestone, $this> */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    /** @return BelongsToMany<Label, $this, Pivot, 'pivot'> */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'ticket_label');
    }
}
