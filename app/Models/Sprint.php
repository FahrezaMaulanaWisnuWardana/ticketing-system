<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SprintFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $state
 */
class Sprint extends Model
{
    /** @use HasFactory<SprintFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['project_id', 'name', 'goal', 'start_date', 'end_date', 'state', 'capacity'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'capacity' => 'integer'];
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

    /** @return HasMany<SprintSnapshot, $this> */
    public function snapshots(): HasMany
    {
        return $this->hasMany(SprintSnapshot::class);
    }

    public function remainingPoints(): int
    {
        return (int) $this->tickets()->whereNotIn('status', ['done', 'closed'])->sum('estimate');
    }
}
