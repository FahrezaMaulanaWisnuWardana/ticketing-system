<?php

declare(strict_types=1);

return [
    'requester' => [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'needs_reply' => 'Needs your reply',
        'resolved' => 'Resolved',
    ],
    // Five canonical ticket statuses. Legacy keys remain for old data/board columns.
    'new' => 'New', 'backlog' => 'Backlog', 'todo' => 'To do', 'in_progress' => 'In progress',
    'in_review' => 'Review', 'waiting_requester' => 'Waiting', 'done' => 'Done', 'closed' => 'Closed',
    'active' => 'Active', 'on_hold' => 'On hold', 'completed' => 'Completed', 'archived' => 'Archived',
];
