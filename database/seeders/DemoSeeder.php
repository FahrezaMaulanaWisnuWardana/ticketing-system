<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\TicketNumbering;
use App\Enums\Priority;
use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Enums\ProjectTemplate;
use App\Enums\TicketKind;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Support\RequestTypeCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'staging'])) {
            return;
        }

        $admin = User::firstOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Rangga Aditya', 'password' => Hash::make('password123'), 'role' => 'admin', 'is_active' => true,
        ]);
        $lead = User::firstOrCreate(['email' => 'lead@example.com'], [
            'name' => 'Dian Kusuma', 'password' => Hash::make('password123'), 'role' => 'agent', 'is_active' => true,
        ]);

        $agents = collect([$lead]);
        $agentNames = [
            'agent1@example.com' => 'Nadia Putri',
            'agent2@example.com' => 'Fajar Hidayat',
            'agent3@example.com' => 'Maya Sari',
        ];
        foreach ($agentNames as $email => $name) {
            $agents->push(User::firstOrCreate(['email' => $email], [
                'name' => $name, 'password' => Hash::make('password123'), 'role' => 'agent', 'is_active' => true,
            ]));
        }

        $requesterNames = [
            'user1@example.com' => 'Budi Santoso',
            'user2@example.com' => 'Siti Rahayu',
            'user3@example.com' => 'Andi Pratama',
            'user4@example.com' => 'Dewi Lestari',
            'user5@example.com' => 'Rizky Ramadhan',
        ];
        $requesters = collect();
        foreach ($requesterNames as $email => $name) {
            $requesters->push(User::firstOrCreate(['email' => $email], [
                'name' => $name, 'password' => Hash::make('password123'), 'role' => 'requester', 'is_active' => true,
            ]));
        }

        $helpdesk = Project::firstOrCreate(['key' => 'IT'], [
            'name' => 'IT Helpdesk', 'owner_id' => $admin->id, 'status' => ProjectStatus::Active,
            'template' => ProjectTemplate::Helpdesk, 'description' => 'Layanan dukungan teknis internal',
        ]);
        $dev = Project::firstOrCreate(['key' => 'APP'], [
            'name' => 'Aplikasi Internal', 'owner_id' => $lead->id, 'status' => ProjectStatus::Active,
            'template' => ProjectTemplate::Dev, 'description' => 'Pengembangan dan perbaikan aplikasi internal',
        ]);

        foreach ([$helpdesk, $dev] as $project) {
            foreach ($agents as $agent) {
                $project->members()->syncWithoutDetaching([
                    $agent->id => ['role' => ($agent->is($lead) && $project->is($dev) ? ProjectRole::Lead : ProjectRole::Member)->value],
                ]);
            }

            foreach (RequestTypeCatalog::all() as $key => $definition) {
                if (($definition['project'] ?? null) !== $project->key) {
                    continue;
                }

                Category::firstOrCreate(['project_id' => $project->id, 'name' => RequestTypeCatalog::label($key)], [
                    'kind' => $project->is($helpdesk) ? TicketKind::Request : TicketKind::Dev,
                    'form_schema' => [], 'sort' => 0,
                ]);
            }
        }

        foreach ($this->ticketTemplates() as $index => $template) {
            $projectKey = data_get(RequestTypeCatalog::find($template['type']), 'project', 'IT');
            $project = $projectKey === 'APP' ? $dev : $helpdesk;
            $number = TicketNumbering::next($project->id);
            $status = $template['status'];
            $reporter = $requesters[$index % $requesters->count()];
            $requester = $index % 5 === 0
                ? $requesters[($index + 2) % $requesters->count()]
                : $reporter;
            $assignee = $status->isFinal() || $index % 4 === 0
                ? $agents[$index % $agents->count()]
                : null;

            Ticket::create([
                'project_id' => $project->id,
                'number' => $number,
                'kind' => $project->is($helpdesk) ? TicketKind::Request : TicketKind::Dev,
                'type' => $template['type'],
                'category_id' => Category::where('project_id', $project->id)
                    ->where('name', RequestTypeCatalog::label($template['type']))->value('id'),
                'title' => $template['title'],
                'description' => $template['description'],
                'status' => $status,
                'priority' => $template['priority'],
                'reporter_id' => $reporter->id,
                'requester_id' => $requester->id,
                'assignee_id' => $assignee?->id,
                'rank' => str_pad((string) $number, 10, '0', STR_PAD_LEFT),
                'first_response_at' => $assignee ? now()->subHours(6) : null,
                'resolved_at' => $status->isFinal() ? now()->subDays(2) : null,
                'closed_at' => $status === TicketStatus::Closed ? now()->subDays(1) : null,
            ]);
        }

        DB::table('settings')->updateOrInsert(['key' => 'app.initialized'], ['value' => (string) json_encode(['at' => now()->toIso8601String()])]);
    }

    /**
     * Realistic, Bahasa Indonesia helpdesk requests. No lorem ipsum.
     *
     * @return array<int, array{type: string, title: string, description: string, status: TicketStatus, priority: Priority}>
     */
    private function ticketTemplates(): array
    {
        $items = [
            ['akses_akun', 'Tidak bisa login ke email kantor', 'Akun email saya terblokir setelah tiga kali salah memasukkan kata sandi. Mohon bantuannya untuk membuka kembali aksesnya.'],
            ['gangguan_it', 'Wi-Fi lantai 2 sering terputus', 'Jaringan Wi-Fi di area lantai 2 sering putus saat rapat, terutama sekitar pukul 10.00 sampai 12.00.'],
            ['gangguan_it', 'Printer lantai 1 tidak merespons', 'Printer bersama di lantai 1 tidak bisa menerima perintah cetak walaupun lampu indikator menyala.'],
            ['akses_akun', 'Mohon akses folder keuangan', 'Saya ditugaskan membantu rekapitulasi anggaran dan memerlukan akses baca ke folder Keuangan 2026.'],
            ['bug_aplikasi', 'Form pengajuan cuti gagal disimpan', 'Saat menekan tombol Simpan pada form cuti, muncul pesan "Terjadi kesalahan" dan data tidak tersimpan.'],
            ['perangkat_baru', 'Pengajuan monitor tambahan', 'Untuk kebutuhan kerja hybrid, saya mengajukan satu monitor tambahan beserta kabel HDMI.'],
            ['administrasi', 'Permohonan surat keterangan kerja', 'Saya memerlukan surat keterangan kerja untuk keperluan pengajuan visa.'],
            ['fitur_baru', 'Permintaan ekspor laporan ke Excel', 'Mohon ditambahkan tombol ekspor laporan bulanan ke format Excel agar bisa diolah lebih lanjut.'],
            ['perubahan_data', 'Perbaikan nama pada data karyawan', 'Nama saya di sistem masih tertulis "Budi S." mohon diperbaiki menjadi "Budi Santoso".'],
            ['lainnya', 'Permintaan bantuan umum', 'Saya tidak yakin jenis permintaan ini. Mohon bantuannya untuk mengarahkan ke tim yang tepat.'],
            ['gangguan_it', 'Laptop tidak menyala setelah update', 'Setelah pembaruan sistem kemarin, laptop tidak menyala dan hanya menyala lampu indikatornya.'],
            ['bug_aplikasi', 'Notifikasi approval tidak masuk', 'Notifikasi persetujuan tidak muncul walaupun status pengajuan sudah berubah menjadi disetujui.'],
            ['akses_akun', 'Reset kata sandi aplikasi absensi', 'Kata sandi aplikasi absensi saya lupa dan tautan reset tidak pernah sampai ke email.'],
            ['fitur_baru', 'Penambahan filter pada daftar tiket', 'Mohon filter berdasarkan tanggal dibuat agar lebih mudah mencari permintaan lama.'],
        ];

        $statuses = [
            TicketStatus::New, TicketStatus::InProgress, TicketStatus::WaitingRequester,
            TicketStatus::InReview, TicketStatus::Done, TicketStatus::Closed, TicketStatus::Todo,
        ];
        $priorities = [Priority::Urgent, Priority::High, Priority::Normal, Priority::Normal, Priority::Low];

        $result = [];
        foreach ($items as $index => [$type, $title, $description]) {
            $result[] = [
                'type' => $type,
                'title' => $title,
                'description' => $description,
                'status' => $statuses[$index % count($statuses)],
                'priority' => $priorities[$index % count($priorities)],
            ];
        }

        return $result;
    }
}
