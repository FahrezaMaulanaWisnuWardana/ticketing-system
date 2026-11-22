<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketStatus;
use App\Enums\TrelloMappingDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusMapping extends Model
{
    /** @var list<string> */
    protected $fillable = ['connection_id', 'trello_list_id', 'trello_list_name', 'ticket_status', 'direction'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['ticket_status' => TicketStatus::class, 'direction' => TrelloMappingDirection::class];
    }

    /** @return BelongsTo<IntegrationConnection, $this> */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(IntegrationConnection::class);
    }
}
