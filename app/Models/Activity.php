<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class Activity extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'ticket_id', 'user_id', 'event', 'field', 'old_value', 'new_value', 'source', 'metadata',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'metadata' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(static function (): void {
            throw new LogicException('Activities are append-only.');
        });
        static::deleting(static function (): void {
            throw new LogicException('Activities are append-only.');
        });
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
