<?php

declare(strict_types=1);

/*
 * The portal's request-type catalogue.
 *
 * Keys are stored verbatim in tickets.type (a varchar column), so adding a
 * type here immediately makes it available on the home page, in the type
 * picker, in list filters and in the demo seeder. All visible text lives in
 * lang/{id,en}/portal.php; only stable identifiers and SVG paths live here.
 */
return [
    'groups' => [
        'it' => 'portal.groups.it',
        'aplikasi' => 'portal.groups.aplikasi',
        'umum' => 'portal.groups.umum',
    ],

    'types' => [
        'gangguan_it' => [
            'group' => 'it',
            'project' => 'IT',
            'priority' => 'high',
            'keywords' => 'laptop komputer wifi jaringan printer mati error keyboard mouse',
            'icon' => '<rect x="4" y="4.5" width="16" height="11" rx="2"/><path stroke-linecap="round" d="M9 19h6m-3-3.5V19"/>',
        ],
        'akses_akun' => [
            'group' => 'it',
            'project' => 'IT',
            'priority' => 'normal',
            'keywords' => 'akun login kata sandi password akses hak izin email sso',
            'icon' => '<circle cx="12" cy="9" r="3"/><path stroke-linecap="round" d="M5.25 19.5a6.75 6.75 0 0 1 13.5 0"/>',
        ],
        'perangkat_baru' => [
            'group' => 'it',
            'project' => 'IT',
            'priority' => 'normal',
            'keywords' => 'laptop monitor keyboard mouse perlengkapan pengajuan perangkat',
            'icon' => '<rect x="3.75" y="5.25" width="16.5" height="10.5" rx="2"/><path stroke-linecap="round" d="M8 19.5h8"/>',
        ],
        'bug_aplikasi' => [
            'group' => 'aplikasi',
            'project' => 'APP',
            'priority' => 'high',
            'keywords' => 'aplikasi error bug lambat crash tidak bisa fitur sistem',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v4.5m0 3h.01"/><circle cx="12" cy="12" r="9"/>',
        ],
        'fitur_baru' => [
            'group' => 'aplikasi',
            'project' => 'APP',
            'priority' => 'normal',
            'keywords' => 'fitur baru permintaan usulan perubahan peningkatan aplikasi',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>',
        ],
        'perubahan_data' => [
            'group' => 'aplikasi',
            'project' => 'APP',
            'priority' => 'normal',
            'keywords' => 'data master referensi ubah koreksi customer karyawan',
            'icon' => '<ellipse cx="12" cy="6.75" rx="7.5" ry="3"/><path stroke-linecap="round" d="M4.5 6.75v10.5c0 1.66 3.36 3 7.5 3s7.5-1.34 7.5-3V6.75M4.5 12c0 1.66 3.36 3 7.5 3s7.5-1.34 7.5-3"/>',
        ],
        'administrasi' => [
            'group' => 'umum',
            'project' => 'IT',
            'priority' => 'low',
            'keywords' => 'administrasi formulir kantor umum pertanyaan surat izin',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h7l4 4V20a1 1 0 0 1-1 1h-10a1 1 0 0 1-1-1V4.75a1 1 0 0 1 1-1Z"/><path stroke-linecap="round" d="M14 3.75v4h4M9 12h6m-6 3.5h6"/>',
        ],
        'lainnya' => [
            'group' => 'umum',
            'project' => 'IT',
            'priority' => 'normal',
            'keywords' => 'lainnya lainnya masih bingung bantuan umum',
            'icon' => '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M9.75 9.5a2.4 2.4 0 1 1 3.6 2.1c-.85.5-1.35 1-1.35 2.15v.5m0 3h.01"/>',
        ],
    ],

    'articles' => [
        'cart' => [
            'url' => '/panduan#membuat-permintaan',
            'keywords' => 'cara buat ajukan permintaan baru form',
        ],
        'status' => [
            'url' => '/panduan#status-permintaan',
            'keywords' => 'arti status terbuka proses selesai menunggu',
        ],
        'sla' => [
            'url' => '/panduan#waktu-penyelesaian',
            'keywords' => 'berapa lama waktu proses penyelesaian sla target',
        ],
        'akun' => [
            'url' => '/panduan#akses-dan-akun',
            'keywords' => 'akun akses kata sandi login email hak',
        ],
    ],
];
