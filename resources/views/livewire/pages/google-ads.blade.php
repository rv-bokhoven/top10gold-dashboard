@php
    $fmtInt = fn ($v) => number_format((float) $v, 0);
    $fmtPct = fn ($v) => number_format((float) $v * 100, 1).'%';
    $fmtEur = fn ($v) => $this->money($v, 'EUR');
    $ga = $this->googleAdsTotals;
@endphp

<div>
    <flux:heading size="xl" class="mb-2">Google Ads</flux:heading>

    @include('partials.dashboard-filters')

    <div class="transition-opacity" wire:loading.class.delay="opacity-40">
        <flux:subheading class="mb-4">{{ $this->googleAdsByCampaign->count() }} {{ \Illuminate\Support\Str::plural('campaign', $this->googleAdsByCampaign->count()) }}</flux:subheading>

        @if ($ga['impressions'] === 0 && $ga['clicks'] === 0)
            <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
                No Google Ads data for this period yet. Connect the Google Ads API
                (<code>GOOGLE_ADS_*</code> in <code>.env</code>) and run <code>php artisan google-ads:sync --all</code>.
            </div>
        @else
            {{-- Summary cards --}}
            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ([
                    ['Impressions', $fmtInt($ga['impressions'])],
                    ['Clicks', $fmtInt($ga['clicks'])],
                    ['CTR', $fmtPct($ga['ctr'])],
                    ['Cost', $fmtEur($ga['cost'])],
                    ['Avg CPC', $fmtEur($ga['cpc'])],
                    ['Conversions', $fmtInt($ga['conversions'])],
                ] as [$label, $value])
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ $label }}</div>
                        <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            {{-- By campaign --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">By campaign</flux:heading>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 text-left text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-800">
                                <th class="py-2 pr-3">Campaign</th>
                                <th class="py-2 pr-3 text-right">Impr.</th>
                                <th class="py-2 pr-3 text-right">Clicks</th>
                                <th class="py-2 pr-3 text-right">CTR</th>
                                <th class="py-2 pr-3 text-right">Cost</th>
                                <th class="py-2 pr-3 text-right">CPC</th>
                                <th class="py-2 pr-3 text-right">LP-clicks</th>
                                <th class="py-2 pr-3 text-right">Leads</th>
                                <th class="py-2 pr-3 text-right">Q-leads</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->googleAdsByCampaign as $c)
                                <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800/60">
                                    <td class="py-2 pr-3 font-medium text-zinc-800 dark:text-zinc-200">{{ $c->campaign_name ?? '—' }}</td>
                                    <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtInt($c->impressions) }}</td>
                                    <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtInt($c->clicks) }}</td>
                                    <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtPct($c->ctr) }}</td>
                                    <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtEur($c->cost) }}</td>
                                    <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtEur($c->cpc) }}</td>
                                    <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtInt($c->conv_lpclick) }}</td>
                                    <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtInt($c->conv_lead) }}</td>
                                    <td class="py-2 pr-3 text-right tabular-nums">{{ $fmtInt($c->conv_qlead) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
