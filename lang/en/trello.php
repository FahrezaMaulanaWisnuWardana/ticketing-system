<?php

declare(strict_types=1);

return [
    'settings' => 'Trello integration', 'connect' => 'Connect Trello', 'connected' => 'Trello connected.',
    'not_connected' => 'No Trello board is connected to this project.', 'status' => 'Status', 'mode' => 'Mode',
    'token' => 'Token', 'last_sync' => 'Last sync', 'pause' => 'Pause', 'resume' => 'Resume',
    'paused' => 'Integration paused.', 'resumed' => 'Integration resumed and reconciliation queued.',
    'updated' => 'Integration settings saved.', 'disconnect' => 'Disconnect', 'disconnected' => 'Trello disconnected.',
    'disconnect_confirm' => 'Disconnect Trello?', 'forget_links' => 'Also forget links',
    'import' => 'Import cards', 'imports' => 'Import runs', 'import_report' => 'Import report',
    'import_queued' => 'Import queued.', 'errors' => 'Errors', 'link_existing' => 'Link existing items',
    'link' => 'Link items', 'linked' => 'Items linked.', 'conflicts' => 'Sync conflicts', 'app_wins' => 'App value kept',
    'changed_externally' => 'This card was changed on Trello. Changes there are not synced back.',
    'resync' => 'Re-sync from app', 'resync_queued' => 'Re-sync queued.', 'unlink' => 'Unlink', 'unlinked' => 'Card unlinked.',
    'mirror_helpdesk' => 'Mirror helpdesk tickets', 'api_key' => 'API key', 'board_id' => 'Board ID',
    'invalid_credentials' => 'The Trello credentials or board could not be verified.',
    'board_too_large' => 'This board exceeds the configured card limit.',
    'ssrf_blocked' => 'Only Trello hosts may be downloaded.',
    'card_footer' => 'View in Ticketing: :url',
    'step_credentials' => 'Step 1 — paste a read-only expiring token and board ID.',
    'step_existing' => 'Step 2 — what should happen to what already exists?',
    'option_import' => 'Import cards from the board', 'option_push' => 'Send project tickets to a new board',
    'option_link' => 'Link matching items', 'option_start' => 'Just start syncing from now',
    'step_mode' => 'Step 3 — choose a sync mode.', 'step_confirm' => 'Step 4 — confirm and connect.',
    'question' => 'Does this project already use a Trello board?',
    'answer_no' => 'No', 'answer_later' => 'Ask me later', 'answer_yes' => 'Yes, connect it now',
    'banner' => 'Does this project use Trello? You can connect a board in Project settings.',
];
