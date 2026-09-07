<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasDashboardFilters;
use App\Models\OfferStat;
use App\Services\DashboardCache;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class OfferDetail extends Component
{
    use HasDashboardFilters;

    public string $offerId;

    public function mount(string $offerId): void
    {
        $this->offerId = $offerId;
    }

    #[Computed]
    public function offerTitle(): string
    {
        $key = 'offer-title:'.$this->source.':'.$this->offerId;

        return app(DashboardCache::class)->remember($key, function () {
            $title = $this->applySourceFilter(OfferStat::query())
                ->offers()
                ->where('offer_id', $this->offerId)
                ->max('offer_title');

            return $title ?: 'Offer';
        });
    }

    /** Dagelijkse, gecorrigeerde funnelcijfers van één offer. */
    #[Computed]
    public function dailyOfferStats(): Collection
    {
        [$from, $to] = $this->range();
        $key = implode(':', [
            'offer-days',
            $this->source,
            $this->offerId,
            $from->toDateString(),
            $to->toDateString(),
        ]);

        $cached = app(DashboardCache::class)->remember($key, function () use ($from, $to) {
            $rows = $this->applySourceFilter(OfferStat::query())
                ->offers()
                ->where('offer_id', $this->offerId)
                ->whereBetween('stat_date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw('stat_date, MAX(offer_title) as offer_title,
                    SUM(lp_clicks) as lp_clicks, SUM(leads) as leads,
                    SUM(qleads) as qleads, SUM(sales) as sales,
                    SUM(conversions) as conversions, SUM(revenue) as revenue')
                ->groupBy('stat_date')
                ->orderBy('stat_date')
                ->get();

            $rows = $this->mergeOfferDateCorrections($rows, $from, $to, $this->offerId)
                ->map(function ($row) {
                    $row->lpclick_to_lead = $row->lp_clicks > 0 ? $row->leads / $row->lp_clicks : 0;
                    $row->lead_to_qlead = $row->leads > 0 ? $row->qleads / $row->leads : 0;

                    return $row;
                });

            return $this->serializeStatRows($rows);
        });

        return $this->restoreStatRows($cached);
    }

    #[Computed]
    public function offerTotals(): array
    {
        $sum = fn (string $field) => (float) $this->dailyOfferStats->sum($field);
        $lpClicks = $sum('lp_clicks');
        $leads = $sum('leads');

        return [
            'lp_clicks' => $lpClicks,
            'leads' => $leads,
            'qleads' => $sum('qleads'),
            'sales' => $sum('sales'),
            'revenue' => $sum('revenue'),
            'lpclick_to_lead' => $lpClicks > 0 ? $leads / $lpClicks : 0,
            'lead_to_qlead' => $leads > 0 ? $sum('qleads') / $leads : 0,
        ];
    }

    public function render()
    {
        return view('livewire.pages.offer-detail')->title($this->offerTitle.' · Offers · '.config('app.name'));
    }
}
