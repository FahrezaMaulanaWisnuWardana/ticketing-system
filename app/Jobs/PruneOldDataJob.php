<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Services\AttachmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PruneOldDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $ticketCutoff = now()->subYears((int) config('ticketing.retention.tickets_years'));
        $auditCutoff = now()->subYears((int) config('ticketing.retention.audit_years'));

        $tickets = Ticket::withoutGlobalScopes()
            ->where('created_at', '<', $ticketCutoff)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);
        $audits = AuditLog::where('created_at', '<', $auditCutoff)->delete();
        DB::table('failed_jobs')->where('failed_at', '<', now()->subWeek())->delete();

        // Editor uploads that were never attached to a ticket are abandoned drafts.
        $files = app(AttachmentService::class);
        $pending = 0;
        Attachment::whereNull('ticket_id')
            ->where('created_at', '<', now()->subDay())
            ->eachById(static function (Attachment $attachment) use ($files, &$pending): void {
                $files->delete($attachment->disk, $attachment->path);
                $attachment->forceDelete();
                $pending++;
            });

        Log::info('Retention prune complete', [
            'tickets' => $tickets,
            'audit_logs' => $audits,
            'pending_attachments' => $pending,
        ]);
    }
}
