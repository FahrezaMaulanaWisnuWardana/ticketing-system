<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\ProjectTemplate;
use App\Enums\TrelloPrompt;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property ProjectStatus $status
 * @property ProjectTemplate $template
 * @property TrelloPrompt $trello_prompt
 * @property Carbon|null $start_date
 * @property Carbon|null $due_date
 */
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'key', 'name', 'description', 'owner_id', 'status', 'start_date', 'due_date',
        'template', 'ticket_seq', 'archived_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'template' => ProjectTemplate::class,
            'trello_prompt' => TrelloPrompt::class,
            'trello_prompt_at' => 'datetime',
            'start_date' => 'date',
            'due_date' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /** @param Builder<Project> $query */
    public function scopeVisible(Builder $query): void
    {
        $user = auth('web')->user();
        if (! $user instanceof User || $user->isAdmin()) {
            return;
        }

        $query->where(static function (Builder $inner) use ($user): void {
            $inner->where('owner_id', $user->id)
                ->orWhereHas('members', static fn (Builder $memberQuery) => $memberQuery->where('users.id', $user->id));
        });
    }

    protected static function booted(): void
    {
        static::saving(function (self $project): void {
            $project->key = strtoupper((string) $project->key);
        });
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsToMany<User, $this, Membership, 'pivot'> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->using(Membership::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return HasMany<Category, $this> */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** @return HasMany<Milestone, $this> */
    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    /** @return HasMany<Epic, $this> */
    public function epics(): HasMany
    {
        return $this->hasMany(Epic::class);
    }

    /** @return HasMany<Sprint, $this> */
    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class);
    }

    /** @return HasMany<Label, $this> */
    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    /** @return HasMany<CannedReply, $this> */
    public function cannedReplies(): HasMany
    {
        return $this->hasMany(CannedReply::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function requests(): HasMany
    {
        return $this->hasMany(Ticket::class)->where('kind', 'request');
    }
}
