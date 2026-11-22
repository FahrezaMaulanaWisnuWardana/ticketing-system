<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class LoginController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        if (! Auth::validate($credentials)) {
            AuditService::record('login.failure', null, ['email' => (string) $request->input('email')]);

            return back()->withErrors(['email' => __('auth.failed')])->onlyInput('email');
        }

        $user = User::where('email', mb_strtolower((string) $request->input('email')))->firstOrFail();
        if (! $user->is_active) {
            AuditService::record('login.failure', $user, ['reason' => 'inactive']);

            return back()->withErrors(['email' => __('auth.failed')])->onlyInput('email');
        }

        $remember = (bool) $request->boolean('remember') && $user->isRequester();
        Auth::login($user, $remember);
        $user->forceFill(['last_login_at' => now()])->save();
        AuditService::record('login.success', $user);
        $request->session()->regenerate();

        if ($user->hasTwoFactor()) {
            Auth::logout();
            $request->session()->put('auth.2fa_user_id', $user->id);

            return redirect()->route('2fa.challenge');
        }

        return redirect()->intended(route('home'));
    }

    public function logout(): RedirectResponse
    {
        $user = Auth::user();
        if ($user !== null) {
            AuditService::record('logout', $user);
        }
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
