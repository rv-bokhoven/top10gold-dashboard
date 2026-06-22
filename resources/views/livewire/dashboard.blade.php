@php
    use Carbon\CarbonImmutable;
    [$rangeFrom, $rangeTo] = $this->range();

    $fmtInt = fn ($v) => number_format((float) $v, 0);
    $fmtMoney = fn ($v) => '$'.number_format((float) $v, 2);
    $fmtPct = fn ($v) => number_format((float) $v * 100, 1).'%';

    $t = $this->totals;
    $kpis = [
        ['key' => 'lp_views', 'label' => 'LP Views', 'value' => $fmtInt($t['lp_views']), 'up_is_good' => true],
        ['key' => 'lp_clicks', 'label' => 'LP Clicks', 'value' => $fmtInt($t['lp_clicks']), 'up_is_good' => true],
        ['key' => 'lp_click_cr', 'label' => 'LP Click CR', 'value' => $fmtPct($t['lp_click_cr']), 'up_is_good' => true],
        ['key' => 'lpclick_to_lead', 'label' => 'LPClick→Lead CR', 'value' => $fmtPct($t['lpclick_to_lead']), 'up_is_good' => true],
        ['key' => 'leads', 'label' => 'Leads', 'value' => $fmtInt($t['leads']), 'up_is_good' => true],
        ['key' => 'qleads', 'label' => 'Q-Leads', 'value' => $fmtInt($t['qleads']), 'up_is_good' => true],
        ['key' => 'sales', 'label' => 'Sales', 'value' => $fmtInt($t['sales']), 'up_is_good' => true],
        ['key' => 'revenue', 'label' => 'Revenue', 'value' => $fmtMoney($t['revenue']), 'up_is_good' => true],
        ['key' => 'roi', 'label' => 'ROI', 'value' => $t['roi'] === null ? '—' : $fmtPct($t['roi']), 'up_is_good' => true, 'delta' => false],
        ['key' => 'cost', 'label' => 'Cost', 'value' => $fmtMoney($t['cost']), 'up_is_good' => false],
    ];
@endphp

<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ config('app.name') }}</flux:heading>
            <flux:subheading>
                Google traffic ({{ config('redtrack.rt_source') }}) ·
                {{ CarbonImmutable::parse($rangeFrom)->format('d M Y') }} – {{ CarbonImmutable::parse($rangeTo)->format('d M Y') }}
            </flux:subheading>
        </div>

        <div class="flex items-center gap-3">
            @if ($this->syncedAt)
                <span class="text-xs text-zinc-500">
                    {{ $lastSyncMessage ?? 'Last updated '.$this->syncedAt->diffForHumans() }}
                </span>
            @endif

            <flux:button wire:click="refreshData" wire:loading.attr="disabled" icon="arrow-path" size="sm">
                <span wire:loading.remove wire:target="refreshData">Refresh</span>
                <span wire:loading wire:target="refreshData">Refreshing…</span>
            </flux:button>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button type="submit" variant="subtle" size="sm" icon="arrow-right-start-on-rectangle">Sign out</flux:button>
            </form>
        </div>
    </div>

    {{-- Period selector --}}
    <div class="mb-6 flex flex-wrap items-center gap-2">
        <flux:select wire:model.live="period" size="sm" class="max-w-48">
            @foreach (\App\Livewire\Dashboard::PERIODS as $value => $label)
                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        @if ($period === 'custom')
            <div class="flex items-center gap-2">
                <flux:input type="date" wire:model.live="from" size="sm" />
                <span class="text-zinc-400">–</span>
                <flux:input type="date" wire:model.live="to" size="sm" />
            </div>
        @endif
    </div>

    {{-- KPI cards (2 rows of 5) --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($kpis as $kpi)
            @php
                $showDelta = $kpi['delta'] ?? true;
                $delta = $showDelta ? $this->delta($kpi['key']) : null;
            @endphp
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ $kpi['label'] }}</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $kpi['value'] }}</div>
                @if (! $showDelta)
                    <div class="mt-1 text-xs text-zinc-400">&nbsp;</div>
                @elseif ($delta === null)
                    <div class="mt-1 text-xs text-zinc-400">no comparison</div>
                @else
                    @php
                        $positive = $delta > 0;
                        $good = $positive === (bool) $kpi['up_is_good'];
                        $neutral = abs($delta) < 0.05;
                    @endphp
                    <div @class([
                        'mt-1 inline-flex items-center gap-1 text-xs font-medium',
                        'text-zinc-400' => $neutral,
                        'text-emerald-600 dark:text-emerald-400' => ! $neutral && $good,
                        'text-rose-600 dark:text-rose-400' => ! $neutral && ! $good,
                    ])>
                        @unless ($neutral)
                            <span>{{ $positive ? '▲' : '▼' }}</span>
                        @endunless
                        {{ number_format(abs($delta), 1) }}%
                        <span class="text-zinc-400">vs previous</span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Trend chart --}}
    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading size="lg">Daily trend</flux:heading>
            <flux:select wire:model.live="metric" size="sm" class="max-w-44">
                <flux:select.option value="leads">Leads</flux:select.option>
                <flux:select.option value="sales">Sales</flux:select.option>
                <flux:select.option value="lp_clicks">LP Clicks</flux:select.option>
                <flux:select.option value="lp_views">LP Views</flux:select.option>
                <flux:select.option value="revenue">Revenue</flux:select.option>
                <flux:select.option value="cost">Cost</flux:select.option>
            </flux:select>
        </div>

        <div
            wire:key="chart-{{ $period }}-{{ $from }}-{{ $to }}-{{ $metric }}"
            x-data="{
                chart: null,
                init() {
                    const data = @js($this->chart);
                    this.chart = new ApexCharts(this.$refs.canvas, this.options(data));
                    this.chart.render();
                },
                destroy() { if (this.chart) this.chart.destroy(); },
                options(data) {
                    const dark = document.documentElement.classList.contains('dark');
                    const accent = dark ? '#ffffff' : '#000000';
                    return {
                        chart: { type: 'area', height: 300, fontFamily: 'inherit', background: 'transparent', toolbar: { show: false }, animations: { enabled: true } },
                        series: [{ name: data.metricLabel, data: data.metricSeries }],
                        xaxis: { categories: data.labels, labels: { style: { colors: '#71717a' } }, axisBorder: { color: dark ? '#27272a' : '#e4e4e7' }, axisTicks: { color: dark ? '#27272a' : '#e4e4e7' } },
                        yaxis: { labels: { style: { colors: '#71717a' } } },
                        colors: [accent],
                        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.03 } },
                        stroke: { curve: 'smooth', width: 2 },
                        dataLabels: { enabled: false },
                        grid: { borderColor: dark ? '#27272a' : '#e4e4e7' },
                        tooltip: { theme: dark ? 'dark' : 'light' },
                    };
                },
            }"
        >
            <div x-ref="canvas"></div>
        </div>
    </div>

    {{-- Tables stacked, full width --}}
    <div class="space-y-6">
        {{-- By offer --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">By offer</flux:heading>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-800">
                            <th class="py-2 pr-3">Offer</th>
                            @foreach ([
                                'lp_clicks' => 'LP Clicks',
                                'leads' => 'Leads',
                                'qleads' => 'Q-Leads',
                                'sales' => 'Sales',
                                'revenue' => 'Revenue',
                                'lpclick_to_lead' => 'LPClick→Lead',
                                'lead_to_qlead' => 'Lead→Q-Lead',
                            ] as $col => $label)
                                <th class="cursor-pointer py-2 pr-3 text-right select-none hover:text-zinc-700 dark:hover:text-zinc-300"
                                    wire:click="sortOffersBy('{{ $col }}')">
                                    {{ $label }}
                                    @if ($offerSort === $col){{ $offerDir === 'asc' ? '↑' : '↓' }}@endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->offerStats as $offer)
                            <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800/60">
                                <td class="py-2 pr-3 font-medium text-zinc-800 dark:text-zinc-200">{{ $offer->offer_title ?? '—' }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtInt($offer->lp_clicks) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtInt($offer->leads) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtInt($offer->qleads) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtInt($offer->sales) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtMoney($offer->revenue) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums font-semibold text-zinc-900 dark:text-white">{{ $fmtPct($offer->lpclick_to_lead) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums font-semibold text-zinc-900 dark:text-white">{{ $fmtPct($offer->lead_to_qlead) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-6 text-center text-zinc-400">No data for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- By day --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">By day</flux:heading>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-800">
                            <th class="py-2 pr-3">Date</th>
                            <th class="py-2 pr-3 text-right">Views</th>
                            <th class="py-2 pr-3 text-right">Clicks</th>
                            <th class="py-2 pr-3 text-right">CR</th>
                            <th class="py-2 pr-3 text-right">Leads</th>
                            <th class="py-2 pr-3 text-right">Q-Leads</th>
                            <th class="py-2 pr-3 text-right">Sales</th>
                            <th class="py-2 pr-3 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->dailyStats->sortByDesc('stat_date') as $day)
                            @php $cr = $day->lp_views > 0 ? $day->lp_clicks / $day->lp_views : 0; @endphp
                            <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800/60">
                                <td class="py-2 pr-3 font-medium text-zinc-800 dark:text-zinc-200">{{ CarbonImmutable::parse($day->stat_date)->format('d M') }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtInt($day->lp_views) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtInt($day->lp_clicks) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtPct($cr) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtInt($day->leads) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtInt($day->qleads) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtInt($day->sales) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtMoney($day->revenue) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-6 text-center text-zinc-400">No data for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
