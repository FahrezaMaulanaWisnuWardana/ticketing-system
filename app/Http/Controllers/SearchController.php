<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

final class SearchController
{
    public function __invoke(): View
    {
        return view('search.index');
    }
}
