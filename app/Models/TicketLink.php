<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketLink extends Model
{
    /** @var list<string> */
    protected $fillable = ['from_id', 'to_id', 'type'];

    /** @return BelongsTo<Ticket, $this> */
    public function from(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'from_id');
    }

    /** @return BelongsTo<Ticket, $this> */
    public function to(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'to_id');
    }
}
