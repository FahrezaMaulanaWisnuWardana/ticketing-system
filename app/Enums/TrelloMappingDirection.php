<?php

declare(strict_types=1);

namespace App\Enums;

enum TrelloMappingDirection: string
{
    case Import = 'import';
    case Mirror = 'mirror';
    case Both = 'both';
    case Ignored = 'both_ignored';
}
