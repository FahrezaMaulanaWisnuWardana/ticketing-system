<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketKind;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property TicketKind $kind
 * @property bool $is_active
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'project_id', 'name', 'kind', 'form_schema', 'default_assignee_id', 'is_active', 'sort',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => TicketKind::class,
            'form_schema' => 'array',
            'is_active' => 'bool',
            'sort' => 'integer',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
