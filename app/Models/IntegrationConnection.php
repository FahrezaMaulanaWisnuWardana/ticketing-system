<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TrelloConnectionStatus;
use App\Enums\TrelloMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property TrelloMode $mode
 * @property TrelloConnectionStatus $status
 */
class IntegrationConnection extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'project_id', 'created_by', 'provider', 'trello_board_id', 'trello_board_name',
        'api_key_encrypted', 'token_encrypted', 'mode', 'status', 'last_synced_at', 'last_error', 'webhook_id', 'mirror_helpdesk',
    ];

    /** @var list<string> */
    protected $hidden = ['api_key_encrypted', 'token_encrypted'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'mode' => TrelloMode::class,
            'status' => TrelloConnectionStatus::class,
            'api_key_encrypted' => 'encrypted',
            'token_encrypted' => 'encrypted',
            'last_synced_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<StatusMapping, $this> */
    public function statusMappings(): HasMany
    {
        return $this->hasMany(StatusMapping::class, 'connection_id');
    }

    /** @return HasMany<MemberMapping, $this> */
    public function memberMappings(): HasMany
    {
        return $this->hasMany(MemberMapping::class, 'connection_id');
    }

    /** @return HasMany<ImportRun, $this> */
    public function importRuns(): HasMany
    {
        return $this->hasMany(ImportRun::class, 'connection_id');
    }

    /** @return HasMany<SyncConflict, $this> */
    public function conflicts(): HasMany
    {
        return $this->hasMany(SyncConflict::class, 'connection_id');
    }

    public function lastFour(): string
    {
        return substr((string) $this->token_encrypted, -4);
    }
}
