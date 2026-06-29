<?php

namespace App\Console\Commands;

use App\Models\LandingPage;
use App\Services\GoogleAdsClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class CheckLandingPages extends Command
{
    protected $signature = 'landing-pages:check';

    protected $description = 'Haal de landingspagina\'s van de live ads op en controleer of ze online zijn';

    public function handle(GoogleAdsClient $client): int
    {
        try {
            $pages = $client->liveLandingPages();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        // Per URL de campagnes die hem gebruiken verzamelen.
        $byUrl = collect($pages)
            ->groupBy('url')
            ->map(fn ($g) => $g->pluck('campaign')->unique()->sort()->implode(', '));

        $now = now();
        $seen = [];

        foreach ($byUrl as $url => $campaigns) {
            $status = null;
            $ok = false;
            $error = null;

            try {
                $response = Http::timeout(15)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; RedTrackDashboard/1.0)'])
                    ->get($url);
                $status = $response->status();
                $ok = $response->successful(); // 2xx
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }

            $hash = sha1($url);
            $seen[] = $hash;

            LandingPage::updateOrCreate(
                ['url_hash' => $hash],
                ['url' => $url, 'campaigns' => $campaigns, 'status_code' => $status,
                    'ok' => $ok, 'error' => $error, 'checked_at' => $now],
            );

            $this->line(($ok ? '<info>OK </info>' : '<error>BAD</error>')." {$status} {$url}");
        }

        // Pagina's die niet meer in live ads zitten opruimen.
        LandingPage::whereNotIn('url_hash', $seen)->delete();

        $this->info($byUrl->count().' landingspagina\'s gecontroleerd.');

        return self::SUCCESS;
    }
}
