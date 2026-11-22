<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberMapping extends Model
{
    /** @var list<string> */
    protected $fillable = ['connection_id', 'trello_member_id', 'trello_username', 'user_id'];

    /** @return BelongsTo<IntegrationConnection, $this> */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(IntegrationConnection::class);
    }
}
