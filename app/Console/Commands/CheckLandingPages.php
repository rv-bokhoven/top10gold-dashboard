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

            // Max 2 pogingen: alleen opnieuw proberen bij een verbindings-/
            // time-outfout (transient). Een 404/5xx komt als response binnen en
            // wordt niet herhaald.
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                try {
                    // Alleen de response-headers/status lezen (stream): de body
                    // downloaden we niet, zodat een trage of grote HTML nooit een
                    // body-download-timeout (cURL 28) veroorzaakt — dat gaf vals
                    // "offline"-alarm terwijl de pagina gewoon werkt.
                    $response = Http::connectTimeout(10)
                        ->timeout(20)
                        ->withOptions(['stream' => true])
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; RedTrackDashboard/1.0)'])
                        ->get($url);

                    $status = $response->status();
                    $ok = $response->successful(); // 2xx
                    $error = null;
                    break;
                } catch (Throwable $e) {
                    $error = $e->getMessage();
                    if ($attempt === 1) {
                        usleep(1_000_000); // 1s wachten en één keer opnieuw
                    }
                }
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
