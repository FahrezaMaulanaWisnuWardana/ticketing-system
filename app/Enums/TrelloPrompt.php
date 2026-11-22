<?php

declare(strict_types=1);

namespace App\Enums;

enum TrelloPrompt: string
{
    case Unasked = 'unasked';
    case No = 'no';
    case Later = 'later';
    case Connected = 'connected';
}
