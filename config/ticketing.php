<?php

declare(strict_types=1);

return [
    'display_timezone' => env('DISPLAY_TIMEZONE', 'Asia/Jakarta'),

    'allowed_email_domains' => array_values(array_filter(explode(',', (string) env('ALLOWED_EMAIL_DOMAINS', '')))),

    'require_admin_2fa' => (bool) env('REQUIRE_ADMIN_2FA', true),

    'session_idle_minutes' => (int) env('SESSION_IDLE_MINUTES', 480),

    'invite_ttl_hours' => 48,

    'uploads' => [
        'max_kb' => (int) env('UPLOAD_MAX_KB', 10240),
        'max_files' => (int) env('UPLOAD_MAX_FILES', 5),
        'allowed_mimes' => [
            'image/png', 'image/jpeg', 'image/gif', 'image/webp',
            'application/pdf', 'text/plain', 'text/csv',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip', 'application/x-zip-compressed',
        ],
        'blocked_extensions' => [
            'php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'exe', 'sh', 'bash',
            'js', 'mjs', 'html', 'htm', 'svg', 'jsp', 'asp', 'aspx', 'dll', 'bat', 'cmd',
        ],
    ],

    'csp_mode' => env('CSP_MODE', 'report-only'),

    'security_headers' => (bool) env('SECURITY_HEADERS', true),

    'sla' => [
        'timezone' => env('DISPLAY_TIMEZONE', 'Asia/Jakarta'),
        'business_start' => '08:00',
        'business_end' => '17:00',
        'workdays' => [1, 2, 3, 4, 5],
        'urgent_24x7' => true,
        'targets' => [
            'urgent' => ['response' => 30, 'resolve' => 240],
            'high' => ['response' => 120, 'resolve' => 480],
            'normal' => ['response' => 480, 'resolve' => 1440],
            'low' => ['response' => 960, 'resolve' => 2400],
        ],
    ],

    'auto_close_days' => (int) env('AUTO_CLOSE_DAYS', 7),

    'reopen_days' => (int) env('REOPEN_DAYS', 7),

    'retention' => [
        'tickets_years' => (int) env('TICKET_RETENTION_YEARS', 3),
        'audit_years' => (int) env('AUDIT_RETENTION_YEARS', 1),
    ],
];
