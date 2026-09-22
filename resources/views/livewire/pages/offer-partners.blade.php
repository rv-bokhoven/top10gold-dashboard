<div class="flex flex-col gap-4 lg:gap-5">
    <div>
        <h1 class="text-base font-normal text-fg">Partnerbeheer</h1>
        <p class="text-xs text-subtle">Partner-, contact- en dealgegevens per offer</p>
    </div>

    {{-- Bewerk-formulier --}}
    @if ($editingOfferId !== null)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/30 p-4 sm:items-center" wire:key="editor">
            <div class="w-full max-w-2xl rounded-lg bg-surface p-5 shadow-lg sm:p-6" @click.outside="$wire.cancel()">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-medium text-fg">{{ $editingOfferTitle }}</h2>
                        <p class="text-xs text-subtle">{{ $editingOfferId }}</p>
                    </div>
                    <button type="button" wire:click="cancel" class="flex size-8 items-center justify-center rounded-md text-subtle transition hover:bg-elevated hover:text-fg">
                        <x-lucide name="x" class="size-4" />
                    </button>
                </div>

                <form wire:submit="save" class="flex flex-col gap-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-muted">Partner</label>
                            <input type="text" wire:model="partner" placeholder="bijv. American Hartford Gold"
                                class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none">
                            @error('partner') <span class="text-xs text-negative">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-muted">Affiliateplatform-link</label>
                            <input type="url" wire:model="platform_url" placeholder="https://…"
                                class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none">
                            @error('platform_url') <span class="text-xs text-negative">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-muted">Contactpersoon</label>
                            <input type="text" wire:model="contact_name" placeholder="Naam"
                                class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none">
                            @error('contact_name') <span class="text-xs text-negative">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-muted">E-mail</label>
                            <input type="email" wire:model="contact_email" placeholder="naam@partner.com"
                                class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none">
                            @error('contact_email') <span class="text-xs text-negative">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Deal --}}
                    <div class="rounded-md bg-bg p-4">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="flex flex-col gap-1">
                                <label class="text-xs text-muted">Type deal</label>
                                <select wire:model="deal_model"
                                    class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg focus:border-border-strong focus:outline-none">
                                    <option value="">—</option>
                                    <option value="cpl">CPL</option>
                                    <option value="cpql">CPQL</option>
                                </select>
                                @error('deal_model') <span class="text-xs text-negative">{{ $message }}</span> @enderror
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-xs text-muted">Bedrag per lead/q-lead</label>
                                <input type="number" step="0.01" min="0" wire:model="payout" placeholder="0,00"
                                    class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none">
                                @error('payout') <span class="text-xs text-negative">{{ $message }}</span> @enderror
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-xs text-muted">Valuta</label>
                                <select wire:model="payout_currency"
                                    class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg focus:border-border-strong focus:outline-none">
                                    <option value="USD">USD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-end gap-4">
                            <label class="inline-flex items-center gap-2 text-sm text-fg">
                                <input type="checkbox" wire:model.live="has_revshare" class="size-4 rounded border-border text-accent focus:ring-0">
                                Met revshare
                            </label>
                            @if ($has_revshare)
                                <div class="flex flex-col gap-1">
                                    <label class="text-xs text-muted">Revshare %</label>
                                    <input type="number" step="0.01" min="0" max="100" wire:model="revshare_pct" placeholder="bijv. 20"
                                        class="h-10 w-32 rounded-md border border-border bg-surface px-3 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none">
                                    @error('revshare_pct') <span class="text-xs text-negative">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-muted">Opmerkingen</label>
                        <textarea wire:model="comments" rows="3" placeholder="Notities over de deal, betalingen, afspraken…"
                            class="rounded-md border border-border bg-surface px-3 py-2 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none"></textarea>
                        @error('comments') <span class="text-xs text-negative">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="cancel"
                            class="inline-flex h-10 items-center rounded-md border border-border bg-surface px-4 text-sm text-fg transition hover:bg-elevated">Annuleren</button>
                        <button type="submit"
                            class="inline-flex h-10 items-center rounded-md bg-accent px-4 text-sm font-medium text-accent-fg transition hover:opacity-90">Opslaan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Overzicht --}}
    <div class="rounded-lg bg-surface p-4 sm:p-5">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-sm">
                <thead>
                    <tr class="border-b border-border text-left text-xs text-subtle">
                        <th class="py-2 pr-3 font-normal">Offer</th>
                        <th class="py-2 pr-3 font-normal">Partner</th>
                        <th class="py-2 pr-3 font-normal">Deal</th>
                        <th class="py-2 pr-3 font-normal">Contact</th>
                        <th class="py-2 pr-3 font-normal">Platform</th>
                        <th class="py-2 pr-3 text-right font-normal"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->offers as $offer)
                        @php $p = $offer->partner_data; @endphp
                        <tr class="border-b border-border/60 last:border-0 align-top">
                            <td class="py-3 pr-3">
                                <div class="font-medium text-fg">{{ $offer->offer_title ?: $offer->offer_id }}</div>
                                @if ($offer->offer_title)<div class="text-xs text-subtle">{{ $offer->offer_id }}</div>@endif
                            </td>
                            <td class="py-3 pr-3 text-fg">{{ $p->partner ?? '—' }}</td>
                            <td class="py-3 pr-3 text-muted">
                                {{ $p ? $p->dealSummary() : '—' }}
                                @if ($p && $p->comments)
                                    <div class="mt-0.5 text-xs text-subtle">{{ \Illuminate\Support\Str::limit($p->comments, 60) }}</div>
                                @endif
                            </td>
                            <td class="py-3 pr-3 text-muted">
                                @if ($p && $p->contact_name)<div class="text-fg">{{ $p->contact_name }}</div>@endif
                                @if ($p && $p->contact_email)<a href="mailto:{{ $p->contact_email }}" class="text-xs text-muted underline-offset-2 hover:text-fg hover:underline">{{ $p->contact_email }}</a>@endif
                                @if (! $p || (! $p->contact_name && ! $p->contact_email))—@endif
                            </td>
                            <td class="py-3 pr-3">
                                @if ($p && $p->platform_url)
                                    <a href="{{ $p->platform_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-muted underline-offset-2 hover:text-fg hover:underline">
                                        <x-lucide name="globe" class="size-3.5" /> Open
                                    </a>
                                @else
                                    <span class="text-subtle">—</span>
                                @endif
                            </td>
                            <td class="py-3 text-right">
                                <button type="button" wire:click="edit(@js($offer->offer_id))"
                                    class="inline-flex h-8 items-center rounded-md border border-border bg-surface px-3 text-xs text-fg transition hover:bg-elevated">
                                    {{ $p ? 'Bewerken' : 'Invullen' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-subtle">Nog geen offers gevonden.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
