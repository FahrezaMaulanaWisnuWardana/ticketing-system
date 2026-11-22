<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/** @property Carbon|null $due_date
 * @property Carbon|null $start_date */
class Epic extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = ['project_id', 'key', 'name', 'description', 'start_date', 'due_date', 'status', 'color'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['start_date' => 'date', 'due_date' => 'date'];
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
