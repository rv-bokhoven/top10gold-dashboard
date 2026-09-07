@php
    $fmtInt = fn ($v) => number_format((float) $v, 0);
    $fmtPct = fn ($v) => number_format((float) $v * 100, 1).'%';
    $fmtMoney = fn ($v) => $this->money($v, 'USD');
@endphp

<div>
    <flux:heading size="xl" class="mb-1">Offers</flux:heading>
    <flux:subheading class="mb-4">Select an offer to view its daily performance.</flux:subheading>

    @include('partials.dashboard-filters')

    @php
        $offerQuery = http_build_query(array_filter([
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'currency' => $currency,
            'source' => $source,
        ], fn ($value) => $value !== null && $value !== ''));
    @endphp

    <div class="transition-opacity" wire:loading.class.delay="opacity-40">
        <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm shadow-zinc-950/[0.02] dark:border-zinc-800 dark:bg-zinc-900">
            <div class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <flux:heading size="lg">Offer performance</flux:heading>
            </div>
            <div class="overflow-x-auto px-5">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 text-left text-xs font-medium text-zinc-500 dark:border-zinc-800">
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
                            <tr class="border-b border-zinc-100/80 last:border-0 transition hover:bg-zinc-50/80 dark:border-zinc-800/60 dark:hover:bg-zinc-800/40">
                                <td class="py-3 pr-3 font-medium text-zinc-800 dark:text-zinc-200">
                                    <a href="{{ route('offers.show', ['offerId' => $offer->offer_id]).($offerQuery ? '?'.$offerQuery : '') }}" wire:navigate class="hover:underline underline-offset-4">
                                        {{ $offer->offer_title ?? '—' }}
                                    </a>
                                </td>
                                <td class="py-3 pr-3 text-right tabular-nums">{{ $fmtInt($offer->lp_clicks) }}</td>
                                <td class="py-3 pr-3 text-right tabular-nums">{{ $fmtInt($offer->leads) }}</td>
                                <td class="py-3 pr-3 text-right tabular-nums">{{ $fmtInt($offer->qleads) }}</td>
                                <td class="py-3 pr-3 text-right tabular-nums">{{ $fmtInt($offer->sales) }}</td>
                                <td class="py-3 pr-3 text-right tabular-nums">{{ $fmtMoney($offer->revenue) }}</td>
                                <td class="py-3 pr-3 text-right tabular-nums font-semibold text-zinc-900 dark:text-white">{{ $fmtPct($offer->lpclick_to_lead) }}</td>
                                <td class="py-3 pr-3 text-right tabular-nums font-semibold text-zinc-900 dark:text-white">{{ $fmtPct($offer->lead_to_qlead) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-10 text-center text-zinc-500">No data for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
