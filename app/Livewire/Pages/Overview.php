<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasDashboardFilters;
use App\Models\LogEntry;
use App\Services\CampaignMonitor;
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

    #[Url]
    public string $metric = 'leads';

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
        return app(CampaignMonitor::class)->silentCampaigns();
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

    /** Data voor de trendgrafiek (incl. logboek-markers). */
    #[Computed]
    public function chart(): array
    {
        $labels = [];
        $metricSeries = [];

        foreach ($this->dailyStats as $row) {
            $labels[] = CarbonImmutable::parse($row->stat_date)->format('d M');
            $metricSeries[] = round((float) ($row->{$this->metric} ?? 0), 2);
        }

        [$from, $to] = $this->range();
        $annotations = [];
        foreach (LogEntry::whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('entry_date')->get() as $log) {
            $label = CarbonImmutable::parse($log->entry_date)->format('d M');
            if (in_array($label, $labels, true)) {
                $annotations[] = ['x' => $label, 'note' => $log->note];
            }
        }

        return [
            'labels' => $labels,
            'metric' => $this->metric,
            'metricLabel' => $this->metricLabel($this->metric),
            'metricSeries' => $metricSeries,
            'annotations' => $annotations,
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
        return view('livewire.pages.overview')->title(config('app.name'));
    }
}
