<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectRole: string
{
    case Lead = 'lead';
    case Member = 'member';

    public function labelKey(): string
    {
        return 'project_roles.'.$this->value;
    }
}
