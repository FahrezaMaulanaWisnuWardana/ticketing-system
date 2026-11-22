<?php

declare(strict_types=1);

return [
    'requester' => [
        'open' => 'Terbuka',
        'in_progress' => 'Dalam proses',
        'needs_reply' => 'Perlu balasan Anda',
        'resolved' => 'Selesai',
    ],
    // Five canonical ticket statuses. Legacy keys remain for old data/board columns.
    'new' => 'Baru', 'backlog' => 'Antrean', 'todo' => 'Akan dikerjakan', 'in_progress' => 'Dikerjakan',
    'in_review' => 'Review', 'waiting_requester' => 'Menunggu', 'done' => 'Selesai', 'closed' => 'Ditutup',
    'active' => 'Aktif', 'on_hold' => 'Ditunda', 'completed' => 'Selesai', 'archived' => 'Diarsipkan',
];
