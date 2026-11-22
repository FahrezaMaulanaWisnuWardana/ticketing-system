<?php

declare(strict_types=1);

return [
    'app_tagline' => 'Internal service portal',

    'nav' => [
        'home' => 'Home',
        'my_requests' => 'My requests',
        'queues' => 'Queues',
        'guide' => 'User guide',
        'section_requests' => 'Requests',
        'section_manage' => 'Manage',
        'create' => 'Create request',
        'choose_type' => 'Choose a request type',
        'global_search' => 'Search by key or keyword',
        'global_search_aria' => 'Search tickets by key or keyword',
        'help' => 'Help and shortcuts',
        'user_menu' => 'User menu',
        'toggle_navigation' => 'Open or close the navigation menu',
        'language' => 'Language',
        'theme' => 'Theme',
        'sign_out' => 'Sign out',
    ],

    'home' => [
        'eyebrow' => 'Welcome to the service portal',
        'heading' => 'What can we help you with?',
        'subheading' => 'Search the guides or pick a request type below. We will take it from there.',
        'search_label' => 'Search help or request types',
        'search_placeholder' => 'What can we help you with?',
        'search_hint' => 'Try “email access”, “laptop won’t start”, or “IT-12”.',
        'searching' => 'Searching…',
        'no_results' => 'No matching results.',
        'results_types' => 'Request types',
        'results_articles' => 'Guides',
        'types_heading' => 'Choose a request type',
        'types_subheading' => 'Pick the closest match. You can add details on the next step.',
        'recent_heading' => 'My recent requests',
        'recent_empty' => 'No requests yet. Choose a request type to get started.',
        'view_all' => 'View all',
    ],

    'groups' => [
        'it' => 'IT',
        'aplikasi' => 'Applications',
        'umum' => 'General',
    ],

    'types' => [
        'gangguan_it' => [
            'label' => 'Computer & network issues',
            'description' => 'Laptop, Wi-Fi, printer, or another device is not working.',
        ],
        'akses_akun' => [
            'label' => 'Access & accounts',
            'description' => 'Request a new account, reset a password, or fix permissions.',
        ],
        'perangkat_baru' => [
            'label' => 'New equipment',
            'description' => 'Request a laptop, monitor, or other work equipment.',
        ],
        'bug_aplikasi' => [
            'label' => 'Application problems',
            'description' => 'Report a feature that is broken, slow, or incorrect.',
        ],
        'fitur_baru' => [
            'label' => 'Feature request',
            'description' => 'Suggest a new or improved feature for an internal app.',
        ],
        'perubahan_data' => [
            'label' => 'Data changes',
            'description' => 'Ask for a change to master or reference data.',
        ],
        'administrasi' => [
            'label' => 'Administration',
            'description' => 'General questions, forms, and office administration.',
        ],
        'lainnya' => [
            'label' => 'Something else',
            'description' => 'None of the above? Tell us what you need.',
        ],
    ],

    'articles' => [
        'cart' => 'How to create a request',
        'status' => 'What each request status means',
        'sla' => 'How long does a request take?',
        'akun' => 'Account and access guide',
    ],

    'status' => [
        'todo' => 'To do',
        'in_progress' => 'In progress',
        'waiting' => 'Waiting for reply',
        'done' => 'Done',
    ],

    'list' => [
        'title' => 'My requests',
        'subtitle' => 'Every request you filed, with its latest status.',
        'created_by' => 'Created by',
        'created_by_mine' => 'Created by me',
        'created_by_all' => 'All requests',
        'status_filter' => 'Status',
        'status_open' => 'Open requests',
        'status_done' => 'Finished requests',
        'status_all' => 'All',
        'type' => 'Request type',
        'all_types' => 'All types',
        'search' => 'Search this list',
        'search_placeholder' => 'Search by title or key…',
        'reset' => 'Reset filters',
        'results_count' => ':count requests found',
    ],

    'table' => [
        'type' => 'Type',
        'reference' => 'Reference',
        'summary' => 'Summary',
        'status' => 'Status',
        'requester' => 'Requester',
        'reporter' => 'Reporter',
        'created' => 'Created',
        'updated' => 'Updated',
        'assignee' => 'Assignee',
        'priority' => 'Priority',
        'due' => 'Due',
        'resolution' => 'Resolution time',
        'unassigned' => 'Unassigned',
        'select' => 'Select ticket',
        'select_all' => 'Select all tickets on this page',
        'open_ticket' => 'Open ticket',
        'pending_reply' => 'Needs your reply',
        'sort' => 'Sort by :column',
        'scroll_hint' => 'Scroll the table sideways to see more columns.',
    ],

    'sla' => [
        'breached' => 'Breached',
        'at_risk' => 'Almost due',
        'on_track' => 'On track',
        'met' => 'Resolved',
    ],

    'empty' => [
        'title' => 'No requests yet',
        'hint' => 'Every request you file will appear here.',
        'filtered_title' => 'No requests match your filters',
        'filtered_hint' => 'Try changing the filters or search term.',
        'action' => 'Create a request',
    ],

    'pagination' => [
        'previous' => 'Previous',
        'next' => 'Next',
        'page' => 'Page :page',
        'showing' => 'Showing :from–:to of :total requests',
        'label' => 'Pagination',
    ],

    'queues' => [
        'title' => 'Queues',
        'subtitle' => 'Manage the requests coming to your team.',
        'all_open' => 'All open',
        'mine' => 'Assigned to me',
        'unassigned' => 'Unassigned',
        'new' => 'Newly created',
        'recently_resolved' => 'Recently resolved',
        'search' => 'Search this queue',
        'selected' => ':count tickets selected',
        'bulk_assign' => 'Assign to me',
        'bulk_status' => 'Change status',
        'assignee' => 'Assignee',
        'select_assignee' => 'Select an assignee',
        'choose_status' => 'Select a status',
        'apply' => 'Apply',
        'assigned' => ':count tickets assigned.',
        'status_changed' => ':count tickets updated.',
        'no_selection' => 'Select at least one ticket first.',
        'empty' => 'This queue is empty',
        'empty_hint' => 'No tickets match this queue or filter.',
    ],

    'picker' => [
        'title' => 'What kind of request do you need?',
        'hint' => 'Pick one to open the request form.',
        'cancel' => 'Cancel',
    ],

    'suggest' => [
        'aria' => 'Help search results',
        'empty' => 'No results. Try another keyword.',
    ],
];
