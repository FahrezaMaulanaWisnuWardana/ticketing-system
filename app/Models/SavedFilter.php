<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedFilter extends Model
{
    /** @var list<string> */
    protected $fillable = ['user_id', 'name', 'filters'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['filters' => 'array'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
