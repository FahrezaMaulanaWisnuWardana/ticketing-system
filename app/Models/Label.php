<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Label extends Model
{
    /** @var list<string> */
    protected $fillable = ['project_id', 'name', 'color'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsToMany<Ticket, $this> */
    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_label');
    }
}
