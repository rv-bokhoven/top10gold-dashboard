<?php

namespace App\Console\Commands;

use App\Models\OfferStat;
use App\Services\RedTrackClient;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class SyncRedTrack extends Command
{
    protected $signature = 'redtrack:sync
        {--from= : Startdatum YYYY-MM-DD}
        {--to= : Einddatum YYYY-MM-DD (standaard vandaag)}
        {--days= : Aantal dagen terugkijken vanaf vandaag}
        {--all : Volledige backfill vanaf redtrack.backfill_from}
        {--fresh : Verwijder bestaande rijen in het bereik eerst (schone re-sync)}';

    protected $description = 'Synchroniseer RedTrack-stats (rt_source=Google) naar de lokale database';

    public function handle(RedTrackClient $client): int
    {
        [$from, $to] = $this->resolveRange();

        $this->info("RedTrack sync: {$from} t/m {$to} (rt_source=".config('redtrack.rt_source').', per source)');

        try {
            $rows = $client->reportByDateSource($from, $to);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (empty($rows)) {
            $this->warn('Geen data ontvangen voor deze periode.');

            return self::SUCCESS;
        }

        // source-naam (bijv. "BingAds") -> canonieke key (bijv. "bing").
        $sourceMap = collect(config('redtrack.sources'))
            ->mapWithKeys(fn ($cfg, $key) => [$cfg['match'] => $key]);

        $map = config('redtrack.conv_types');
        $sumCols = fn (array $row, string $type) => collect((array) ($map[$type] ?? []))
            ->sum(fn ($col) => (int) ($row[$col] ?? 0));
        $now = now();
        $records = [];
        $skipped = 0;

        foreach ($rows as $row) {
            $sourceKey = $sourceMap[$row['source'] ?? ''] ?? null;
            if ($sourceKey === null) {
                $skipped++;

                continue;
            }

            $offerId = ($row['offer_id'] ?? '') ?: OfferStat::CAMPAIGN;

            $leads = $sumCols($row, 'lead');
            $qleads = $sumCols($row, 'qlead');
            $sales = $sumCols($row, 'sale');

            $records[] = [
                'stat_date' => $row['date'],
                'offer_id' => $offerId,
                'source' => $sourceKey,
                'offer_title' => ($row['offer'] ?? '') ?: null,
                'lp_views' => (int) ($row['lp_views'] ?? 0),
                'lp_clicks' => (int) ($row['lp_clicks'] ?? 0),
                'clicks' => (int) ($row['clicks'] ?? 0),
                'leads' => $leads,
                'qleads' => $qleads,
                'sales' => $sales,
                'conversions' => $leads + $qleads + $sales,
                'cost' => (float) ($row['cost'] ?? 0),
                'revenue' => (float) ($row[config('redtrack.revenue_field')] ?? 0),
                'synced_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // --fresh: maak het bereik eerst leeg zodat oude (vervuilde) rijen zonder
        // per-source-splitsing niet als spookrijen achterblijven.
        if ($this->option('fresh')) {
            $deleted = OfferStat::whereBetween('stat_date', [$from, $to])->delete();
            $this->info("Fresh: {$deleted} bestaande rijen in {$from}..{$to} verwijderd.");
        }

        // Upsert: bestaande (datum, offer, source)-rijen worden bijgewerkt, nieuwe toegevoegd.
        foreach (array_chunk($records, 500) as $chunk) {
            OfferStat::upsert(
                $chunk,
                ['stat_date', 'offer_id', 'source'],
                ['offer_title', 'lp_views', 'lp_clicks', 'clicks', 'leads',
                    'qleads', 'sales', 'conversions', 'cost', 'revenue',
                    'synced_at', 'updated_at'],
            );
        }

        $this->info(count($records).' rijen gesynchroniseerd.'.($skipped ? " ({$skipped} rijen zonder bekende source overgeslagen.)" : ''));

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
            $days = (int) ($this->option('days') ?: config('redtrack.sync_days', 3));
            $from = $today->subDays(max($days - 1, 0));
        }

        return [$from->toDateString(), $to->toDateString()];
    }
}
