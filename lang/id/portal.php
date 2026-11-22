<?php

declare(strict_types=1);

return [
    'app_tagline' => 'Portal layanan internal',

    'nav' => [
        'home' => 'Beranda',
        'my_requests' => 'Permintaan saya',
        'queues' => 'Antrean',
        'guide' => 'Panduan penggunaan',
        'section_requests' => 'Permintaan',
        'section_manage' => 'Pengelolaan',
        'create' => 'Buat permintaan',
        'choose_type' => 'Pilih jenis permintaan',
        'global_search' => 'Cari berdasarkan nomor atau kata kunci',
        'global_search_aria' => 'Cari tiket berdasarkan nomor atau kata kunci',
        'help' => 'Bantuan dan pintasan',
        'user_menu' => 'Menu pengguna',
        'toggle_navigation' => 'Buka atau tutup menu navigasi',
        'language' => 'Bahasa',
        'theme' => 'Tema',
        'sign_out' => 'Keluar',
    ],

    'home' => [
        'eyebrow' => 'Selamat datang di portal layanan',
        'heading' => 'Apa yang bisa kami bantu?',
        'subheading' => 'Cari panduan atau pilih jenis permintaan di bawah ini. Kami akan membantu Anda sampai selesai.',
        'search_label' => 'Cari bantuan atau jenis permintaan',
        'search_placeholder' => 'Apa yang bisa kami bantu?',
        'search_hint' => 'Coba “akses email”, “laptop mati”, atau “IT-12”.',
        'searching' => 'Mencari…',
        'no_results' => 'Tidak ada hasil yang cocok.',
        'results_types' => 'Jenis permintaan',
        'results_articles' => 'Panduan',
        'types_heading' => 'Pilih jenis permintaan',
        'types_subheading' => 'Pilih yang paling sesuai. Anda bisa menambahkan detail pada langkah berikutnya.',
        'recent_heading' => 'Permintaan terbaru saya',
        'recent_empty' => 'Belum ada permintaan. Pilih jenis permintaan untuk memulai.',
        'view_all' => 'Lihat semua',
    ],

    'groups' => [
        'it' => 'IT',
        'aplikasi' => 'Aplikasi',
        'umum' => 'Umum',
    ],

    'types' => [
        'gangguan_it' => [
            'label' => 'Gangguan komputer & jaringan',
            'description' => 'Laptop, Wi-Fi, printer, atau perangkat lain tidak berfungsi.',
        ],
        'akses_akun' => [
            'label' => 'Akses & akun',
            'description' => 'Minta akun baru, ubah kata sandi, atau perbaiki hak akses.',
        ],
        'perangkat_baru' => [
            'label' => 'Perangkat baru',
            'description' => 'Pengajuan laptop, monitor, atau perlengkapan kerja lainnya.',
        ],
        'bug_aplikasi' => [
            'label' => 'Masalah aplikasi',
            'description' => 'Laporkan fitur yang error, lambat, atau tidak sesuai harapan.',
        ],
        'fitur_baru' => [
            'label' => 'Permintaan fitur',
            'description' => 'Usulkan penambahan atau perubahan fitur aplikasi internal.',
        ],
        'perubahan_data' => [
            'label' => 'Perubahan data',
            'description' => 'Minta bantuan mengubah data master atau data referensi.',
        ],
        'administrasi' => [
            'label' => 'Administrasi',
            'description' => 'Pertanyaan umum, formulir, dan urusan administrasi kantor.',
        ],
        'lainnya' => [
            'label' => 'Lainnya',
            'description' => 'Tidak menemukan pilihan yang sesuai? Ceritakan kebutuhan Anda.',
        ],
    ],

    'articles' => [
        'cart' => 'Cara membuat permintaan baru',
        'status' => 'Arti status permintaan',
        'sla' => 'Berapa lama permintaan diproses?',
        'akun' => 'Panduan akses dan akun',
    ],

    'status' => [
        'todo' => 'Akan dikerjakan',
        'in_progress' => 'Dalam proses',
        'waiting' => 'Menunggu balasan',
        'done' => 'Selesai',
    ],

    'list' => [
        'title' => 'Permintaan saya',
        'subtitle' => 'Semua permintaan yang Anda ajukan, lengkap dengan status terakhirnya.',
        'created_by' => 'Dibuat oleh',
        'created_by_mine' => 'Dibuat oleh saya',
        'created_by_all' => 'Semua permintaan',
        'status_filter' => 'Status',
        'status_open' => 'Permintaan terbuka',
        'status_done' => 'Permintaan selesai',
        'status_all' => 'Semua',
        'type' => 'Jenis permintaan',
        'all_types' => 'Semua jenis',
        'search' => 'Cari dalam daftar',
        'search_placeholder' => 'Cari berdasarkan judul atau nomor…',
        'reset' => 'Atur ulang filter',
        'results_count' => ':count permintaan ditemukan',
    ],

    'table' => [
        'type' => 'Jenis',
        'reference' => 'Referensi',
        'summary' => 'Ringkasan',
        'status' => 'Status',
        'requester' => 'Pemohon',
        'reporter' => 'Pelapor',
        'created' => 'Dibuat',
        'updated' => 'Diperbarui',
        'assignee' => 'Penanggung jawab',
        'priority' => 'Prioritas',
        'due' => 'Jatuh tempo',
        'resolution' => 'Waktu penyelesaian',
        'unassigned' => 'Belum ditugaskan',
        'select' => 'Pilih tiket',
        'select_all' => 'Pilih semua tiket di halaman ini',
        'open_ticket' => 'Buka tiket',
        'pending_reply' => 'Perlu balasan Anda',
        'sort' => 'Urutkan menurut :column',
        'scroll_hint' => 'Geser tabel ke samping untuk melihat kolom lainnya.',
    ],

    'sla' => [
        'breached' => 'Terlewat',
        'at_risk' => 'Hampir terlewat',
        'on_track' => 'Masih aman',
        'met' => 'Terselesaikan',
    ],

    'empty' => [
        'title' => 'Belum ada permintaan',
        'hint' => 'Semua permintaan yang Anda buat akan muncul di sini.',
        'filtered_title' => 'Tidak ada permintaan yang cocok',
        'filtered_hint' => 'Coba ubah filter atau kata kunci pencarian Anda.',
        'action' => 'Buat permintaan',
    ],

    'pagination' => [
        'previous' => 'Sebelumnya',
        'next' => 'Berikutnya',
        'page' => 'Halaman :page',
        'showing' => 'Menampilkan :from–:to dari :total permintaan',
        'label' => 'Navigasi halaman',
    ],

    'queues' => [
        'title' => 'Antrean',
        'subtitle' => 'Kelola permintaan yang masuk ke tim Anda.',
        'all_open' => 'Semua terbuka',
        'mine' => 'Ditugaskan ke saya',
        'unassigned' => 'Belum ditugaskan',
        'new' => 'Baru dibuat',
        'recently_resolved' => 'Baru diselesaikan',
        'search' => 'Cari dalam antrean',
        'selected' => ':count tiket dipilih',
        'bulk_assign' => 'Tetapkan ke saya',
        'bulk_status' => 'Ubah status',
        'assignee' => 'Penanggung jawab',
        'select_assignee' => 'Pilih penanggung jawab',
        'choose_status' => 'Pilih status',
        'apply' => 'Terapkan',
        'assigned' => ':count tiket berhasil ditetapkan.',
        'status_changed' => ':count tiket berhasil diperbarui.',
        'no_selection' => 'Pilih minimal satu tiket terlebih dahulu.',
        'empty' => 'Antrean ini kosong',
        'empty_hint' => 'Tidak ada tiket yang cocok dengan antrean atau filter ini.',
    ],

    'picker' => [
        'title' => 'Jenis permintaan apa yang Anda butuhkan?',
        'hint' => 'Pilih satu untuk membuka formulir pengajuan.',
        'cancel' => 'Batal',
    ],

    'suggest' => [
        'aria' => 'Hasil pencarian bantuan',
        'empty' => 'Tidak ada hasil. Coba kata kunci lain.',
    ],
];
