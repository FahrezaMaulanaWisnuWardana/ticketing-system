<?php

declare(strict_types=1);

namespace App\Providers;

use App\Integrations\Contracts\IntegrationAdapter;
use App\Integrations\Trello\TrelloAdapter;
use App\Models\Comment;
use App\Models\Ticket;
use App\Observers\CommentObserver;
use App\Observers\TicketObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        if ($this->app->isProduction()) {
            Model::handleLazyLoadingViolationUsing(static function (Model $model, string $relation): void {
                Log::warning('Lazy loading violation', ['model' => $model::class, 'relation' => $relation]);
            });
        }

        $this->app->bind(IntegrationAdapter::class, TrelloAdapter::class);

        Ticket::observe(TicketObserver::class);
        Comment::observe(CommentObserver::class);

        RateLimiter::for('login', static fn (Request $request): Limit => Limit::perMinute(5)
            ->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('password-reset', static fn (Request $request): Limit => Limit::perHour(3)->by((string) $request->ip()));
        RateLimiter::for('invite-resend', static fn (Request $request): Limit => Limit::perHour(3)->by((string) $request->ip()));
        RateLimiter::for('comment', static fn (Request $request): Limit => Limit::perMinute(10)->by((string) $request->user()?->id));
        RateLimiter::for('upload', static fn (Request $request): Limit => Limit::perMinute(20)->by((string) $request->user()?->id));
        RateLimiter::for('webhooks', static fn (Request $request): Limit => Limit::perMinute(120)->by((string) $request->ip()));
    }
}
