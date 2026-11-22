<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LocaleController
{
    public function switch(Request $request): RedirectResponse
    {
        $locale = (string) $request->input('locale');
        if (! in_array($locale, ['id', 'en'], true)) {
            abort(422);
        }

        $request->session()->put('locale', $locale);
        if ($request->user() !== null) {
            $request->user()->forceFill(['locale' => $locale])->save();
        }
        app()->setLocale($locale);

        return back();
    }
}
