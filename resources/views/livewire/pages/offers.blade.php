@php
    $fmtInt = fn ($v) => number_format((float) $v, 0);
    $fmtPct = fn ($v) => number_format((float) $v * 100, 1).'%';
    $fmtMoney = fn ($v) => $this->money($v, 'USD');
@endphp

<div>
    <flux:heading size="xl" class="mb-2">Offers</flux:heading>

    @include('partials.dashboard-filters')

    <div class="space-y-6 transition-opacity" wire:loading.class.delay="opacity-40">
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
                                <td class="py-2 pr-3 font-medium text-zinc-800 dark:text-zinc-200">{{ \Carbon\CarbonImmutable::parse($day->stat_date)->format('d M') }}</td>
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
