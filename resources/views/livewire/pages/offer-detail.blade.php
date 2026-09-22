@php
    $totals = $this->offerTotals;
    $backQuery = http_build_query(array_filter([
        'period' => $period, 'from' => $from, 'to' => $to,
        'currency' => $currency, 'source' => $source,
    ], fn ($value) => $value !== null && $value !== ''));
@endphp

<div class="flex flex-col gap-4 lg:gap-5">
    @include('partials.dashboard-filters')

    <div>
        <a href="{{ route('offers').($backQuery ? '?'.$backQuery : '') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-muted transition hover:text-fg">
            <x-lucide name="chevron-left" class="size-4" /> Offers
        </a>
        <h2 class="mt-2 text-base font-medium text-fg">{{ $this->offerTitle }}</h2>
        <p class="text-xs text-subtle">Dagelijkse offer-prestaties en funnel-kwaliteit</p>
    </div>

    <div class="grid grid-cols-2 gap-2 transition-opacity sm:grid-cols-3 xl:grid-cols-6" wire:loading.class.delay="opacity-40">
        @foreach ([
            ['LP clicks', $this->nlInt($totals['lp_clicks'])],
            ['Leads', $this->nlInt($totals['leads'])],
            ['Q-leads', $this->nlInt($totals['qleads'])],
            ['Sales', $this->nlInt($totals['sales'])],
            ['LPClick → Lead', $this->nlPct($totals['lpclick_to_lead'])],
            ['Revenue', $this->money($totals['revenue'], 'USD')],
        ] as [$label, $value])
            <div class="rounded-md bg-surface px-3 py-2.5">
                <div class="text-xs text-muted">{{ $label }}</div>
                <div class="mt-0.5 text-sm font-medium tabular-nums text-fg sm:text-base">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    @php $partner = $this->partner; @endphp
    <div class="rounded-lg bg-surface p-4 sm:p-5">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-base font-medium text-fg">Partnergegevens</h2>
            <a href="{{ route('offers.partners') }}" wire:navigate class="inline-flex h-8 items-center rounded-md border border-border bg-surface px-3 text-xs text-fg transition hover:bg-elevated">
                {{ $partner ? 'Bewerken' : 'Invullen' }}
            </a>
        </div>
        @if ($partner)
            <div class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <div class="text-xs text-muted">Partner</div>
                    <div class="mt-0.5 text-sm text-fg">{{ $partner->partner ?: '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-muted">Deal</div>
                    <div class="mt-0.5 text-sm text-fg">{{ $partner->dealSummary() }}</div>
                </div>
                <div>
                    <div class="text-xs text-muted">Contact</div>
                    <div class="mt-0.5 text-sm text-fg">
                        {{ $partner->contact_name ?: '—' }}
                        @if ($partner->contact_email)
                            <a href="mailto:{{ $partner->contact_email }}" class="block text-xs text-muted underline-offset-2 hover:text-fg hover:underline">{{ $partner->contact_email }}</a>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-xs text-muted">Platform</div>
                    <div class="mt-0.5 text-sm">
                        @if ($partner->platform_url)
                            <a href="{{ $partner->platform_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-fg underline-offset-2 hover:underline"><x-lucide name="globe" class="size-3.5" /> Open</a>
                        @else
                            <span class="text-fg">—</span>
                        @endif
                    </div>
                </div>
                @if ($partner->comments)
                    <div class="sm:col-span-2 lg:col-span-4">
                        <div class="text-xs text-muted">Opmerkingen</div>
                        <div class="mt-0.5 whitespace-pre-line text-sm text-fg">{{ $partner->comments }}</div>
                    </div>
                @endif
            </div>
        @else
            <p class="text-sm text-subtle">Nog geen partnergegevens voor deze offer.</p>
        @endif
    </div>

    <div class="rounded-lg bg-surface p-4 transition-opacity sm:p-5" wire:loading.class.delay="opacity-40">
        <h2 class="mb-4 text-base font-medium text-fg">Per dag</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm">
                <thead>
                    <tr class="border-b border-border text-left text-xs text-subtle">
                        <th class="py-2 pr-4 font-normal">Datum</th>
                        @foreach (['LP clicks', 'Leads', 'Q-leads', 'Sales', 'LPClick → Lead', 'Lead → Q-lead', 'Revenue'] as $h)
                            <th class="py-2 pr-4 text-right font-normal">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->dailyOfferStats->sortByDesc('stat_date') as $day)
                        <tr class="border-b border-border/60 last:border-0">
                            <td class="py-3 pr-4 font-medium text-fg">{{ \Carbon\CarbonImmutable::parse($day->stat_date)->locale('nl')->isoFormat('dd D MMM') }}</td>
                            <td class="py-3 pr-4 text-right tabular-nums text-muted">{{ $this->nlInt($day->lp_clicks) }}</td>
                            <td class="py-3 pr-4 text-right tabular-nums text-fg">{{ $this->nlInt($day->leads) }}</td>
                            <td class="py-3 pr-4 text-right tabular-nums text-muted">{{ $this->nlInt($day->qleads) }}</td>
                            <td class="py-3 pr-4 text-right tabular-nums text-muted">{{ $this->nlInt($day->sales) }}</td>
                            <td class="py-3 pr-4 text-right font-medium tabular-nums text-fg">{{ $this->nlPct($day->lpclick_to_lead) }}</td>
                            <td class="py-3 pr-4 text-right font-medium tabular-nums text-fg">{{ $this->nlPct($day->lead_to_qlead) }}</td>
                            <td class="py-3 text-right tabular-nums text-fg">{{ $this->money($day->revenue, 'USD') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-10 text-center text-subtle">Geen data voor deze offer in de gekozen periode.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
