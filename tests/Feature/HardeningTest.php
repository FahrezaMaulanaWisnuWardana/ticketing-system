<?php

declare(strict_types=1);

use App\Jobs\CheckSystemHealthJob;
use App\Jobs\PruneOldDataJob;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('records scheduler heartbeat and backup alert state', function (): void {
    (new CheckSystemHealthJob)->handle();

    expect(Setting::get('health.scheduler_at'))->not->toBeNull()
        ->and(Setting::get('health.failed_jobs'))->toBe(0);
});

it('prunes expired audit rows and soft deletes very old tickets', function (): void {
    $ticket = Ticket::factory()->create(['created_at' => now()->subYears(4)]);
    $audit = AuditLog::forceCreate(['action' => 'test', 'created_at' => now()->subYears(2), 'metadata' => []]);

    (new PruneOldDataJob)->handle();

    expect($ticket->refresh()->trashed())->toBeTrue()
        ->and(AuditLog::find($audit->id))->toBeNull();
});
