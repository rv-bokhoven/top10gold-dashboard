@php
    $fmtInt = fn ($value) => number_format((float) $value, 0);
    $fmtPct = fn ($value) => number_format((float) $value * 100, 1).'%';
    $fmtMoney = fn ($value) => $this->money($value, 'USD');
    $totals = $this->offerTotals;
    $backQuery = http_build_query(array_filter([
        'period' => $period,
        'from' => $from,
        'to' => $to,
        'currency' => $currency,
        'source' => $source,
    ], fn ($value) => $value !== null && $value !== ''));
@endphp

<div>
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <a href="{{ route('offers').($backQuery ? '?'.$backQuery : '') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-zinc-500 transition hover:text-zinc-900 dark:hover:text-white">
                <flux:icon icon="arrow-left" class="size-4" />
                Offers
            </a>
            <flux:heading size="xl" class="mt-2">{{ $this->offerTitle }}</flux:heading>
            <flux:subheading>Daily offer performance and funnel quality</flux:subheading>
        </div>
    </div>

    @include('partials.dashboard-filters')

    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6" wire:loading.class.delay="opacity-40">
        @foreach ([
            ['LP Clicks', $fmtInt($totals['lp_clicks'])],
            ['Leads', $fmtInt($totals['leads'])],
            ['Q-Leads', $fmtInt($totals['qleads'])],
            ['Sales', $fmtInt($totals['sales'])],
            ['LPClick → Lead', $fmtPct($totals['lpclick_to_lead'])],
            ['Revenue', $fmtMoney($totals['revenue'])],
        ] as [$label, $value])
            <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm shadow-zinc-950/[0.02] dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-xs font-medium text-zinc-500">{{ $label }}</div>
                <div class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-sm shadow-zinc-950/[0.02] dark:border-zinc-800 dark:bg-zinc-900" wire:loading.class.delay="opacity-40">
        <div class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
            <flux:heading size="lg">Daily breakdown</flux:heading>
        </div>
        <div class="overflow-x-auto px-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 text-left text-xs font-medium text-zinc-500 dark:border-zinc-800">
                        <th class="py-3 pr-4">Date</th>
                        <th class="py-3 pr-4 text-right">LP Clicks</th>
                        <th class="py-3 pr-4 text-right">Leads</th>
                        <th class="py-3 pr-4 text-right">Q-Leads</th>
                        <th class="py-3 pr-4 text-right">Sales</th>
                        <th class="py-3 pr-4 text-right">LPClick → Lead</th>
                        <th class="py-3 pr-4 text-right">Lead → Q-Lead</th>
                        <th class="py-3 text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->dailyOfferStats->sortByDesc('stat_date') as $day)
                        <tr class="border-b border-zinc-100/80 last:border-0 dark:border-zinc-800/60">
                            <td class="py-3 pr-4 font-medium text-zinc-800 dark:text-zinc-200">{{ \Carbon\CarbonImmutable::parse($day->stat_date)->format('D, d M') }}</td>
                            <td class="py-3 pr-4 text-right tabular-nums">{{ $fmtInt($day->lp_clicks) }}</td>
                            <td class="py-3 pr-4 text-right tabular-nums">{{ $fmtInt($day->leads) }}</td>
                            <td class="py-3 pr-4 text-right tabular-nums">{{ $fmtInt($day->qleads) }}</td>
                            <td class="py-3 pr-4 text-right tabular-nums">{{ $fmtInt($day->sales) }}</td>
                            <td class="py-3 pr-4 text-right tabular-nums font-medium">{{ $fmtPct($day->lpclick_to_lead) }}</td>
                            <td class="py-3 pr-4 text-right tabular-nums font-medium">{{ $fmtPct($day->lead_to_qlead) }}</td>
                            <td class="py-3 text-right tabular-nums">{{ $fmtMoney($day->revenue) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-10 text-center text-zinc-500">No data for this offer in the selected period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
