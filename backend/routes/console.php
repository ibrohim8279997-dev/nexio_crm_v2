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
// Heartbeat proves cron is alive; the health check flags a stale stamp.
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::put(\App\Services\HealthChecks::HEARTBEAT_KEY, time(), 900))->everyMinute();
Schedule::command('nexio:backup')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('nexio:accounts:purge')->dailyAt('04:00')->withoutOverlapping();
Schedule::command('nexio:health:alert')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('nexio:limits:check')->hourly()->withoutOverlapping();
// Standby database (REPLICA_ENABLED=true): near-live copy every 5 minutes, full row-by-row check nightly.
if (config('replica.enabled')) {
    Schedule::command('nexio:db:replicate')->everyFiveMinutes()->withoutOverlapping();
    Schedule::command('nexio:db:replicate --verify')->dailyAt('03:30')->withoutOverlapping();
}
// Page-view rows are kept 24 months (stated in the Privacy Policy).
Schedule::call(fn () => \Illuminate\Support\Facades\DB::table('site_visits')->where('visit_day', '<', gmdate('Y-m-d', strtotime('-24 months')))->delete())->dailyAt('04:30');
