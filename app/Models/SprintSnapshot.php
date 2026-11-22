<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SprintSnapshot extends Model
{
    /** @var list<string> */
    protected $fillable = ['sprint_id', 'date', 'remaining_points', 'remaining_count'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['date' => 'date', 'remaining_points' => 'integer', 'remaining_count' => 'integer'];
    }

    /** @return BelongsTo<Sprint, $this> */
    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }
}
