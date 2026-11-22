<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\Provider;
use App\Enums\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class GoogleController
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();
        $email = $googleUser->getEmail();
        $id = (string) $googleUser->getId();

        $verified = (bool) ($googleUser->user['email_verified'] ?? false);
        $domain = $email === null ? '' : mb_strtolower((string) Str::afterLast($email, '@'));
        $allowed = array_map('mb_strtolower', (array) config('ticketing.allowed_email_domains'));

        if ($email === null || ! $verified || ! in_array($domain, $allowed, true)) {
            AuditService::record('google.rejected', null, ['email' => $email, 'reason' => 'domain_or_verification']);

            return redirect()->route('login')->withErrors(['email' => __('auth.google_domain_not_allowed')]);
        }

        $byGoogleId = User::where('google_id', $id)->first();
        if ($byGoogleId !== null && mb_strtolower($byGoogleId->email) !== mb_strtolower($email)) {
            AuditService::record('google.rejected', $byGoogleId, ['reason' => 'linked_to_other_account']);
            Log::warning('Google account mismatch for linked user', ['user_id' => $byGoogleId->id]);

            return redirect()->route('login')->withErrors(['email' => __('auth.failed')]);
        }

        $user = $byGoogleId ?? User::where('email', $email)->first();
        if ($user !== null && $user->google_id !== null && $user->google_id !== $id) {
            AuditService::record('google.rejected', $user, ['reason' => 'already_linked']);

            return redirect()->route('login')->withErrors(['email' => __('auth.failed')]);
        }

        if ($user === null) {
            $user = User::create([
                'name' => $googleUser->getName() ?: mb_strstr($email, '@', true) ?: 'User',
                'email' => mb_strtolower($email),
                'provider' => Provider::Google,
                'google_id' => $id,
                'role' => Role::Requester,
                'is_active' => true,
                'password' => null,
                'email_verified_at' => now(),
            ]);
        } else {
            $user->forceFill(['google_id' => $id, 'email_verified_at' => $user->email_verified_at ?? now()])->save();
        }

        if (! $user->is_active) {
            AuditService::record('login.failure', $user, ['reason' => 'inactive']);

            return redirect()->route('login')->withErrors(['email' => __('auth.failed')]);
        }

        Auth::login($user);
        $user->forceFill(['last_login_at' => now()])->save();
        AuditService::record('login.success', $user, ['provider' => 'google']);
        request()->session()->regenerate();

        if ($user->hasTwoFactor()) {
            Auth::logout();
            request()->session()->put('auth.2fa_user_id', $user->id);

            return redirect()->route('2fa.challenge');
        }

        return redirect()->intended(route('home'));
    }
}
