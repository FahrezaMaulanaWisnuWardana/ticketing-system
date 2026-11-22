<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationLog extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['connection_id', 'level', 'event', 'payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    /** @return BelongsTo<IntegrationConnection, $this> */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(IntegrationConnection::class);
    }
}
