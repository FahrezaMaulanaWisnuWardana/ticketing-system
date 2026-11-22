<?php

declare(strict_types=1);

namespace App\Support;

final class NotificationEvents
{
    public const TICKET_CREATED = 'ticket.created';

    public const COMMENT_PUBLIC = 'comment.public';

    public const STATUS_CHANGED = 'status.changed';

    public const ASSIGNED = 'ticket.assigned';

    public const MENTIONED = 'user.mentioned';

    public const REQUESTER_REPLIED = 'requester.replied';

    public const REOPENED = 'ticket.reopened';

    public const SLA_AT_RISK = 'sla.at_risk';

    public const SLA_BREACHED = 'sla.breached';
}
