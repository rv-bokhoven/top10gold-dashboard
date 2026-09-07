<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasDashboardFilters;
use App\Models\GoogleAdStat;
use App\Services\DashboardCache;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class GoogleAds extends Component
{
    use HasDashboardFilters;

    /** Google Ads-totalen voor de periode. */
    #[Computed]
    public function googleAdsTotals(): array
    {
        [$from, $to] = $this->range();

        $key = implode(':', ['google-totals', $from->toDateString(), $to->toDateString()]);

        return app(DashboardCache::class)->remember($key, function () use ($from, $to) {
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
        });
    }

    /** Google Ads opgeteld per campagne. */
    #[Computed]
    public function googleAdsByCampaign(): Collection
    {
        [$from, $to] = $this->range();

        $key = implode(':', ['google-campaigns', $from->toDateString(), $to->toDateString()]);

        $cached = app(DashboardCache::class)->remember($key, function () use ($from, $to) {
            return GoogleAdStat::query()
                ->whereBetween('stat_date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw('campaign_id, MAX(campaign_name) as campaign_name,
                    SUM(impressions) as impressions, SUM(clicks) as clicks,
                    SUM(cost) as cost, SUM(conversions) as conversions,
                    SUM(conv_lpclick) as conv_lpclick, SUM(conv_lead) as conv_lead,
                    SUM(conv_qlead) as conv_qlead, SUM(conv_sale) as conv_sale')
                ->groupBy('campaign_id')
                ->get()
                ->map(function ($r) {
                    $impressions = (int) $r->impressions;
                    $clicks = (int) $r->clicks;
                    $cost = (float) $r->cost;
                    $conversions = (float) $r->conversions;

                    return [
                        'campaign_id' => (string) $r->campaign_id,
                        'campaign_name' => $r->campaign_name,
                        'impressions' => $impressions,
                        'clicks' => $clicks,
                        'cost' => $cost,
                        'conversions' => $conversions,
                        'conv_lpclick' => (float) $r->conv_lpclick,
                        'conv_lead' => (float) $r->conv_lead,
                        'conv_qlead' => (float) $r->conv_qlead,
                        'conv_sale' => (float) $r->conv_sale,
                        'ctr' => $impressions > 0 ? $clicks / $impressions : 0,
                        'cpc' => $clicks > 0 ? $cost / $clicks : 0,
                        'cpa' => $conversions > 0 ? $cost / $conversions : 0,
                    ];
                })
                ->sortByDesc('cost')
                ->values()
                ->all();
        });

        return collect($cached)->map(fn (array $row) => (object) $row)->values();
    }

    public function render()
    {
        return view('livewire.pages.google-ads')->title('Google Ads · '.config('app.name'));
    }
}
