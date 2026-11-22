<?php

declare(strict_types=1);

namespace App\Enums;

enum TrelloMode: string
{
    case ImportOnly = 'import_only';
    case Mirror = 'mirror';
    case TwoWay = 'two_way';
}
