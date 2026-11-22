<?php

declare(strict_types=1);

namespace App\Enums;

enum TrelloConnectionStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Error = 'error';
    case Disabled = 'disabled';
}
