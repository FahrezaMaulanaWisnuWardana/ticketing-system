<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\CreateUser;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportUsersRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

final class UserController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('admin.users.index', [
            'users' => User::query()->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request, CreateUser $create): RedirectResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            abort(401);
        }
        $user = $create($actor, $request->validated());

        return redirect()->route('admin.users.index')
            ->with('status', __('admin.user_invited', ['email' => $user->email]));
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.edit', ['subject' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());
        AuditService::record('user.updated', $user, ['fields' => array_keys($request->validated())]);

        return back()->with('status', __('admin.user_updated'));
    }

    public function deactivate(User $user): RedirectResponse
    {
        Gate::authorize('activate', $user);
        $user->update(['is_active' => false]);
        $user->tokens()->delete();
        AuditService::record('user.deactivated', $user);

        return back()->with('status', __('admin.user_deactivated'));
    }

    public function activate(User $user): RedirectResponse
    {
        Gate::authorize('activate', $user);
        $user->update(['is_active' => true]);
        AuditService::record('user.activated', $user);

        return back()->with('status', __('admin.user_activated'));
    }

    public function resetTwoFactor(User $user): RedirectResponse
    {
        Gate::authorize('resetTwoFactor', $user);
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
        AuditService::record('2fa.reset', $user);

        return back()->with('status', __('admin.user_2fa_reset'));
    }

    public function showImport(): View
    {
        Gate::authorize('import', User::class);

        return view('admin.users.import');
    }

    public function import(ImportUsersRequest $request, CreateUser $create): View
    {
        $handle = fopen($request->file('csv')->getRealPath(), 'rb');
        if ($handle === false) {
            abort(500);
        }

        $report = ['created' => [], 'errors' => []];
        $row = 0;
        while (($line = fgetcsv($handle, 1000, ',')) !== false) {
            $row++;
            if ($row === 1 || count($line) < 3) {
                continue;
            }
            $data = ['name' => trim((string) $line[0]), 'email' => mb_strtolower(trim((string) $line[1])), 'role' => trim((string) $line[2])];
            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'unique:users,email'],
                'role' => ['required', new Enum(Role::class)],
            ]);
            if ($validator->fails()) {
                $report['errors'][] = ['row' => $row, 'email' => $data['email'], 'message' => $validator->errors()->first()];

                continue;
            }
            $actor = $request->user();
            if (! $actor instanceof User) {
                abort(401);
            }
            $create($actor, $data);
            $report['created'][] = $data['email'];
        }
        fclose($handle);
        AuditService::record('user.import', null, ['created' => count($report['created']), 'errors' => count($report['errors'])]);

        return view('admin.users.import-report', ['report' => $report]);
    }
}
