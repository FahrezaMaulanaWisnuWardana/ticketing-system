<?php

declare(strict_types=1);

use App\Jobs\CheckSlaJob;
use App\Jobs\CheckSystemHealthJob;
use App\Jobs\CheckTrelloWebhooksJob;
use App\Jobs\PruneOldDataJob;
use App\Jobs\ReconcileTrelloJob;
use App\Jobs\TakeSprintSnapshotsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new CheckSlaJob)->everyMinute()->onOneServer()->withoutOverlapping();
Schedule::job(new TakeSprintSnapshotsJob)->dailyAt('23:55')->onOneServer()->withoutOverlapping();
Schedule::job(new CheckSystemHealthJob)->everyFiveMinutes()->onOneServer()->withoutOverlapping();
Schedule::job(new PruneOldDataJob)->weekly()->onOneServer()->withoutOverlapping();

if (config('trello.enabled')) {
    Schedule::job(new ReconcileTrelloJob)->dailyAt('02:00')->onOneServer()->withoutOverlapping();
    Schedule::job(new CheckTrelloWebhooksJob)->dailyAt('03:00')->onOneServer()->withoutOverlapping();
}

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
