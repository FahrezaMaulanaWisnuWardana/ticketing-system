<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Requester = 'requester';
    case Agent = 'agent';
    case Admin = 'admin';

    public function labelKey(): string
    {
        return 'roles.'.$this->value;
    }
}
