@php
    $offerQuery = http_build_query(array_filter([
        'period' => $period, 'from' => $from, 'to' => $to,
        'currency' => $currency, 'source' => $source,
    ], fn ($value) => $value !== null && $value !== ''));

    $cols = [
        'lp_clicks' => 'LP clicks', 'leads' => 'Leads', 'qleads' => 'Q-leads',
        'sales' => 'Sales', 'revenue' => 'Revenue',
        'lpclick_to_lead' => 'LPClick→Lead', 'lead_to_qlead' => 'Lead→Q-lead',
    ];
@endphp

<div class="flex flex-col gap-4 lg:gap-5">
    @include('partials.dashboard-filters')

    <div class="rounded-lg bg-surface p-4 transition-opacity sm:p-5" wire:loading.class.delay="opacity-40">
        <h2 class="mb-4 text-base font-medium text-fg">Offer-prestaties</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm">
                <thead>
                    <tr class="border-b border-border text-left text-xs text-subtle">
                        <th class="py-2 pr-3 font-normal">Offer</th>
                        @foreach ($cols as $col => $label)
                            <th class="py-2 pr-3 font-normal">
                                <button type="button" wire:click="sortOffersBy('{{ $col }}')" class="flex w-full items-center justify-end gap-1 hover:text-fg">
                                    {{ $label }}
                                    @if ($offerSort === $col)<span class="text-fg">{{ $offerDir === 'asc' ? '↑' : '↓' }}</span>@endif
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->offerStats as $offer)
                        <tr class="border-b border-border/60 transition last:border-0 hover:bg-elevated/40">
                            <td class="py-3 pr-3 font-medium text-fg">
                                <a href="{{ route('offers.show', ['offerId' => $offer->offer_id]).($offerQuery ? '?'.$offerQuery : '') }}" wire:navigate class="underline-offset-4 hover:underline">
                                    {{ $offer->offer_title ?? '—' }}
                                </a>
                            </td>
                            <td class="py-3 pr-3 text-right tabular-nums text-muted">{{ $this->nlInt($offer->lp_clicks) }}</td>
                            <td class="py-3 pr-3 text-right tabular-nums text-fg">{{ $this->nlInt($offer->leads) }}</td>
                            <td class="py-3 pr-3 text-right tabular-nums text-muted">{{ $this->nlInt($offer->qleads) }}</td>
                            <td class="py-3 pr-3 text-right tabular-nums text-muted">{{ $this->nlInt($offer->sales) }}</td>
                            <td class="py-3 pr-3 text-right tabular-nums text-fg">{{ $this->money($offer->revenue, 'USD') }}</td>
                            <td class="py-3 pr-3 text-right font-semibold tabular-nums text-fg">{{ $this->nlPct($offer->lpclick_to_lead) }}</td>
                            <td class="py-3 pr-3 text-right font-semibold tabular-nums text-fg">{{ $this->nlPct($offer->lead_to_qlead) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-10 text-center text-subtle">Geen data voor deze periode.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
