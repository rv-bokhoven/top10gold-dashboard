<?php

namespace App\Livewire;

use App\Models\GoogleAdStat;
use App\Models\LandingPage;
use App\Models\OfferStat;
use App\Services\RedTrackClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public const PERIODS = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last_7' => 'Last 7 days',
        'this_week' => 'This week',
        'last_week' => 'Last week',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'custom' => 'Custom',
    ];

    #[Url]
    public string $period = 'last_7';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    #[Url]
    public string $metric = 'leads';

    #[Url]
    public string $offerSort = 'lp_clicks';

    #[Url]
    public string $offerDir = 'desc';

    public ?string $lastSyncMessage = null;

    public function mount(): void
    {
        $this->from ??= CarbonImmutable::today()->subDays(6)->toDateString();
        $this->to ??= CarbonImmutable::today()->toDateString();
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }

    public function sortOffersBy(string $column): void
    {
        if ($this->offerSort === $column) {
            $this->offerDir = $this->offerDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->offerSort = $column;
            $this->offerDir = 'desc';
        }
    }

    public function refreshData(): void
    {
        Artisan::call('redtrack:sync');
        unset($this->dailyStats, $this->offerStats, $this->totals, $this->previousTotals, $this->syncedAt);
        $this->lastSyncMessage = 'Bijgewerkt om '.now()->format('H:i');
        $this->dispatch('stats-refreshed');
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    public function range(): array
    {
        $today = CarbonImmutable::today();

        return match ($this->period) {
            'today' => [$today, $today],
            'yesterday' => [$today->subDay(), $today->subDay()],
            'last_7' => [$today->subDays(6), $today],
            'this_week' => [$today->startOfWeek(), $today],
            'last_week' => [$today->subWeek()->startOfWeek(), $today->subWeek()->endOfWeek()],
            'this_month' => [$today->startOfMonth(), $today],
            'last_month' => [$today->subMonth()->startOfMonth(), $today->subMonth()->endOfMonth()],
            default => [
                CarbonImmutable::parse($this->from ?: $today->toDateString()),
                CarbonImmutable::parse($this->to ?: $today->toDateString()),
            ],
        };
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    protected function previousRange(): array
    {
        [$from, $to] = $this->range();
        $days = $from->diffInDays($to) + 1;

        return [$from->subDays($days), $from->subDay()];
    }

    /** Opgetelde dag-rijen (per stat_date) binnen de periode. */
    #[Computed]
    public function dailyStats(): Collection
    {
        [$from, $to] = $this->range();

        return $this->aggregateByDate($from, $to);
    }

    #[Computed]
    public function totals(): array
    {
        return $this->sumRows($this->dailyStats);
    }

    #[Computed]
    public function previousTotals(): array
    {
        [$from, $to] = $this->previousRange();

        return $this->sumRows($this->aggregateByDate($from, $to));
    }

    /** Per offer opgeteld (excl. de campagne-/landing-rij). */
    #[Computed]
    public function offerStats(): Collection
    {
        [$from, $to] = $this->range();

        $rows = OfferStat::query()
            ->offers()
            ->whereBetween('stat_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('offer_id, MAX(offer_title) as offer_title,
                SUM(lp_clicks) as lp_clicks, SUM(leads) as leads,
                SUM(qleads) as qleads, SUM(sales) as sales,
                SUM(conversions) as conversions, SUM(clicks) as clicks,
                SUM(cost) as cost, SUM(revenue) as revenue')
            ->groupBy('offer_id')
            ->get()
            ->map(function ($r) {
                $r->lpclick_to_lead = $r->lp_clicks > 0 ? $r->leads / $r->lp_clicks : 0;
                $r->lead_to_qlead = $r->leads > 0 ? $r->qleads / $r->leads : 0;
                $r->cpl = $r->leads > 0 ? $r->cost / $r->leads : 0;

                return $r;
            });

        return $rows
            ->sortBy(fn ($r) => $r->{$this->offerSort} ?? 0, SORT_REGULAR, $this->offerDir === 'desc')
            ->values();
    }

    /** Google Ads-totalen voor de periode. */
    #[Computed]
    public function googleAdsTotals(): array
    {
        [$from, $to] = $this->range();

        $row = GoogleAdStat::query()
            ->whereBetween('stat_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('SUM(impressions) as impressions, SUM(clicks) as clicks,
                SUM(cost) as cost, SUM(conversions) as conversions')
            ->first();

        $impressions = (int) ($row->impressions ?? 0);
        $clicks = (int) ($row->clicks ?? 0);
        $cost = (float) ($row->cost ?? 0);
        $conversions = (float) ($row->conversions ?? 0);

        return [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => $impressions > 0 ? $clicks / $impressions : 0,
            'cost' => $cost,
            'cpc' => $clicks > 0 ? $cost / $clicks : 0,
            'conversions' => $conversions,
            'cpa' => $conversions > 0 ? $cost / $conversions : 0,
        ];
    }

    /** Google Ads opgeteld per campagne. */
    #[Computed]
    public function googleAdsByCampaign(): Collection
    {
        return $this->googleAdsGrouped('campaign_id', ['campaign_name']);
    }

    /**
     * @param  array<int, string>  $labels
     */
    protected function googleAdsGrouped(string $groupBy, array $labels): Collection
    {
        [$from, $to] = $this->range();

        $labelSelects = collect($labels)
            ->map(fn ($c) => "MAX({$c}) as {$c}")
            ->implode(', ');

        return GoogleAdStat::query()
            ->whereBetween('stat_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("{$groupBy}, {$labelSelects},
                SUM(impressions) as impressions, SUM(clicks) as clicks,
                SUM(cost) as cost, SUM(conversions) as conversions")
            ->groupBy($groupBy)
            ->get()
            ->map(function ($r) {
                $r->ctr = $r->impressions > 0 ? $r->clicks / $r->impressions : 0;
                $r->cpc = $r->clicks > 0 ? $r->cost / $r->clicks : 0;
                $r->cpa = $r->conversions > 0 ? $r->cost / $r->conversions : 0;

                return $r;
            })
            ->sortByDesc('cost')
            ->values();
    }

    /**
     * Actieve campagnes die langer dan de drempel geen lpclick-conversie hadden.
     *
     * @return array<int, array{campaign: string, last: ?CarbonImmutable, hours: ?int}>
     */
    #[Computed]
    public function campaignAlerts(): array
    {
        $hours = (int) config('redtrack.lpclick_alert_hours', 4);
        $minDaily = (int) config('redtrack.lpclick_alert_min_daily', 10);

        try {
            $records = app(RedTrackClient::class)->conversions(
                CarbonImmutable::today()->subDay()->toDateString(),
                CarbonImmutable::tomorrow()->toDateString(),
            );
        } catch (\Throwable $e) {
            return []; // RedTrack tijdelijk niet bereikbaar → geen alert
        }

        // Per Google-campagne (sub6): aantal lpclicks + laatste tijdstip (laatste 24u).
        $cutoff = CarbonImmutable::now()->subDay();
        $byCampaign = [];

        foreach ($records as $c) {
            if (($c['type'] ?? null) !== 'lpclick') {
                continue;
            }
            $cid = (string) ($c['sub6'] ?? '');
            $time = $c['created_at'] ?? null;
            if ($cid === '' || ! $time) {
                continue;
            }
            $ts = CarbonImmutable::parse($time);
            if ($ts->lessThan($cutoff)) {
                continue;
            }
            $byCampaign[$cid]['count'] = ($byCampaign[$cid]['count'] ?? 0) + 1;
            if (! isset($byCampaign[$cid]['last']) || $ts->greaterThan($byCampaign[$cid]['last'])) {
                $byCampaign[$cid]['last'] = $ts;
            }
        }

        $names = GoogleAdStat::query()->whereNotNull('campaign_name')->pluck('campaign_name', 'campaign_id');
        $now = CarbonImmutable::now();
        $alerts = [];

        foreach ($byCampaign as $cid => $data) {
            if ($data['count'] < $minDaily) {
                continue; // te weinig volume om betrouwbaar te bewaken
            }
            $hoursSince = (int) abs($now->diffInHours($data['last']));
            if ($hoursSince >= $hours) {
                $alerts[] = [
                    'campaign' => $names[$cid] ?? ('Campaign '.$cid),
                    'last' => $data['last'],
                    'hours' => $hoursSince,
                ];
            }
        }

        return $alerts;
    }

    /** Landingspagina's van de live ads + hun online-status. */
    #[Computed]
    public function landingPages(): Collection
    {
        return LandingPage::orderBy('ok')->orderBy('url')->get();
    }

    public function checkLandingPages(): void
    {
        Artisan::call('landing-pages:check');
        unset($this->landingPages);
        $this->dispatch('landing-pages-checked');
    }

    #[Computed]
    public function syncedAt(): ?CarbonImmutable
    {
        $value = OfferStat::max('synced_at');

        return $value ? CarbonImmutable::parse($value) : null;
    }

    protected function aggregateByDate(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return OfferStat::query()
            ->whereBetween('stat_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('stat_date,
                SUM(lp_views) as lp_views, SUM(lp_clicks) as lp_clicks,
                SUM(clicks) as clicks, SUM(leads) as leads,
                SUM(qleads) as qleads, SUM(sales) as sales,
                SUM(conversions) as conversions, SUM(cost) as cost,
                SUM(revenue) as revenue')
            ->groupBy('stat_date')
            ->orderBy('stat_date')
            ->get();
    }

    protected function sumRows(Collection $rows): array
    {
        $sum = fn (string $key) => (float) $rows->sum($key);

        $lpViews = $sum('lp_views');
        $lpClicks = $sum('lp_clicks');
        $leads = $sum('leads');
        $cost = $sum('cost');
        $revenue = $sum('revenue');

        return [
            'lp_views' => $lpViews,
            'lp_clicks' => $lpClicks,
            'lp_click_cr' => $lpViews > 0 ? $lpClicks / $lpViews : 0,
            'lpview_to_lead' => $lpViews > 0 ? $leads / $lpViews : 0,
            'leads' => $leads,
            'qleads' => $sum('qleads'),
            'sales' => $sum('sales'),
            'conversions' => $sum('conversions'),
            'cost' => $cost,
            'revenue' => $revenue,
            'profit' => $revenue - $cost,
            'roi' => $cost > 0 ? ($revenue - $cost) / $cost : null,
            'cpl' => $leads > 0 ? $cost / $leads : 0,
            'lpclick_to_lead' => $lpClicks > 0 ? $leads / $lpClicks : 0,
        ];
    }

    /** Procentuele verandering t.o.v. de vorige periode. */
    public function delta(string $key): ?float
    {
        $now = $this->totals[$key] ?? 0;
        $prev = $this->previousTotals[$key] ?? 0;

        if ($prev == 0.0) {
            return $now == 0.0 ? 0.0 : null; // null = geen vergelijking mogelijk
        }

        return ($now - $prev) / $prev * 100;
    }

    /** Data voor de trendgrafiek. */
    #[Computed]
    public function chart(): array
    {
        $labels = [];
        $metricSeries = [];
        $lpClicksSeries = [];

        foreach ($this->dailyStats as $row) {
            $labels[] = CarbonImmutable::parse($row->stat_date)->format('d M');
            $metricSeries[] = round((float) ($row->{$this->metric} ?? 0), 2);
            $lpClicksSeries[] = (int) $row->lp_clicks;
        }

        return [
            'labels' => $labels,
            'metric' => $this->metric,
            'metricLabel' => $this->metricLabel($this->metric),
            'metricSeries' => $metricSeries,
            'lpClicksSeries' => $lpClicksSeries,
        ];
    }

    public function metricLabel(string $metric): string
    {
        return match ($metric) {
            'lp_views' => 'LP Views',
            'lp_clicks' => 'LP Clicks',
            'leads' => 'Leads',
            'sales' => 'Sales',
            'revenue' => 'Revenue',
            'cost' => 'Cost',
            default => ucfirst($metric),
        };
    }

    public function render()
    {
        return view('livewire.dashboard')->title(config('app.name'));
    }
}
