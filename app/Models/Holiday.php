<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['date', 'name'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
