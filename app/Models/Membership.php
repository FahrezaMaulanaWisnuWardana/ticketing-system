<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Membership extends Pivot
{
    protected $table = 'project_members';

    protected $primaryKey = 'id';

    public $incrementing = true;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'role' => ProjectRole::class,
        ];
    }
}
