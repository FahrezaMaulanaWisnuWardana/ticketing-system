<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\ProjectMetricsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/** @property Carbon|null $due_date */
class Milestone extends Model
{
    /** @var list<string> */
    protected $fillable = ['project_id', 'name', 'due_date', 'state'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['due_date' => 'date'];
    }

    protected static function booted(): void
    {
        static::saved(static function (self $milestone): void {
            ProjectMetricsService::forget($milestone->project_id);
        });
        static::deleted(static function (self $milestone): void {
            ProjectMetricsService::forget($milestone->project_id);
        });
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
