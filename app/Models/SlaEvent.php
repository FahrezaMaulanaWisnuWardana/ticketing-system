<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaEvent extends Model
{
    /** @var list<string> */
    protected $fillable = ['ticket_id', 'kind', 'channel', 'fired_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['fired_at' => 'datetime'];
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
