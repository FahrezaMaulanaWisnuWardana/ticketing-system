<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CheckSystemHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Setting::set('health.scheduler_at', now()->toIso8601String());
        $failed = (int) DB::table('failed_jobs')->count();
        Setting::set('health.failed_jobs', $failed);

        if ($failed > 10) {
            Log::warning('Alert: failed jobs above threshold', ['count' => $failed]);
        }
        if ((int) Setting::get('health.last_backup_at', 0) === 0
            || Carbon::parse((string) Setting::get('health.last_backup_at'))->lt(now()->subHours(26))) {
            Log::warning('Alert: backup older than 26 hours or never recorded');
        }

        $disk = disk_free_space(storage_path());
        if (is_float($disk) && $disk > 0) {
            $total = disk_total_space(storage_path());
            if (is_float($total) && $total > 0 && ($disk / $total) < 0.2) {
                Log::warning('Alert: disk usage above 80%');
            }
        }
    }
}
