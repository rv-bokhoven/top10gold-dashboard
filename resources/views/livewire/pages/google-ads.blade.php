@php $ga = $this->googleAdsTotals; @endphp

<div class="flex flex-col gap-4 lg:gap-5">
    @include('partials.dashboard-filters')

    <div class="flex flex-col gap-4 transition-opacity lg:gap-5" wire:loading.class.delay="opacity-40">
        @if ($ga['impressions'] === 0 && $ga['clicks'] === 0)
            <div class="rounded-lg bg-surface p-6 text-center text-sm text-muted">
                Nog geen Google Ads-data voor deze periode. Koppel de Google Ads API
                (<code>GOOGLE_ADS_*</code> in <code>.env</code>) en draai <code>php artisan google-ads:sync --all</code>.
            </div>
        @else
            {{-- Samenvatting --}}
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ([
                    ['Impressies', $this->nlInt($ga['impressions'])],
                    ['Clicks', $this->nlInt($ga['clicks'])],
                    ['CTR', $this->nlPct($ga['ctr'])],
                    ['Cost', $this->money($ga['cost'], 'EUR')],
                    ['Gem. CPC', $this->money($ga['cpc'], 'EUR')],
                    ['Conversies', $this->nlInt($ga['conversions'])],
                ] as [$label, $value])
                    <div class="rounded-md bg-surface px-3 py-2.5">
                        <div class="text-xs text-muted">{{ $label }}</div>
                        <div class="mt-0.5 text-sm font-medium tabular-nums text-fg sm:text-base">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Per campagne --}}
            <div class="rounded-lg bg-surface p-4 sm:p-5">
                <h2 class="mb-4 text-base font-medium text-fg">Per campagne</h2>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead>
                            <tr class="border-b border-border text-left text-xs text-subtle">
                                <th class="py-2 pr-3 font-normal">Campagne</th>
                                @foreach (['Impr.', 'Clicks', 'CTR', 'Cost', 'CPC', 'LP-clicks', 'Leads', 'Q-leads'] as $h)
                                    <th class="py-2 pr-3 text-right font-normal">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->googleAdsByCampaign as $c)
                                <tr class="border-b border-border/60 last:border-0">
                                    <td class="py-3 pr-3 font-medium text-fg">{{ $c->campaign_name ?? '—' }}</td>
                                    <td class="py-3 pr-3 text-right tabular-nums text-muted">{{ $this->nlInt($c->impressions) }}</td>
                                    <td class="py-3 pr-3 text-right tabular-nums text-muted">{{ $this->nlInt($c->clicks) }}</td>
                                    <td class="py-3 pr-3 text-right tabular-nums text-muted">{{ $this->nlPct($c->ctr) }}</td>
                                    <td class="py-3 pr-3 text-right tabular-nums text-fg">{{ $this->money($c->cost, 'EUR') }}</td>
                                    <td class="py-3 pr-3 text-right tabular-nums text-muted">{{ $this->money($c->cpc, 'EUR') }}</td>
                                    <td class="py-3 pr-3 text-right tabular-nums text-muted">{{ $this->nlInt($c->conv_lpclick) }}</td>
                                    <td class="py-3 pr-3 text-right tabular-nums text-fg">{{ $this->nlInt($c->conv_lead) }}</td>
                                    <td class="py-3 pr-3 text-right tabular-nums text-fg">{{ $this->nlInt($c->conv_qlead) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        @endif

        {{-- Recente wijzigingen (Google Ads change history, laatste ~30 dagen).
             Staat buiten de stats-check want de change history is altijd
             periode-onafhankelijk. --}}
        @php
            $changes = $this->recentChanges;
            $resLabels = [
                'CAMPAIGN' => 'Campagne',
                'CAMPAIGN_BUDGET' => 'Campagnebudget',
                'CAMPAIGN_CRITERION' => 'Campagne-targeting',
                'CAMPAIGN_BID_MODIFIER' => 'Bodaanpassing',
                'AD_GROUP' => 'Advertentiegroep',
                'AD_GROUP_AD' => 'Advertentie',
                'AD_GROUP_CRITERION' => 'Zoekwoord / targeting',
                'AD_GROUP_BID_MODIFIER' => 'Bodaanpassing',
                'AD' => 'Advertentie',
                'FEED' => 'Feed',
                'FEED_ITEM' => 'Feed-item',
            ];
            $opMap = [
                'CREATE' => ['Toegevoegd', 'text-positive'],
                'UPDATE' => ['Gewijzigd', 'text-fg'],
                'REMOVE' => ['Verwijderd', 'text-negative'],
            ];
        @endphp
        <div class="rounded-lg bg-surface p-4 transition-opacity sm:p-5" wire:loading.class.delay="opacity-40">
            <h2 class="text-base font-medium text-fg">Recente wijzigingen</h2>
            <p class="mb-4 text-xs text-subtle">Wijzigingen in het Google Ads-account · laatste 30 dagen</p>
            <div class="max-h-[360px] overflow-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="sticky top-0 z-10 bg-surface">
                        <tr class="border-b border-border text-left text-xs text-subtle">
                            <th class="bg-surface py-2 pr-3 font-normal">Tijd</th>
                            <th class="bg-surface py-2 pr-3 font-normal">Wijziging</th>
                            <th class="bg-surface py-2 pr-3 font-normal">Campagne</th>
                            <th class="bg-surface py-2 pr-3 font-normal">Door</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($changes as $ch)
                            @php
                                [$opLabel, $opTone] = $opMap[$ch['operation']] ?? [ucfirst(strtolower((string) $ch['operation'])), 'text-muted'];
                                $resLabel = $resLabels[$ch['resource_type']] ?? \Illuminate\Support\Str::of((string) $ch['resource_type'])->replace('_', ' ')->lower()->ucfirst();
                                $fields = trim((string) $ch['changed_fields']);
                            @endphp
                            <tr class="border-b border-border/60 align-top last:border-0">
                                <td class="whitespace-nowrap py-3 pr-3 tabular-nums text-muted">
                                    {{ $ch['time'] ? \Carbon\CarbonImmutable::parse($ch['time'])->locale('nl')->isoFormat('D MMM HH:mm') : '—' }}
                                </td>
                                <td class="py-3 pr-3">
                                    <span class="font-medium {{ $opTone }}">{{ $opLabel }}</span>
                                    <span class="text-muted">· {{ $resLabel }}</span>
                                    @if ($fields !== '')
                                        <div class="text-xs text-subtle">{{ \Illuminate\Support\Str::limit(str_replace(['campaign.', 'ad_group.', 'ad_group_ad.', 'ad.'], '', $fields), 80) }}</div>
                                    @endif
                                </td>
                                <td class="py-3 pr-3 text-fg">{{ $ch['campaign'] ?? '—' }}</td>
                                <td class="py-3 pr-3 text-muted">{{ $ch['user'] ? \Illuminate\Support\Str::before($ch['user'], '@') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-10 text-center text-subtle">Geen wijzigingen in de laatste 30 dagen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
