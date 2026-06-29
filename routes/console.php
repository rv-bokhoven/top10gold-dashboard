<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Elk uur de laatste dagen verversen (vangt ook late conversie-attributie op).
// Let op: op Vercel draait de scheduler niet; daar verzorgt /cron/sync dit.
Schedule::command('redtrack:sync')->hourly()->withoutOverlapping();

// Google Ads alleen inplannen als de credentials zijn ingesteld.
if (filled(config('google_ads.developer_token'))) {
    Schedule::command('google-ads:sync')->hourly()->withoutOverlapping();
    Schedule::command('landing-pages:check')->hourly()->withoutOverlapping();
}
