<?php

use App\Http\Middleware\EnsureDashboardAuth;
use App\Livewire\Login;
use App\Livewire\Pages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/login', Login::class)->name('login');

// Sync-endpoint voor cron (Vercel Cron stuurt automatisch "Authorization: Bearer
// <CRON_SECRET>"; een externe cron-dienst kan ?token=<secret> meegeven).
Route::get('/cron/sync', function (Request $request) {
    $secret = (string) config('redtrack.cron_secret');
    $provided = (string) ($request->bearerToken() ?: $request->query('token', ''));

    abort_unless($secret !== '' && hash_equals($secret, $provided), 403);

    Artisan::call('redtrack:sync');
    $output = trim(Artisan::output());

    Artisan::call('fx:update');
    $output .= "\n".trim(Artisan::output());

    // Google Ads alleen meesyncen als de credentials zijn ingesteld.
    if (filled(config('google_ads.developer_token'))) {
        Artisan::call('google-ads:sync');
        $output .= "\n".trim(Artisan::output());

        Artisan::call('landing-pages:check');
        $output .= "\n".trim(Artisan::output());
    }

    return response()->json([
        'ok' => true,
        'output' => $output,
    ]);
})->name('cron.sync');

// Telegram-meldingen (nieuwe conversies + campagne-waarschuwingen). Roep dit
// elk uur aan via een externe cron met ?token=<CRON_SECRET>.
Route::get('/cron/notify', function (Request $request) {
    $secret = (string) config('redtrack.cron_secret');
    $provided = (string) ($request->bearerToken() ?: $request->query('token', ''));

    abort_unless($secret !== '' && hash_equals($secret, $provided), 403);

    Artisan::call('notifications:run');

    return response()->json([
        'ok' => true,
        'output' => trim(Artisan::output()),
    ]);
})->name('cron.notify');

Route::post('/logout', function () {
    session()->forget(EnsureDashboardAuth::SESSION_KEY);
    session()->regenerate();

    return redirect()->route('login');
})->name('logout');

Route::middleware('dashboard.auth')->group(function () {
    Route::get('/', Pages\Overview::class)->name('dashboard');
    Route::get('/offers', Pages\Offers::class)->name('offers');
    Route::get('/google-ads', Pages\GoogleAds::class)->name('google-ads');
    Route::get('/landing-pages', Pages\LandingPages::class)->name('landing-pages');
    Route::get('/logbook', Pages\Logbook::class)->name('logbook');
});
