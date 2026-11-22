<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRun extends Model
{
    /** @var list<string> */
    protected $fillable = ['connection_id', 'started_by', 'status', 'counts', 'errors', 'started_at', 'finished_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['counts' => 'array', 'errors' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    /** @return BelongsTo<IntegrationConnection, $this> */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(IntegrationConnection::class);
    }
}
