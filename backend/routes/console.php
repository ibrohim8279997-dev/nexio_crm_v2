<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run every minute in production (cron: * * * * * php artisan schedule:run).
Schedule::command('nexio:billing:renew')->hourly()->withoutOverlapping();
Schedule::command('nexio:webhooks:retry')->everyMinute()->withoutOverlapping();
Schedule::job(new \App\Jobs\ExpireStaleQuotes())->dailyAt('01:00');
