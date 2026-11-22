<?php

declare(strict_types=1);

namespace App\Enums;

enum TrelloExternalState: string
{
    case Ok = 'ok';
    case ChangedExternally = 'changed_externally';
    case Deleted = 'deleted';
    case Archived = 'archived';
}
