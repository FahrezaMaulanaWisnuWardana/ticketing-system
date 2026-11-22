<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddHolidayRequest;
use App\Http\Requests\UpdateSlaSettingsRequest;
use App\Models\Holiday;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class SlaSettingsController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('admin.sla', [
            'hours' => Setting::get('sla.business_hours', [
                'start' => config('ticketing.sla.business_start'),
                'end' => config('ticketing.sla.business_end'),
                'days' => config('ticketing.sla.workdays'),
                'timezone' => config('ticketing.sla.timezone'),
            ]),
            'targets' => Setting::get('sla.targets', config('ticketing.sla.targets')),
            'urgent24x7' => (bool) Setting::get('sla.urgent_24x7', config('ticketing.sla.urgent_24x7')),
            'holidays' => Holiday::orderBy('date')->get(),
        ]);
    }

    public function update(UpdateSlaSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        Setting::set('sla.business_hours', [
            'start' => $data['business_start'],
            'end' => $data['business_end'],
            'days' => array_map('intval', $data['workdays']),
            'timezone' => config('ticketing.sla.timezone'),
        ]);
        Setting::set('sla.targets', $data['targets']);
        Setting::set('sla.urgent_24x7', (bool) ($data['urgent_24x7'] ?? false));
        AuditService::record('settings.sla_updated', null, ['keys' => array_keys($data)]);

        return back()->with('status', __('admin.settings_updated'));
    }

    public function addHoliday(AddHolidayRequest $request): RedirectResponse
    {
        Holiday::create($request->validated());

        return back()->with('status', __('admin.settings_updated'));
    }

    public function deleteHoliday(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return back()->with('status', __('admin.settings_updated'));
    }
}
