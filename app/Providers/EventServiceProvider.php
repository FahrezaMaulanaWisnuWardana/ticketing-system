<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\CommentAdded;
use App\Events\TicketAssigned;
use App\Events\TicketCreated;
use App\Events\TicketReopened;
use App\Events\TicketStatusChanged;
use App\Events\TicketUpdated;
use App\Listeners\MirrorTrelloChanges;
use App\Listeners\MirrorTrelloComment;
use App\Listeners\NotifyAgentsOnNewTicket;
use App\Listeners\NotifyOnCommentAdded;
use App\Listeners\NotifyOnStatusChanged;
use App\Listeners\NotifyOnTicketAssigned;
use App\Listeners\NotifyOnTicketReopened;
use App\Listeners\NotifyRequestWhenDevDone;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    /** @var array<class-string, array<int, class-string>> */
    protected $listen = [
        TicketCreated::class => [NotifyAgentsOnNewTicket::class, MirrorTrelloChanges::class],
        TicketUpdated::class => [MirrorTrelloChanges::class],
        CommentAdded::class => [NotifyOnCommentAdded::class, MirrorTrelloComment::class],
        TicketStatusChanged::class => [NotifyOnStatusChanged::class, NotifyRequestWhenDevDone::class, MirrorTrelloChanges::class],
        TicketAssigned::class => [NotifyOnTicketAssigned::class, MirrorTrelloChanges::class],
        TicketReopened::class => [NotifyOnTicketReopened::class],
    ];
}
