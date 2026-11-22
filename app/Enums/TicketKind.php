<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketKind: string
{
    case Request = 'request';
    case Dev = 'dev';
}
