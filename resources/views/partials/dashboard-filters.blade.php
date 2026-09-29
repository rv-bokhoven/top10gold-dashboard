@php
    [$rangeFrom, $rangeTo] = $this->range();
    $rangeFrom = \Carbon\CarbonImmutable::parse($rangeFrom)->locale('nl');
    $rangeTo = \Carbon\CarbonImmutable::parse($rangeTo)->locale('nl');

    if ($rangeFrom->isSameDay($rangeTo)) {
        $rangeLabel = $rangeFrom->isoFormat('D MMM YYYY');
    } elseif ($rangeFrom->isSameMonth($rangeTo) && $rangeFrom->year === $rangeTo->year) {
        $rangeLabel = $rangeFrom->isoFormat('D').'–'.$rangeTo->isoFormat('D MMM YYYY');
    } elseif ($rangeFrom->year === $rangeTo->year) {
        $rangeLabel = $rangeFrom->isoFormat('D MMM').' – '.$rangeTo->isoFormat('D MMM YYYY');
    } else {
        $rangeLabel = $rangeFrom->isoFormat('D MMM YYYY').' – '.$rangeTo->isoFormat('D MMM YYYY');
    }

    $sources = config('redtrack.sources');
    $pageTitle = $source === 'all' ? 'Alle campagnes' : ($sources[$source]['label'] ?? ucfirst($source));
    $periodLabel = $this::PERIODS[$period] ?? 'Aangepast';
@endphp

<div class="sticky top-0 z-20 -mx-4 mb-1 flex flex-wrap items-center justify-between gap-3 bg-bg/90 px-4 py-3 backdrop-blur lg:-mx-8 lg:px-8">
    <div>
        <h1 class="text-base font-normal text-fg">{{ $pageTitle }}</h1>
        <p class="text-xs text-subtle">{{ $rangeLabel }}</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        {{-- Source toggle --}}
        <div class="hidden h-9 shrink-0 overflow-hidden rounded-md border border-border sm:inline-flex">
            @foreach (['all' => 'Alle'] + collect($sources)->map(fn ($c) => $c['label'])->all() as $val => $label)
                <button type="button" wire:click="$set('source', '{{ $val }}')"
                    @class([
                        'inline-flex items-center px-3 text-sm transition',
                        'bg-accent text-accent-fg' => $source === $val,
                        'bg-surface text-muted hover:bg-elevated' => $source !== $val,
                    ])>{{ $label }}</button>
            @endforeach
        </div>

        {{-- Currency toggle --}}
        <div class="hidden h-9 shrink-0 overflow-hidden rounded-md border border-border sm:inline-flex">
            @foreach (['EUR' => '€', 'USD' => '$'] as $val => $label)
                <button type="button" wire:click="$set('currency', '{{ $val }}')"
                    @class([
                        'inline-flex items-center px-3 text-sm font-medium transition',
                        'bg-accent text-accent-fg' => $currency === $val,
                        'bg-surface text-muted hover:bg-elevated' => $currency !== $val,
                    ])>{{ $label }}</button>
            @endforeach
        </div>

        {{-- Period dropdown --}}
        <div
            x-data="{
                open: false, cal: false,
                cur: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
                selFrom: @js($from), selTo: @js($to),
                today: new Date(new Date().toDateString()),
                monthLabel() { return this.cur.toLocaleDateString('nl-NL', { month: 'long', year: 'numeric' }); },
                weeks() {
                    let y = this.cur.getFullYear(), m = this.cur.getMonth();
                    let start = (new Date(y, m, 1).getDay() + 6) % 7;
                    let d = new Date(y, m, 1 - start), days = [];
                    for (let i = 0; i < 42; i++) { days.push(new Date(d)); d.setDate(d.getDate() + 1); }
                    let w = []; for (let i = 0; i < 6; i++) w.push(days.slice(i * 7, i * 7 + 7));
                    return w;
                },
                iso(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); },
                inMonth(d) { return d.getMonth() === this.cur.getMonth(); },
                disabled(d) { return d > this.today; },
                edge(d) { let s = this.iso(d); return s === this.selFrom || s === this.selTo; },
                inRange(d) { if (!this.selFrom || !this.selTo) return false; let s = this.iso(d); return s > this.selFrom && s < this.selTo; },
                prev() { this.cur = new Date(this.cur.getFullYear(), this.cur.getMonth() - 1, 1); },
                next() { this.cur = new Date(this.cur.getFullYear(), this.cur.getMonth() + 1, 1); },
                pick(d) {
                    if (this.disabled(d)) return;
                    let s = this.iso(d);
                    if (!this.selFrom || (this.selFrom && this.selTo)) { this.selFrom = s; this.selTo = null; }
                    else if (s < this.selFrom) { this.selFrom = s; }
                    else {
                        this.selTo = s;
                        $wire.set('from', this.selFrom, false);
                        $wire.set('to', this.selTo, false);
                        $wire.set('period', 'custom');
                        this.open = false; this.cal = false;
                    }
                },
            }"
            @click.outside="open = false; cal = false"
            class="relative"
        >
            <button type="button" @click="open = !open; cal = false"
                class="inline-flex h-10 items-center gap-2 rounded-md border border-border bg-surface px-3 text-sm font-normal text-fg transition hover:bg-elevated">
                <x-lucide name="calendar-range" class="size-4 text-muted" />
                <span>{{ $periodLabel }}</span>
                <x-lucide name="chevron-down" class="size-4 text-muted" />
            </button>

            {{-- Presets --}}
            <div x-show="open && !cal" x-cloak x-transition.opacity
                class="absolute right-0 z-30 mt-1 w-48 rounded-md border border-border bg-surface p-1 shadow-border">
                @foreach ($this::PERIODS as $key => $label)
                    @if ($key === 'custom')
                        <button type="button" @click="cal = true"
                            class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm text-fg transition hover:bg-elevated">
                            <span>{{ $label }}</span>
                            @if ($period === 'custom')<x-lucide name="check" class="size-4 text-muted" />@endif
                        </button>
                    @else
                        <button type="button" wire:click="$set('period', '{{ $key }}')" @click="open = false"
                            class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm text-fg transition hover:bg-elevated">
                            <span>{{ $label }}</span>
                            @if ($period === $key)<x-lucide name="check" class="size-4 text-muted" />@endif
                        </button>
                    @endif
                @endforeach
            </div>

            {{-- Range calendar --}}
            <div x-show="cal" x-cloak x-transition.opacity
                class="absolute right-0 z-30 mt-1 w-72 rounded-md border border-border bg-surface p-3 shadow-border">
                <div class="mb-2 flex items-center justify-between">
                    <button type="button" @click="prev()" class="flex size-8 items-center justify-center rounded-md text-muted hover:bg-elevated">
                        <x-lucide name="chevron-left" class="size-4" />
                    </button>
                    <span class="text-sm font-medium capitalize text-fg" x-text="monthLabel()"></span>
                    <button type="button" @click="next()" class="flex size-8 items-center justify-center rounded-md text-muted hover:bg-elevated">
                        <x-lucide name="chevron-right" class="size-4" />
                    </button>
                </div>
                <div class="grid grid-cols-7 gap-0.5 text-center text-[11px] text-subtle">
                    @foreach (['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'] as $wd)<div class="py-1">{{ $wd }}</div>@endforeach
                </div>
                <template x-for="(week, wi) in weeks()" :key="wi">
                    <div class="grid grid-cols-7 gap-0.5">
                        <template x-for="(d, di) in week" :key="di">
                            <button type="button" @click="pick(d)" :disabled="disabled(d)"
                                :class="{
                                    'text-subtle/50': !inMonth(d),
                                    'cursor-not-allowed text-subtle/40': disabled(d),
                                    'bg-accent text-accent-fg': edge(d),
                                    'bg-elevated': inRange(d),
                                    'text-fg hover:bg-elevated': inMonth(d) && !disabled(d) && !edge(d) && !inRange(d),
                                }"
                                class="flex h-8 items-center justify-center rounded-md text-sm tabular-nums transition"
                                x-text="d.getDate()"></button>
                        </template>
                    </div>
                </template>
            </div>
        </div>

    </div>
</div>
