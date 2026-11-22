<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitLink extends Model
{
    /** @var list<string> */
    protected $fillable = ['ticket_id', 'provider', 'ref', 'url', 'state', 'external_id'];

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
