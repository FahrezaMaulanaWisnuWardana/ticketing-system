<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class AuditLogController
{
    public function __invoke(): View
    {
        Gate::authorize('viewAny', AuditLog::class);

        return view('admin.audit-logs', [
            'logs' => AuditLog::query()->with('actor:id,name')->latest('created_at')->paginate(50),
        ]);
    }
}
