<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\TwoFactorVerifyRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\TotpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

final class TwoFactorController extends Controller
{
    public function __construct(private readonly TotpService $totp) {}

    public function showSetup(): View|RedirectResponse
    {
        $user = Auth::user();
        if ($user === null) {
            return redirect()->route('login');
        }
        if ($user->hasTwoFactor()) {
            return redirect()->route('home');
        }

        $secret = Session::get('auth.2fa_secret');
        if (! is_string($secret)) {
            $secret = $this->totp->generateSecret();
            Session::put('auth.2fa_secret', $secret);
        }

        return view('auth.two-factor-setup', [
            'secret' => $secret,
            'qrUrl' => $this->totp->otpauthUrl($user->email, $secret),
        ]);
    }

    public function enable(TwoFactorVerifyRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $secret = Session::get('auth.2fa_secret');
        if ($user === null || ! is_string($secret) || ! $this->totp->verify((string) $request->input('code'), $secret)) {
            return back()->withErrors(['code' => __('auth.2fa_invalid')]);
        }

        $codes = $this->totp->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => array_map(static fn (string $code): string => Hash::make($code), $codes),
            'two_factor_confirmed_at' => now(),
        ])->save();
        Session::forget('auth.2fa_secret');
        AuditService::record('2fa.enabled', $user);

        return redirect()->route('2fa.recovery')->with('recoveryCodes', $codes);
    }

    public function showChallenge(): View|RedirectResponse
    {
        $id = Session::get('auth.2fa_user_id');
        if (! is_int($id) && ! is_string($id)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(TwoFactorVerifyRequest $request): RedirectResponse
    {
        $id = Session::get('auth.2fa_user_id');
        $user = is_int($id) || is_string($id) ? User::find((int) $id) : null;
        if ($user === null) {
            return redirect()->route('login');
        }

        $code = (string) $request->input('code');
        $secret = $user->two_factor_secret === null ? null : decrypt($user->two_factor_secret);
        $valid = is_string($secret) && $this->totp->verify($code, $secret);
        $codes = $user->two_factor_recovery_codes;
        if (! is_array($codes)) {
            $codes = [];
        }

        if (! $valid) {
            foreach ($codes as $index => $hashed) {
                if (Hash::check($code, $hashed)) {
                    unset($codes[$index]);
                    $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                    $valid = true;
                    break;
                }
            }
        }

        if (! $valid) {
            AuditService::record('2fa.failure', $user);

            return back()->withErrors(['code' => __('auth.2fa_invalid')]);
        }

        Auth::login($user);
        Session::forget('auth.2fa_user_id');
        $request->session()->regenerate();
        AuditService::record('2fa.verified', $user);

        return redirect()->intended(route('home'));
    }

    public function showRecovery(): View|RedirectResponse
    {
        $codes = Session::get('recoveryCodes');
        if (! is_array($codes)) {
            return redirect()->route('home');
        }

        return view('auth.two-factor-recovery', ['codes' => $codes]);
    }

    public function disable(TwoFactorVerifyRequest $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return back()->withErrors(['code' => __('auth.2fa_invalid')]);
        }
        $secret = $user->two_factor_secret === null ? null : decrypt((string) $user->two_factor_secret);
        if (! is_string($secret) || ! $this->totp->verify((string) $request->input('code'), $secret)) {
            return back()->withErrors(['code' => __('auth.2fa_invalid')]);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
        AuditService::record('2fa.disabled', $user);

        return redirect()->route('home')->with('status', __('auth.2fa_disabled'));
    }
}
