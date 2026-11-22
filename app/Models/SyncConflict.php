<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncConflict extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['connection_id', 'ticket_id', 'field', 'app_value', 'trello_value', 'resolution', 'resolved_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    /** @return BelongsTo<IntegrationConnection, $this> */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(IntegrationConnection::class);
    }
}
