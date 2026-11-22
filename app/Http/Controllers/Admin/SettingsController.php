<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class SettingsController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('admin.settings', [
            'autoCloseDays' => (int) Setting::get('tickets.auto_close_days', (int) config('ticketing.auto_close_days')),
            'reopenDays' => (int) Setting::get('tickets.reopen_days', (int) config('ticketing.reopen_days')),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        Gate::authorize('viewAny', User::class);

        foreach ($request->validated() as $key => $value) {
            Setting::set('tickets.'.$key, $value);
        }
        AuditService::record('settings.updated', null, ['keys' => array_keys($request->validated())]);

        return back()->with('status', __('admin.settings_updated'));
    }
}
