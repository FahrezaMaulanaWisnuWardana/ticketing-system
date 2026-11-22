<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CannedReplyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $project_id
 */
class CannedReply extends Model
{
    /** @use HasFactory<CannedReplyFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['project_id', 'title', 'body'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
