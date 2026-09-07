@php
    $fmtInt = fn ($v) => number_format((float) $v, 0);
    $fmtPct = fn ($v) => number_format((float) $v * 100, 1).'%';
    $fmtMoney = fn ($v) => $this->money($v, 'USD');

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

<div>
    @include('partials.dashboard-filters')

    {{-- Alert: campaigns with no recent lpclick conversions --}}
    @if (count($this->campaignAlerts))
        <div class="mb-6 rounded-xl border border-rose-300 bg-rose-50 p-4 dark:border-rose-500/30 dark:bg-rose-500/10">
            <div class="flex items-start gap-3">
                <flux:icon icon="exclamation-triangle" class="mt-0.5 size-5 shrink-0 text-rose-600 dark:text-rose-400" />
                <div class="text-sm text-rose-800 dark:text-rose-200">
                    <div class="font-semibold">Warning: campaign(s) with no lpclick conversion in the last {{ config('redtrack.lpclick_alert_hours', 4) }} hours</div>
                    <ul class="mt-1 space-y-0.5">
                        @foreach ($this->campaignAlerts as $alert)
                            <li>
                                <strong>{{ $alert['campaign'] }}</strong> —
                                {{ $alert['last'] ? 'last lpclick '.$alert['last']->diffForHumans() : 'no lpclick yet today' }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- KPI cards (2 rows of 5) --}}
    <div class="mb-4 grid grid-cols-2 gap-2 transition-opacity sm:grid-cols-3 lg:grid-cols-5" wire:loading.class.delay="opacity-40">
        @foreach ($kpis as $kpi)
            @php
                $showDelta = $kpi['delta'] ?? true;
                $delta = $showDelta ? $this->delta($kpi['key']) : null;
            @endphp
            <div class="rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-sm shadow-zinc-950/[0.02] dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-xs font-medium text-zinc-500">{{ $kpi['label'] }}</div>
                <div class="mt-0.5 text-xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $kpi['value'] }}</div>
                @if (! $showDelta)
                    <div class="mt-0.5 text-xs text-zinc-400">&nbsp;</div>
                @elseif ($delta === null)
                    <div class="mt-0.5 text-xs text-zinc-400">no comparison</div>
                @else
                    @php
                        $positive = $delta > 0;
                        $good = $positive === (bool) $kpi['up_is_good'];
                        $neutral = abs($delta) < 0.05;
                    @endphp
                    <div @class([
                        'mt-0.5 inline-flex items-center gap-1 text-xs font-medium',
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
    <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm shadow-zinc-950/[0.02] dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-3 flex items-center justify-between">
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
                async init() {
                    const data = @js($this->chart);
                    const ApexCharts = await window.loadApexCharts();
                    if (!this.$el.isConnected) return;
                    this.chart = new ApexCharts(this.$refs.canvas, this.options(data));
                    this.chart.render();
                },
                destroy() { if (this.chart) this.chart.destroy(); },
                options(data) {
                    const dark = document.documentElement.classList.contains('dark');
                    const accent = dark ? '#ffffff' : '#000000';
                    return {
                        chart: { type: 'area', height: 230, fontFamily: 'inherit', background: 'transparent', toolbar: { show: false }, animations: { enabled: true } },
                        series: [{ name: data.metricLabel, data: data.metricSeries }],
                        xaxis: { categories: data.labels, labels: { style: { colors: '#71717a' } }, axisBorder: { color: dark ? '#27272a' : '#e4e4e7' }, axisTicks: { color: dark ? '#27272a' : '#e4e4e7' } },
                        yaxis: { labels: { style: { colors: '#71717a' } } },
                        colors: [accent],
                        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.03 } },
                        stroke: { curve: 'smooth', width: 2 },
                        dataLabels: { enabled: false },
                        grid: { borderColor: dark ? '#27272a' : '#e4e4e7' },
                        tooltip: { theme: dark ? 'dark' : 'light' },
                        annotations: {
                            xaxis: (data.annotations || []).map(a => ({
                                x: a.x,
                                strokeDashArray: 4,
                                borderColor: dark ? '#a1a1aa' : '#52525b',
                                label: {
                                    text: (a.note.length > 30 ? a.note.slice(0, 30) + '…' : a.note),
                                    orientation: 'horizontal',
                                    style: { fontSize: '10px', color: dark ? '#fafafa' : '#18181b', background: dark ? '#3f3f46' : '#f4f4f5' },
                                },
                            })),
                        },
                    };
                },
            }"
        >
            <div x-ref="canvas"></div>
        </div>
    </div>

    {{-- Monthly overview (all months, independent of the date filter) --}}
    <div class="mt-4 rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm shadow-zinc-950/[0.02] transition-opacity dark:border-zinc-800 dark:bg-zinc-900" wire:loading.class.delay="opacity-40">
        <flux:heading size="lg">Monthly overview</flux:heading>
        <flux:subheading class="mb-4">
            All months{{ $source === 'all' ? '' : ' · '.(config('redtrack.sources.'.$source.'.label') ?? $source) }} — compare performance at a glance
        </flux:subheading>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 text-left text-xs font-medium text-zinc-500 dark:border-zinc-800">
                        <th class="py-2 pr-3">Month</th>
                        <th class="py-2 pr-3 text-right">LP Views</th>
                        <th class="py-2 pr-3 text-right">LP Clicks</th>
                        <th class="py-2 pr-3 text-right">CR</th>
                        <th class="py-2 pr-3 text-right">Leads</th>
                        <th class="py-2 pr-3 text-right">Q-Leads</th>
                        <th class="py-2 pr-3 text-right">Sales</th>
                        <th class="py-2 pr-3 text-right">Revenue</th>
                        <th class="py-2 pr-3 text-right">Cost</th>
                        <th class="py-2 pr-3 text-right">ROI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->monthlyStats as $m)
                        <tr class="border-b border-zinc-100/80 last:border-0 dark:border-zinc-800/60">
                            <td class="py-3 pr-3 font-medium text-zinc-800 dark:text-zinc-200">{{ \Carbon\CarbonImmutable::parse($m->month.'-01')->format('M Y') }}</td>
                            <td class="py-3 pr-3 text-right tabular-nums">{{ $fmtInt($m->lp_views) }}</td>
                            <td class="py-3 pr-3 text-right tabular-nums">{{ $fmtInt($m->lp_clicks) }}</td>
                            <td class="py-3 pr-3 text-right tabular-nums">{{ $fmtPct($m->lp_click_cr) }}</td>
                            <td class="py-3 pr-3 text-right tabular-nums">{{ $fmtInt($m->leads) }}</td>
                            <td class="py-3 pr-3 text-right tabular-nums">{{ $fmtInt($m->qleads) }}</td>
                            <td class="py-3 pr-3 text-right tabular-nums">{{ $fmtInt($m->sales) }}</td>
                            <td class="py-3 pr-3 text-right tabular-nums">{{ $fmtMoney($m->revenue) }}</td>
                            <td class="py-3 pr-3 text-right tabular-nums text-zinc-500">{{ $fmtMoney($m->cost) }}</td>
                            <td @class([
                                'py-2 pr-3 text-right tabular-nums font-semibold',
                                'text-zinc-400' => $m->roi === null,
                                'text-emerald-600 dark:text-emerald-400' => $m->roi !== null && $m->roi >= 0,
                                'text-rose-600 dark:text-rose-400' => $m->roi !== null && $m->roi < 0,
                            ])>{{ $m->roi === null ? '—' : $fmtPct($m->roi) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="py-6 text-center text-zinc-400">No data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
