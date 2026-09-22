<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasDashboardFilters;
use App\Models\LogEntry;
use App\Models\OfferStat;
use App\Services\CampaignMonitor;
use App\Services\DashboardCache;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class Overview extends Component
{
    use HasDashboardFilters;

    /** Verloop-tab: revenue (Omzet & kosten), traffic, conversion. */
    #[Url]
    public string $trendTab = 'revenue';

    #[Url]
    public string $monthSort = 'month';

    #[Url]
    public string $monthDir = 'desc';

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

    /** Actieve campagnes die langer dan de drempel geen lpclick-conversie hadden. */
    #[Computed]
    public function campaignAlerts(): array
    {
        return app(CampaignMonitor::class)->storedAlerts();
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
            return $now == 0.0 ? 0.0 : null;
        }

        return ($now - $prev) / $prev * 100;
    }

    /** Verschil in procentpunten (voor rates: CTR, O→L, ROI). */
    public function deltaPp(string $key): ?float
    {
        $now = $this->totals[$key];
        $prev = $this->previousTotals[$key];

        if ($now === null || $prev === null) {
            return null;
        }

        return ((float) $now - (float) $prev) * 100;
    }

    /**
     * Funnel-stages LP views → LP clicks → Leads → Qualified, met aandeel van
     * LP views. De SVG-vorm wordt in de view opgebouwd uit deze shares.
     */
    #[Computed]
    public function funnel(): array
    {
        $t = $this->totals;
        $lpViews = (float) $t['lp_views'];

        $stages = [
            ['label' => 'LP views', 'value' => $lpViews],
            ['label' => 'LP clicks', 'value' => (float) $t['lp_clicks']],
            ['label' => 'Leads', 'value' => (float) $t['leads']],
            ['label' => 'Qualified', 'value' => (float) $t['qleads']],
        ];

        return array_map(function ($s) use ($lpViews) {
            $s['share'] = $lpViews > 0 ? $s['value'] / $lpViews : 0;

            return $s;
        }, $stages);
    }

    /** Data voor de verloopgrafiek: per tab twee series + grain-padding. */
    #[Computed]
    public function chart(): array
    {
        [$from, $to] = $this->range();
        $span = $from->diffInDays($to) + 1;

        $note = null;
        if ($span < 8) {
            $chartTo = $to;
            $chartFrom = $to->subDays(13);
            $grain = 'day';
            $note = 'Trend over 14 dagen — cijfers hierboven blijven op de gekozen periode';
        } elseif ($span > 90) {
            [$chartFrom, $chartTo, $grain] = [$from, $to, 'week'];
        } else {
            [$chartFrom, $chartTo, $grain] = [$from, $to, 'day'];
        }

        $buckets = [];
        if ($grain === 'day') {
            for ($d = $chartFrom; $d->lessThanOrEqualTo($chartTo); $d = $d->addDay()) {
                $buckets[$d->toDateString()] = $this->emptyBucket($this->chartLabel($d));
            }
        }

        foreach ($this->aggregateByDate($chartFrom, $chartTo) as $row) {
            $date = CarbonImmutable::parse((string) $row->stat_date);
            $anchor = $grain === 'week' ? $date->startOfWeek() : $date;
            $key = $anchor->toDateString();

            $buckets[$key] ??= $this->emptyBucket($this->chartLabel($anchor));

            foreach (['lp_views', 'lp_clicks', 'leads', 'qleads', 'cost', 'revenue'] as $f) {
                $buckets[$key][$f] += (float) ($row->{$f} ?? 0);
            }
        }

        ksort($buckets);
        $ordered = array_values($buckets);
        $labels = array_column($ordered, 'label');

        $col = fn (string $f) => array_map(fn ($b) => round($b[$f], 2), $ordered);
        $money = fn (string $f) => array_map(fn ($b) => round($this->moneyValue($b[$f]), 2), $ordered);

        [$series, $dualAxis, $isMoney] = match ($this->trendTab) {
            'traffic' => [[
                ['name' => 'LP views', 'data' => $col('lp_views')],
                ['name' => 'LP clicks', 'data' => $col('lp_clicks')],
            ], true, false],
            'conversion' => [[
                ['name' => 'Leads', 'data' => $col('leads')],
                ['name' => 'Qualified', 'data' => $col('qleads')],
            ], false, false],
            default => [[
                ['name' => 'Revenue', 'data' => $money('revenue')],
                ['name' => 'Cost', 'data' => $money('cost')],
            ], false, true],
        };

        $annotations = [];
        foreach (LogEntry::whereBetween('entry_date', [$chartFrom->toDateString(), $chartTo->toDateString()])
            ->orderBy('entry_date')->get() as $log) {
            $anchor = CarbonImmutable::parse($log->entry_date);
            $label = $this->chartLabel($grain === 'week' ? $anchor->startOfWeek() : $anchor);
            if (in_array($label, $labels, true)) {
                $annotations[] = ['x' => $label, 'note' => $log->note];
            }
        }

        return [
            'labels' => $labels,
            'series' => $series,
            'colors' => ['#2a2a28', '#8a8a84'],
            'dualAxis' => $dualAxis,
            'money' => $isMoney,
            'currencySymbol' => $this->currency === 'EUR' ? '€' : '$',
            'tab' => $this->trendTab,
            'note' => $note,
            'annotations' => $annotations,
        ];
    }

    protected function emptyBucket(string $label): array
    {
        return [
            'label' => $label, 'lp_views' => 0, 'lp_clicks' => 0,
            'leads' => 0, 'qleads' => 0, 'cost' => 0, 'revenue' => 0,
        ];
    }

    protected function chartLabel(CarbonImmutable $date): string
    {
        return $date->locale('nl')->isoFormat('D MMM');
    }

    public function sortMonthsBy(string $column): void
    {
        if ($this->monthSort === $column) {
            $this->monthDir = $this->monthDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->monthSort = $column;
            $this->monthDir = $column === 'month' ? 'desc' : 'desc';
        }
    }

    /**
     * Stats per maand over ALLE maanden (los van de periode-filter), met source-
     * filter en handmatige correcties. Voor snelle maand-op-maand vergelijking.
     */
    #[Computed]
    public function monthlyStats(): Collection
    {
        $cached = app(DashboardCache::class)->remember('monthly:'.$this->source, function () {
            $min = $this->applySourceFilter(OfferStat::query())->min('stat_date');

            if (! $min) {
                return [];
            }

            $from = CarbonImmutable::parse($min)->startOfMonth();
            $to = CarbonImmutable::today();

            return $this->aggregateByDate($from, $to)
                ->groupBy(fn ($r) => CarbonImmutable::parse((string) $r->stat_date)->format('Y-m'))
                ->map(function (Collection $rows, string $month) {
                    $sum = fn (string $k) => (float) $rows->sum($k);

                    $lpViews = $sum('lp_views');
                    $lpClicks = $sum('lp_clicks');
                    $leads = $sum('leads');
                    $cost = $sum('cost');
                    $revenue = $sum('revenue');

                    return [
                        'month' => $month,
                        'lp_views' => $lpViews,
                        'lp_clicks' => $lpClicks,
                        'lp_click_cr' => $lpViews > 0 ? $lpClicks / $lpViews : 0,
                        'lpclick_to_lead' => $lpClicks > 0 ? $leads / $lpClicks : 0,
                        'leads' => $leads,
                        'qleads' => $sum('qleads'),
                        'cost' => $cost,
                        'revenue' => $revenue,
                        'roi' => $cost > 0 ? ($revenue - $cost) / $cost : null,
                    ];
                })
                ->values()
                ->all();
        });

        return collect($cached)
            ->sortBy(fn (array $r) => $r[$this->monthSort] ?? 0, SORT_REGULAR, $this->monthDir === 'desc')
            ->map(fn (array $row) => (object) $row)
            ->values();
    }

    /** Totalen over de hele historie van de geselecteerde source (footer). */
    #[Computed]
    public function monthlyTotals(): array
    {
        $rows = $this->monthlyStats;

        $lpViews = (float) $rows->sum('lp_views');
        $lpClicks = (float) $rows->sum('lp_clicks');
        $leads = (float) $rows->sum('leads');
        $cost = (float) $rows->sum('cost');
        $revenue = (float) $rows->sum('revenue');

        return [
            'lp_views' => $lpViews,
            'lp_clicks' => $lpClicks,
            'lp_click_cr' => $lpViews > 0 ? $lpClicks / $lpViews : 0,
            'lpclick_to_lead' => $lpClicks > 0 ? $leads / $lpClicks : 0,
            'leads' => $leads,
            'qleads' => (float) $rows->sum('qleads'),
            'cost' => $cost,
            'revenue' => $revenue,
            'roi' => $cost > 0 ? ($revenue - $cost) / $cost : null,
        ];
    }

    public function render()
    {
        return view('livewire.pages.overview')->title(config('app.name'));
    }
}
