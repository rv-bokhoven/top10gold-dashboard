<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasDashboardFilters;
use App\Models\OfferStat;
use App\Services\DashboardCache;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class Offers extends Component
{
    use HasDashboardFilters;

    #[Url]
    public string $offerSort = 'lp_clicks';

    #[Url]
    public string $offerDir = 'desc';

    public function sortOffersBy(string $column): void
    {
        if ($this->offerSort === $column) {
            $this->offerDir = $this->offerDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->offerSort = $column;
            $this->offerDir = 'desc';
        }
    }

    /** Per offer opgeteld (excl. de campagne-/landing-rij). */
    #[Computed]
    public function offerStats(): Collection
    {
        [$from, $to] = $this->range();

        $key = implode(':', ['offers', $this->source, $from->toDateString(), $to->toDateString()]);

        $rows = app(DashboardCache::class)->remember($key, function () use ($from, $to) {
            $rows = $this->applySourceFilter(OfferStat::query())
                ->offers()
                ->whereBetween('stat_date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw('offer_id, MAX(offer_title) as offer_title,
                    SUM(lp_clicks) as lp_clicks, SUM(leads) as leads,
                    SUM(qleads) as qleads, SUM(sales) as sales,
                    SUM(conversions) as conversions, SUM(clicks) as clicks,
                    SUM(cost) as cost, SUM(revenue) as revenue')
                ->groupBy('offer_id')
                ->get();

            // Handmatige correcties toepassen vóór de CR-berekening.
            return $this->mergeOfferCorrections($rows, $from, $to)
                ->map(function ($r) {
                    $r->lpclick_to_lead = $r->lp_clicks > 0 ? $r->leads / $r->lp_clicks : 0;
                    $r->lead_to_qlead = $r->leads > 0 ? $r->qleads / $r->leads : 0;
                    $r->cpl = $r->leads > 0 ? $r->cost / $r->leads : 0;

                    return $r;
                });
        });

        return $rows
            ->sortBy(fn ($r) => $r->{$this->offerSort} ?? 0, SORT_REGULAR, $this->offerDir === 'desc')
            ->values();
    }

    public function render()
    {
        return view('livewire.pages.offers')->title('Offers · '.config('app.name'));
    }
}
