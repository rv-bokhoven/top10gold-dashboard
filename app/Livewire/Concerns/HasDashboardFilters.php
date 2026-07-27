<?php

namespace App\Livewire\Concerns;

use App\Models\OfferStat;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * Gedeelde filter-state en helpers voor alle dashboard-pagina's (datumrange,
 * valuta, geldweergave, RedTrack-sync). Via de URL-query blijven de filters
 * behouden bij navigeren tussen pagina's.
 */
trait HasDashboardFilters
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
    public string $currency = 'USD';

    public ?string $lastSyncMessage = null;

    public function mountHasDashboardFilters(): void
    {
        $this->from ??= CarbonImmutable::today()->subDays(6)->toDateString();
        $this->to ??= CarbonImmutable::today()->toDateString();
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

    /** Actuele EUR→USD-koers (USD per 1 EUR). */
    public function fxRate(): float
    {
        return (float) Setting::get('fx.eur_usd', config('currency.eur_usd_fallback', 1.08));
    }

    /**
     * Geldweergave in de gekozen valuta. Bronwaarden zijn "native" USD (RedTrack)
     * of EUR (Google Ads); die rekenen we om.
     */
    public function money($value, string $native = 'USD'): string
    {
        $value = (float) $value;
        $rate = $this->fxRate();

        if ($native !== $this->currency && $rate > 0) {
            $value = $native === 'EUR' ? $value * $rate : $value / $rate;
        }

        $symbol = $this->currency === 'EUR' ? '€' : '$';

        return $symbol.number_format($value, 2);
    }

    public function refreshData(): void
    {
        Artisan::call('redtrack:sync');
        $this->lastSyncMessage = 'Updated at '.now()->format('H:i');
        $this->dispatch('stats-refreshed');
    }

    #[Computed]
    public function syncedAt(): ?CarbonImmutable
    {
        $value = OfferStat::max('synced_at');

        return $value ? CarbonImmutable::parse($value) : null;
    }

    /** Opgetelde dag-rijen (per stat_date) binnen de periode. */
    #[Computed]
    public function dailyStats(): Collection
    {
        [$from, $to] = $this->range();

        return $this->aggregateByDate($from, $to);
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
}
