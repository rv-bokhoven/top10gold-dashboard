<div class="flex flex-col gap-4 lg:gap-5"
    x-data="{
        open: false,
        saving: false,
        errors: {},
        form: {},
        blank: {
            offer_id: '', offer_title: '', partner: '', platform_url: '',
            contact_name: '', contact_email: '', deal_model: '', payout: '',
            payout_currency: 'USD', has_revshare: false, revshare_pct: '', comments: '',
        },
        edit(offer) {
            this.errors = {};
            this.form = Object.assign({}, this.blank, offer, {
                partner: offer.partner || offer.offer_title || '',
                payout: offer.payout ?? '',
                revshare_pct: offer.revshare_pct ?? '',
                has_revshare: !!offer.has_revshare,
                payout_currency: offer.payout_currency || 'USD',
            });
            this.open = true;
        },
        async save() {
            if (this.saving) return;
            this.saving = true;
            let res = await $wire.savePartner({ ...this.form });
            this.saving = false;
            if (res && res.ok) { this.open = false; }
            else { this.errors = (res && res.errors) || {}; }
        },
        err(field) { return this.errors[field] ? this.errors[field][0] : null; },
    }">
    <div>
        <h1 class="text-base font-normal text-fg">Partnerbeheer</h1>
        <p class="text-xs text-subtle">Partner-, contact- en dealgegevens per offer</p>
    </div>

    {{-- Bewerk-formulier (volledig client-side; alleen opslaan raakt de server) --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/30 p-4 sm:items-center">
        <div class="w-full max-w-2xl rounded-lg bg-surface p-5 shadow-lg sm:p-6" @click.outside="open = false" @keydown.escape.window="open = false">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-medium text-fg" x-text="form.offer_title || form.offer_id"></h2>
                    <p class="text-xs text-subtle" x-text="form.offer_id"></p>
                </div>
                <button type="button" @click="open = false" class="flex size-8 items-center justify-center rounded-md text-subtle transition hover:bg-elevated hover:text-fg">
                    <x-lucide name="x" class="size-4" />
                </button>
            </div>

            <form @submit.prevent="save()" class="flex flex-col gap-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-muted">Partner</label>
                        <input type="text" x-model="form.partner" placeholder="bijv. American Hartford Gold"
                            class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none">
                        <span class="text-xs text-negative" x-show="err('partner')" x-text="err('partner')"></span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-muted">Affiliateplatform-link</label>
                        <input type="url" x-model="form.platform_url" placeholder="https://…"
                            class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none">
                        <span class="text-xs text-negative" x-show="err('platform_url')" x-text="err('platform_url')"></span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-muted">Contactpersoon</label>
                        <input type="text" x-model="form.contact_name" placeholder="Naam"
                            class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none">
                        <span class="text-xs text-negative" x-show="err('contact_name')" x-text="err('contact_name')"></span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-muted">E-mail</label>
                        <input type="email" x-model="form.contact_email" placeholder="naam@partner.com"
                            class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none">
                        <span class="text-xs text-negative" x-show="err('contact_email')" x-text="err('contact_email')"></span>
                    </div>
                </div>

                {{-- Deal --}}
                <div class="rounded-md bg-bg p-4">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-muted">Type deal</label>
                            <select x-model="form.deal_model"
                                class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg focus:border-border-strong focus:outline-none">
                                <option value="">—</option>
                                <option value="cpl">CPL</option>
                                <option value="cpql">CPQL</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-muted">Bedrag per lead/q-lead</label>
                            <input type="number" step="0.01" min="0" x-model="form.payout" placeholder="0,00"
                                class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none">
                            <span class="text-xs text-negative" x-show="err('payout')" x-text="err('payout')"></span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs text-muted">Valuta</label>
                            <select x-model="form.payout_currency"
                                class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg focus:border-border-strong focus:outline-none">
                                <option value="USD">USD ($)</option>
                                <option value="EUR">EUR (€)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-end gap-4">
                        <label class="inline-flex items-center gap-2 text-sm text-fg">
                            <input type="checkbox" x-model="form.has_revshare" class="size-4 rounded border-border text-accent focus:ring-0">
                            Met revshare
                        </label>
                        <div class="flex flex-col gap-1" x-show="form.has_revshare" x-cloak>
                            <label class="text-xs text-muted">Revshare %</label>
                            <input type="number" step="0.01" min="0" max="100" x-model="form.revshare_pct" placeholder="bijv. 20"
                                class="h-10 w-32 rounded-md border border-border bg-surface px-3 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none">
                            <span class="text-xs text-negative" x-show="err('revshare_pct')" x-text="err('revshare_pct')"></span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted">Opmerkingen</label>
                    <textarea x-model="form.comments" rows="3" placeholder="Notities over de deal, betalingen, afspraken…"
                        class="rounded-md border border-border bg-surface px-3 py-2 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none"></textarea>
                    <span class="text-xs text-negative" x-show="err('comments')" x-text="err('comments')"></span>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="open = false"
                        class="inline-flex h-10 items-center rounded-md border border-border bg-surface px-4 text-sm text-fg transition hover:bg-elevated">Annuleren</button>
                    <button type="submit" :disabled="saving"
                        class="inline-flex h-10 items-center rounded-md bg-accent px-4 text-sm font-medium text-accent-fg transition hover:opacity-90 disabled:opacity-60"
                        x-text="saving ? 'Opslaan…' : 'Opslaan'"></button>
                </div>
            </form>
        </div>
    </div>

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
                        @php
                            $p = $offer->partner_data;
                            $offerData = [
                                'offer_id' => $offer->offer_id,
                                'offer_title' => $offer->offer_title,
                                'partner' => $p->partner ?? null,
                                'platform_url' => $p->platform_url ?? null,
                                'contact_name' => $p->contact_name ?? null,
                                'contact_email' => $p->contact_email ?? null,
                                'deal_model' => $p->deal_model ?? null,
                                'payout' => $p && $p->payout !== null ? (float) $p->payout : null,
                                'payout_currency' => $p->payout_currency ?? 'USD',
                                'has_revshare' => (bool) ($p->has_revshare ?? false),
                                'revshare_pct' => $p && $p->revshare_pct !== null ? (float) $p->revshare_pct : null,
                                'comments' => $p->comments ?? null,
                            ];
                        @endphp
                        <tr class="border-b border-border/60 align-top last:border-0">
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
                                <button type="button" @click="edit(@js($offerData))"
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
