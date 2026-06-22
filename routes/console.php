<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Elk uur de laatste dagen verversen (vangt ook late conversie-attributie op).
Schedule::command('redtrack:sync')->hourly()->withoutOverlapping();
