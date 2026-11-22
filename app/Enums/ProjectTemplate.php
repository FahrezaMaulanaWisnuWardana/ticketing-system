<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectTemplate: string
{
    case Dev = 'dev';
    case Helpdesk = 'helpdesk';
    case Blank = 'blank';
}
