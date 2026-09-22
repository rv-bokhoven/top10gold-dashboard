@php
    $t = $this->totals;

    $roiFmt = fn ($r) => $r === null ? '—' : (($r >= 0 ? '+' : '−').number_format(abs($r) * 100, 0, ',', '.').'%');

    $kpis = [
        ['label' => 'LP views', 'value' => $this->nlInt($t['lp_views']), 'delta' => $this->delta('lp_views'), 'invert' => false],
        ['label' => 'LP clicks', 'value' => $this->nlInt($t['lp_clicks']), 'delta' => $this->delta('lp_clicks'), 'invert' => false,
            'extra' => $this->nlPct($t['lp_click_cr']), 'extraLabel' => 'CTR'],
        ['label' => 'Leads', 'value' => $this->nlInt($t['leads']), 'delta' => $this->delta('leads'), 'invert' => false,
            'extra' => $this->nlPct($t['lpclick_to_lead']), 'extraLabel' => 'O→L', 'extraTitle' => 'Outclick → Lead'],
        ['label' => 'Qualified', 'value' => $this->nlInt($t['qleads']), 'delta' => $this->delta('qleads'), 'invert' => false],
        ['label' => 'Cost', 'value' => $this->money($t['cost'], 'USD'), 'delta' => $this->delta('cost'), 'invert' => true],
        ['label' => 'Revenue', 'value' => $this->money($t['revenue'], 'USD'), 'delta' => $this->delta('revenue'), 'invert' => false,
            'extra' => $roiFmt($t['roi']), 'extraLabel' => 'ROI', 'extraTone' => $t['roi'] === null ? null : ($t['roi'] >= 0 ? 'pos' : 'neg')],
    ];

    $deltaView = function ($d, $invert = false) {
        if ($d === null) {
            return ['text' => 'geen vergelijking', 'tone' => 'muted', 'arrow' => ''];
        }
        $val = abs($d) < 0.5 ? 0.0 : $d;
        $up = $val > 0;
        $tone = $val == 0.0 ? 'muted' : (($invert ? ! $up : $up) ? 'pos' : 'neg');

        return [
            'text' => number_format(abs($val), 0, ',', '.').'%',
            'arrow' => $val == 0.0 ? '' : ($up ? '▲' : '▼'),
            'tone' => $tone,
        ];
    };

    // Funnel-geometrie uit de shares.
    $funnel = $this->funnel;
    $n = count($funnel);
    $W = 1000; $H = 240; $cy = $H / 2; $colW = $W / $n;
    $centers = []; $hh = [];
    foreach ($funnel as $i => $s) {
        $centers[$i] = $colW * ($i + 0.5);
        $hh[$i] = max(18, pow(max((float) $s['share'], 0), 0.48) * 104);
    }
    $smooth = fn ($x) => $x * $x * (3 - 2 * $x);
    $halfAt = function ($x) use ($centers, $hh, $n, $smooth) {
        if ($x <= $centers[0]) {
            $tt = $centers[0] > 0 ? $x / $centers[0] : 1;
            return $hh[0] * (1.06 - 0.06 * $smooth($tt));
        }
        if ($x >= $centers[$n - 1]) {
            return $hh[$n - 1];
        }
        for ($i = 0; $i < $n - 1; $i++) {
            if ($x >= $centers[$i] && $x <= $centers[$i + 1]) {
                $local = ($x - $centers[$i]) / ($centers[$i + 1] - $centers[$i]);
                return $hh[$i] + ($hh[$i + 1] - $hh[$i]) * $smooth($local);
            }
        }
        return $hh[$n - 1];
    };
    $samples = 96;
    $top = []; $bot = []; $inTop = []; $inBot = [];
    for ($k = 0; $k <= $samples; $k++) {
        $x = round($W * $k / $samples, 1);
        $v = $halfAt($x);
        $top[] = $x.' '.round($cy - $v, 1);
        $bot[] = $x.' '.round($cy + $v, 1);
        $inTop[] = $x.' '.round($cy - $v * 0.72, 1);
        $inBot[] = $x.' '.round($cy + $v * 0.72, 1);
    }
    $mkPath = fn ($tp, $bt) => 'M '.implode(' L ', $tp).' L '.implode(' L ', array_reverse($bt)).' Z';
    $outerPath = $mkPath($top, $bot);
    $innerPath = $mkPath($inTop, $inBot);
    $pillFmt = fn ($share) => ($share * 100 >= 1 ? number_format($share * 100, 0, ',', '.') : number_format($share * 100, 1, ',', '.')).'%';

    $tabs = ['revenue' => 'Omzet & kosten', 'traffic' => 'Traffic', 'conversion' => 'Conversie'];
    $chart = $this->chart;
@endphp

<div class="flex flex-col gap-4 lg:gap-5">
    @include('partials.dashboard-filters')

    {{-- Campaign alert --}}
    @if (count($this->campaignAlerts))
        <div class="rounded-lg bg-surface p-4">
            <div class="flex items-start gap-3 text-sm text-negative">
                <span class="mt-0.5 font-medium">Waarschuwing:</span>
                <div>
                    <span class="text-fg">campagne(s) zonder lpclick-conversie in de laatste {{ config('redtrack.lpclick_alert_hours', 4) }} uur:</span>
                    <ul class="mt-1 space-y-0.5 text-muted">
                        @foreach ($this->campaignAlerts as $alert)
                            <li><strong class="text-fg">{{ $alert['campaign'] }}</strong> — {{ $alert['last'] ? 'laatste lpclick '.\Carbon\CarbonImmutable::parse($alert['last'])->locale('nl')->diffForHumans() : 'nog geen lpclick vandaag' }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- KPI's --}}
    <div class="grid grid-cols-2 gap-2 transition-opacity lg:grid-cols-6" wire:loading.class.delay="opacity-40">
        @foreach ($kpis as $kpi)
            @php $dv = $deltaView($kpi['delta'], $kpi['invert']); @endphp
            <div class="rounded-md bg-surface px-3 py-2.5">
                <div class="flex items-start justify-between gap-2">
                    <span class="text-xs text-muted">{{ $kpi['label'] }}</span>
                    @isset($kpi['extra'])
                        <span class="shrink-0 text-right" @isset($kpi['extraTitle']) title="{{ $kpi['extraTitle'] }}" @endisset>
                            <span @class([
                                'text-sm font-semibold tabular-nums',
                                'text-positive' => ($kpi['extraTone'] ?? null) === 'pos',
                                'text-negative' => ($kpi['extraTone'] ?? null) === 'neg',
                                'text-fg' => ! isset($kpi['extraTone']) || $kpi['extraTone'] === null,
                            ])>{{ $kpi['extra'] }}</span>
                            <span class="ml-0.5 text-xs font-medium text-subtle">{{ $kpi['extraLabel'] }}</span>
                        </span>
                    @endisset
                </div>
                <div class="mt-0.5 text-sm font-medium tabular-nums text-fg sm:text-base">{{ $kpi['value'] }}</div>
                <div @class([
                    'mt-0.5 inline-flex items-center gap-1 text-xs',
                    'text-subtle' => $dv['tone'] === 'muted',
                    'text-positive' => $dv['tone'] === 'pos',
                    'text-negative' => $dv['tone'] === 'neg',
                ])>
                    @if ($dv['arrow'])<span>{{ $dv['arrow'] }}</span>@endif
                    <span class="tabular-nums">{{ $dv['text'] }}</span>
                    @if ($kpi['delta'] !== null)<span class="text-subtle">vs vorige</span>@endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Verloop + Funnel --}}
    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Verloop --}}
        <div class="rounded-lg bg-surface p-4 sm:p-5">
            <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-medium text-fg">Verloop</h2>
                    <p class="text-xs text-subtle">{{ $chart['note'] ?? 'Per dag in de geselecteerde periode' }}</p>
                </div>
                <div class="inline-flex h-8 shrink-0 items-center rounded-md bg-bg p-0.5">
                    @foreach ($tabs as $key => $label)
                        <button type="button" wire:click="$set('trendTab', '{{ $key }}')"
                            @class([
                                'inline-flex h-7 items-center rounded-[5px] px-2.5 text-xs transition',
                                'bg-elevated text-fg' => $trendTab === $key,
                                'text-muted hover:text-fg' => $trendTab !== $key,
                            ])>{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div wire:key="chart-{{ $trendTab }}-{{ $period }}-{{ $from }}-{{ $to }}-{{ $source }}-{{ $currency }}"
                x-data="{
                    chart: null,
                    async init() {
                        const d = @js($chart);
                        const ApexCharts = await window.loadApexCharts();
                        if (! this.$el.isConnected) return;
                        this.chart = new ApexCharts(this.$refs.canvas, this.options(d));
                        this.chart.render();
                    },
                    destroy() { if (this.chart) this.chart.destroy(); },
                    fmtNum(v) { return new Intl.NumberFormat('nl-NL').format(Math.round(v)); },
                    options(d) {
                        const sym = d.currencySymbol;
                        const yfmt = (v) => d.money ? sym + ' ' + this.fmtNum(v) : this.fmtNum(v);
                        const axisLabels = { style: { colors: '#8a8a84', fontSize: '11px' } };
                        const yaxis = d.dualAxis
                            ? [
                                { seriesName: d.series[0].name, labels: { ...axisLabels, formatter: (v) => this.fmtNum(v) } },
                                { seriesName: d.series[1].name, opposite: true, labels: { ...axisLabels, formatter: (v) => this.fmtNum(v) } },
                              ]
                            : { labels: { ...axisLabels, formatter: yfmt } };
                        return {
                            chart: { type: 'area', height: '100%', fontFamily: 'inherit', background: 'transparent', toolbar: { show: false } },
                            series: d.series,
                            colors: d.colors,
                            xaxis: { categories: d.labels, tickAmount: 6, labels: { ...axisLabels, rotate: 0, hideOverlappingLabels: true }, axisBorder: { show: false }, axisTicks: { show: false }, tooltip: { enabled: false } },
                            yaxis,
                            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: [0.14, 0.10], opacityTo: 0, stops: [0, 100] } },
                            stroke: { curve: 'smooth', width: 1.75 },
                            dataLabels: { enabled: false },
                            markers: { size: 0 },
                            legend: { show: false },
                            grid: { borderColor: '#e2e1db', xaxis: { lines: { show: false } }, yaxis: { lines: { show: true } }, padding: { left: 4, right: 4 } },
                            tooltip: { theme: 'light', y: { formatter: (v) => d.money ? sym + ' ' + this.fmtNum(v) : this.fmtNum(v) } },
                            annotations: { xaxis: (d.annotations || []).map(a => ({ x: a.x, strokeDashArray: 4, borderColor: '#cfcfc8', label: { text: (a.note.length > 26 ? a.note.slice(0, 26) + '…' : a.note), orientation: 'horizontal', style: { fontSize: '10px', color: '#1c1c1a', background: '#e8e7e1' } } })) },
                        };
                    },
                }">
                <div x-ref="canvas" class="h-64 sm:h-72"></div>
            </div>

            <div class="mt-2 flex items-center gap-4 text-xs text-muted">
                @foreach ($chart['series'] as $i => $serie)
                    <span class="inline-flex items-center gap-1.5">
                        <span class="size-2 rounded-full" style="background: {{ $chart['colors'][$i] ?? '#2a2a28' }}"></span>
                        {{ $serie['name'] }}
                    </span>
                @endforeach
            </div>
        </div>

        {{-- Funnel --}}
        <div class="rounded-lg bg-surface p-4 sm:p-5">
            <div class="mb-3">
                <h2 class="text-base font-medium text-fg">Funnel</h2>
                <p class="text-xs text-subtle">Van LP view tot qualified lead</p>
            </div>

            <div class="grid grid-cols-4 text-center text-sm font-semibold tabular-nums text-fg">
                @foreach ($funnel as $s)<div>{{ $this->compactCount($s['value']) }}</div>@endforeach
            </div>

            <div class="relative my-2 h-64 sm:h-72">
                <svg viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="none" class="absolute inset-0 h-full w-full">
                    @for ($i = 1; $i < $n; $i++)
                        <line x1="{{ $colW * $i }}" y1="0" x2="{{ $colW * $i }}" y2="{{ $H }}" stroke="#e2e1db" stroke-width="1" vector-effect="non-scaling-stroke" />
                    @endfor
                    <path d="{{ $outerPath }}" fill="#1c1c1a" fill-opacity="0.22" />
                    <path d="{{ $innerPath }}" fill="#1c1c1a" fill-opacity="0.10" />
                </svg>
                <div class="absolute inset-0 grid grid-cols-4 items-center">
                    @foreach ($funnel as $s)
                        <div class="flex justify-center">
                            <span class="rounded-full bg-surface px-2.5 py-1 text-xs font-medium tabular-nums text-fg shadow-border">{{ $pillFmt($s['share']) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-4 text-center text-xs text-muted">
                @foreach ($funnel as $s)<div>{{ $s['label'] }}</div>@endforeach
            </div>
        </div>
    </div>

    {{-- Maandoverzicht --}}
    <div class="rounded-lg bg-surface p-4 sm:p-5 transition-opacity" wire:loading.class.delay="opacity-40">
        <h2 class="text-base font-medium text-fg">Maandoverzicht</h2>
        <p class="mb-4 text-xs text-subtle">Alle statistieken per maand, ongeacht de periodefilter</p>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-sm">
                @php
                    $cols = [
                        'month' => 'Maand', 'lp_views' => 'LP views', 'lp_clicks' => 'LP clicks',
                        'lp_click_cr' => 'LP CTR', 'lpclick_to_lead' => 'Outclick → Lead', 'leads' => 'Leads',
                        'qleads' => 'Qualified', 'cost' => 'Cost', 'revenue' => 'Revenue', 'roi' => 'ROI',
                    ];
                @endphp
                <thead>
                    <tr class="border-b border-border text-left text-xs text-subtle">
                        @foreach ($cols as $key => $label)
                            <th @class(['py-2 pr-3 font-normal select-none', 'pr-0' => $loop->last])>
                                <button type="button" wire:click="sortMonthsBy('{{ $key }}')"
                                    @class(['inline-flex items-center gap-1 hover:text-fg', 'w-full justify-end' => ! $loop->first])>
                                    {{ $label }}
                                    @if ($monthSort === $key)<span class="text-fg">{{ $monthDir === 'asc' ? '↑' : '↓' }}</span>@endif
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->monthlyStats as $m)
                        <tr class="border-b border-border/60 last:border-0">
                            <td class="py-2 pr-3 font-medium text-fg">{{ \Carbon\CarbonImmutable::parse($m->month.'-01')->locale('nl')->isoFormat('MMMM YYYY') }}</td>
                            <td class="py-2 pr-3 text-right tabular-nums text-muted">{{ $this->nlInt($m->lp_views) }}</td>
                            <td class="py-2 pr-3 text-right tabular-nums text-muted">{{ $this->nlInt($m->lp_clicks) }}</td>
                            <td class="py-2 pr-3 text-right tabular-nums text-muted">{{ $this->nlPct($m->lp_click_cr) }}</td>
                            <td class="py-2 pr-3 text-right tabular-nums text-muted">{{ $this->nlPct($m->lpclick_to_lead) }}</td>
                            <td class="py-2 pr-3 text-right tabular-nums text-fg">{{ $this->nlInt($m->leads) }}</td>
                            <td class="py-2 pr-3 text-right tabular-nums text-fg">{{ $this->nlInt($m->qleads) }}</td>
                            <td class="py-2 pr-3 text-right tabular-nums text-muted">{{ $this->money($m->cost, 'USD') }}</td>
                            <td class="py-2 pr-3 text-right tabular-nums text-fg">{{ $this->money($m->revenue, 'USD') }}</td>
                            <td @class(['py-2 text-right tabular-nums font-medium', 'text-positive' => $m->roi !== null && $m->roi >= 0, 'text-negative' => $m->roi !== null && $m->roi < 0, 'text-subtle' => $m->roi === null])>{{ $roiFmt($m->roi) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="py-6 text-center text-subtle">Nog geen data.</td></tr>
                    @endforelse
                </tbody>
                @if ($this->monthlyStats->isNotEmpty())
                    @php $mt = $this->monthlyTotals; @endphp
                    <tfoot>
                        <tr class="border-t border-border-strong text-fg">
                            <td class="py-2 pr-3 font-semibold">Totaal</td>
                            <td class="py-2 pr-3 text-right font-semibold tabular-nums">{{ $this->nlInt($mt['lp_views']) }}</td>
                            <td class="py-2 pr-3 text-right font-semibold tabular-nums">{{ $this->nlInt($mt['lp_clicks']) }}</td>
                            <td class="py-2 pr-3 text-right font-semibold tabular-nums">{{ $this->nlPct($mt['lp_click_cr']) }}</td>
                            <td class="py-2 pr-3 text-right font-semibold tabular-nums">{{ $this->nlPct($mt['lpclick_to_lead']) }}</td>
                            <td class="py-2 pr-3 text-right font-semibold tabular-nums">{{ $this->nlInt($mt['leads']) }}</td>
                            <td class="py-2 pr-3 text-right font-semibold tabular-nums">{{ $this->nlInt($mt['qleads']) }}</td>
                            <td class="py-2 pr-3 text-right font-semibold tabular-nums">{{ $this->money($mt['cost'], 'USD') }}</td>
                            <td class="py-2 pr-3 text-right font-semibold tabular-nums">{{ $this->money($mt['revenue'], 'USD') }}</td>
                            <td @class(['py-2 text-right font-semibold tabular-nums', 'text-positive' => $mt['roi'] !== null && $mt['roi'] >= 0, 'text-negative' => $mt['roi'] !== null && $mt['roi'] < 0, 'text-subtle' => $mt['roi'] === null])>{{ $roiFmt($mt['roi']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
