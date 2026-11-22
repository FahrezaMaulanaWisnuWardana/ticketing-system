<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ThemeController
{
    public function switch(Request $request): RedirectResponse
    {
        $theme = (string) $request->input('theme');
        if (! in_array($theme, ['light', 'dark'], true)) {
            abort(422);
        }

        $request->session()->put('theme', $theme);
        $user = $request->user();
        if ($user instanceof User) {
            $user->forceFill(['theme' => $theme])->save();
        }

        return back();
    }
}
