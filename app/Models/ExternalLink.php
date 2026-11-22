<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TrelloExternalState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property TrelloExternalState $external_state
 * @property array<string, string> $field_hashes
 */
class ExternalLink extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'ticket_id', 'provider', 'external_id', 'external_url', 'last_synced_hash',
        'last_synced_at', 'external_state', 'field_hashes', 'last_inbound_at', 'last_outbound_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'external_state' => TrelloExternalState::class,
            'field_hashes' => 'array',
            'last_synced_at' => 'datetime',
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
