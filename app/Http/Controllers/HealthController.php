<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

final class HealthController
{
    public function __invoke(): JsonResponse
    {
        Gate::denyIf(fn () => request()->user()?->isAdmin() !== true);

        $checks = [
            'database' => $this->check(static fn (): bool => DB::select('select 1') !== []),
            'cache' => $this->check(static function (): bool {
                Cache::put('health:probe', 'ok', 10);

                return Cache::get('health:probe') === 'ok';
            }),
            'queue' => $this->check(static fn (): bool => Queue::size() >= 0),
            'storage' => $this->check(static function (): bool {
                $path = 'health/'.bin2hex(random_bytes(6)).'.txt';
                Storage::disk('attachments')->put($path, 'ok');
                $ok = Storage::disk('attachments')->get($path) === 'ok';
                Storage::disk('attachments')->delete($path);

                return $ok;
            }),
            'scheduler' => $this->check(static fn (): bool => Setting::get('health.scheduler_at') !== null),
            'backup' => $this->check(static fn (): bool => Setting::get('health.last_backup_at') !== null),
            'reverb' => $this->check(static fn (): bool => config('broadcasting.default') !== null),
        ];

        $healthy = ! in_array(false, array_column($checks, 'ok'), true);

        return response()->json(['status' => $healthy ? 'ok' : 'degraded', 'checks' => $checks], $healthy ? 200 : 503);
    }

    /** @return array{ok: bool, error?: string} */
    private function check(callable $probe): array
    {
        try {
            return ['ok' => (bool) $probe()];
        } catch (\Throwable $exception) {
            return ['ok' => false, 'error' => $exception->getMessage()];
        }
    }
}
