<div class="flex flex-col gap-4 lg:gap-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-base font-normal text-fg">Landingspagina's</h1>
            <p class="text-xs text-subtle">Final URLs uit de live ads — uptime-check</p>
        </div>
        <button type="button" wire:click="checkLandingPages" wire:loading.attr="disabled" wire:target="checkLandingPages"
            class="inline-flex h-10 items-center gap-2 rounded-md border border-border bg-surface px-3 text-sm text-fg transition hover:bg-elevated">
            <x-lucide name="refresh" class="size-4 text-muted" wire:loading.class="animate-spin" wire:target="checkLandingPages" />
            <span wire:loading.remove wire:target="checkLandingPages">Nu checken</span>
            <span wire:loading wire:target="checkLandingPages">Bezig…</span>
        </button>
    </div>

    <div class="rounded-lg bg-surface p-4 transition-opacity sm:p-5" wire:loading.class.delay="opacity-40" wire:target="checkLandingPages">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="border-b border-border text-left text-xs text-subtle">
                        <th class="py-2 pr-3 font-normal">Status</th>
                        <th class="py-2 pr-3 font-normal">Landingspagina</th>
                        <th class="py-2 pr-3 font-normal">Campagnes</th>
                        <th class="py-2 pr-3 text-right font-normal">Gecheckt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->landingPages as $page)
                        <tr class="border-b border-border/60 last:border-0">
                            <td class="py-3 pr-3">
                                @if ($page->ok)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-elevated px-2 py-0.5 text-xs font-medium text-positive">● Online{{ $page->status_code ? ' '.$page->status_code : '' }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-elevated px-2 py-0.5 text-xs font-medium text-negative">● {{ $page->status_code ?: 'Fout' }}</span>
                                @endif
                            </td>
                            <td class="py-3 pr-3">
                                <a href="{{ $page->url }}" target="_blank" rel="noopener" class="text-fg underline-offset-2 hover:underline">{{ \Illuminate\Support\Str::after($page->url, 'top10.compare') ?: $page->url }}</a>
                                @if ($page->error)<div class="text-xs text-negative">{{ \Illuminate\Support\Str::limit($page->error, 80) }}</div>@endif
                            </td>
                            <td class="py-3 pr-3 text-muted">{{ $page->campaigns }}</td>
                            <td class="py-3 pr-3 text-right text-subtle">{{ $page->checked_at?->locale('nl')->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-subtle">Nog niet gecheckt. Klik <strong>Nu checken</strong> (vereist de Google Ads-koppeling).</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
