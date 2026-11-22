<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

final class PasswordResetController extends Controller
{
    public function showForgot(): View
    {
        return view('auth.forgot-password');
    }

    public function sendLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $status = Password::broker()->sendResetLink($request->only('email'));
        AuditService::record('password.reset_requested', null, ['email' => (string) $request->input('email')]);

        return $status === Password::ResetLinkSent
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showReset(string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => request()->string('email')->toString(),
        ]);
    }

    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            static function ($user, string $password): void {
                $user->forceFill(['password' => $password])->save();
                AuditService::record('password.reset', $user);
            },
        );

        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
