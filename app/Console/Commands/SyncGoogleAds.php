<?php

namespace App\Console\Commands;

use App\Models\GoogleAdStat;
use App\Services\GoogleAdsClient;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class SyncGoogleAds extends Command
{
    protected $signature = 'google-ads:sync
        {--from= : Startdatum YYYY-MM-DD}
        {--to= : Einddatum YYYY-MM-DD (standaard vandaag)}
        {--days= : Aantal dagen terugkijken vanaf vandaag}
        {--all : Volledige backfill vanaf redtrack.backfill_from}';

    protected $description = 'Synchroniseer Google Ads ad-stats (campagne/adgroup/ad) naar de lokale database';

    public function handle(GoogleAdsClient $client): int
    {
        [$from, $to] = $this->resolveRange();

        $this->info("Google Ads sync: {$from} t/m {$to}");

        try {
            $rows = $client->reportAdsByDate($from, $to);
            $breakdown = $client->conversionBreakdown($from, $to);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (empty($rows)) {
            $this->warn('Geen data ontvangen voor deze periode.');

            return self::SUCCESS;
        }

        // Conversies per (datum, ad-id) uitgesplitst naar actie → kolom conv_<actie>.
        $actions = config('google_ads.conv_actions');
        $convMap = [];
        foreach ($breakdown as $b) {
            $action = strtolower($b['segments']['conversionActionName'] ?? '');
            if (! in_array($action, $actions, true)) {
                continue;
            }
            $key = ($b['segments']['date'] ?? '').'|'.($b['adGroupAd']['ad']['id'] ?? '');
            $convMap[$key]['conv_'.$action] = ($convMap[$key]['conv_'.$action] ?? 0)
                + (float) ($b['metrics']['conversions'] ?? 0);
        }

        $now = now();
        $records = [];

        foreach ($rows as $row) {
            $ad = $row['adGroupAd']['ad'] ?? [];
            $metrics = $row['metrics'] ?? [];

            $key = ($row['segments']['date'] ?? '').'|'.($ad['id'] ?? '');
            $conv = $convMap[$key] ?? [];

            $records[] = [
                'conv_lpclick' => round($conv['conv_lpclick'] ?? 0, 2),
                'conv_lead' => round($conv['conv_lead'] ?? 0, 2),
                'conv_qlead' => round($conv['conv_qlead'] ?? 0, 2),
                'conv_sale' => round($conv['conv_sale'] ?? 0, 2),
                'stat_date' => $row['segments']['date'],
                'campaign_id' => (string) ($row['campaign']['id'] ?? ''),
                'campaign_name' => $row['campaign']['name'] ?? null,
                'ad_group_id' => (string) ($row['adGroup']['id'] ?? ''),
                'ad_group_name' => $row['adGroup']['name'] ?? null,
                'ad_id' => (string) ($ad['id'] ?? ''),
                'ad_name' => $ad['name'] ?? null,
                'ad_type' => $ad['type'] ?? null,
                'impressions' => (int) ($metrics['impressions'] ?? 0),
                'clicks' => (int) ($metrics['clicks'] ?? 0),
                'cost' => round(((int) ($metrics['costMicros'] ?? 0)) / 1_000_000, 2),
                'conversions' => (float) ($metrics['conversions'] ?? 0),
                'conversions_value' => (float) ($metrics['conversionsValue'] ?? 0),
                'synced_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($records, 500) as $chunk) {
            GoogleAdStat::upsert(
                $chunk,
                ['stat_date', 'ad_id'],
                ['campaign_id', 'campaign_name', 'ad_group_id', 'ad_group_name',
                    'ad_name', 'ad_type', 'impressions', 'clicks', 'cost',
                    'conversions', 'conversions_value', 'conv_lpclick', 'conv_lead',
                    'conv_qlead', 'conv_sale', 'synced_at', 'updated_at'],
            );
        }

        $this->info(count($records).' rijen gesynchroniseerd.');

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveRange(): array
    {
        $today = CarbonImmutable::today();
        $to = $this->option('to') ? CarbonImmutable::parse($this->option('to')) : $today;

        if ($this->option('all')) {
            $from = CarbonImmutable::parse(config('redtrack.backfill_from'));
        } elseif ($this->option('from')) {
            $from = CarbonImmutable::parse($this->option('from'));
        } else {
            $days = (int) ($this->option('days') ?: 3);
            $from = $today->subDays(max($days - 1, 0));
        }

        return [$from->toDateString(), $to->toDateString()];
    }
}
