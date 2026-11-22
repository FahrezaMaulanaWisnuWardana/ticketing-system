<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

final class HorizonServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Horizon::auth(static fn (Request $request): bool => $request->user() instanceof User && $request->user()->isAdmin());
    }
}
