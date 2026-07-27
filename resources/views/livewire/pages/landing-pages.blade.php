<div>
    <div class="mb-4 flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Landing pages</flux:heading>
            <flux:subheading>Final URLs from the live ads — uptime check</flux:subheading>
        </div>
        <flux:button wire:click="checkLandingPages" wire:loading.attr="disabled" wire:target="checkLandingPages" icon="arrow-path" size="sm">
            <span wire:loading.remove wire:target="checkLandingPages">Check now</span>
            <span wire:loading wire:target="checkLandingPages">Checking…</span>
        </flux:button>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 transition-opacity dark:border-zinc-800 dark:bg-zinc-900"
        wire:loading.class.delay="opacity-40" wire:target="checkLandingPages">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-800">
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Landing page</th>
                        <th class="py-2 pr-3">Campaigns</th>
                        <th class="py-2 pr-3 text-right">Checked</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->landingPages as $page)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800/60">
                            <td class="py-2 pr-3">
                                @if ($page->ok)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">● Online{{ $page->status_code ? ' '.$page->status_code : '' }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-500/10 dark:text-rose-400">● {{ $page->status_code ?: 'Error' }}</span>
                                @endif
                            </td>
                            <td class="py-2 pr-3">
                                <a href="{{ $page->url }}" target="_blank" rel="noopener" class="text-zinc-800 underline-offset-2 hover:underline dark:text-zinc-200">{{ \Illuminate\Support\Str::after($page->url, 'top10.compare') ?: $page->url }}</a>
                                @if ($page->error)<div class="text-xs text-rose-500">{{ \Illuminate\Support\Str::limit($page->error, 80) }}</div>@endif
                            </td>
                            <td class="py-2 pr-3 text-zinc-500">{{ $page->campaigns }}</td>
                            <td class="py-2 pr-3 text-right text-zinc-400">{{ $page->checked_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-zinc-400">Not checked yet. Click <strong>Check now</strong> (requires the Google Ads connection).</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
