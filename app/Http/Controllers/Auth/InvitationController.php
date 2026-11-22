<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\SetPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetPasswordRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

final class InvitationController extends Controller
{
    public function edit(int $user): View|RedirectResponse
    {
        if (! URL::hasValidSignature(request())) {
            abort(403, __('auth.invite_invalid'));
        }

        $subject = User::findOrFail($user);
        if ($subject->password !== null) {
            return redirect()->route('login')->with('status', __('auth.invite_already_used'));
        }

        $actionUrl = URL::temporarySignedRoute(
            'invitations.update',
            now()->addHours((int) config('ticketing.invite_ttl_hours')),
            ['user' => $subject->id],
        );

        return view('auth.accept-invite', ['invited' => $subject, 'actionUrl' => $actionUrl]);
    }

    public function update(SetPasswordRequest $request, int $user, SetPassword $setPassword): RedirectResponse
    {
        if (! URL::hasValidSignature(request())) {
            abort(403, __('auth.invite_invalid'));
        }

        $subject = User::findOrFail($user);
        if ($subject->password !== null) {
            return redirect()->route('login')->with('status', __('auth.invite_already_used'));
        }

        $setPassword($subject, (string) $request->input('password'));

        return redirect()->route('login')->with('status', __('auth.invite_accepted'));
    }
}
