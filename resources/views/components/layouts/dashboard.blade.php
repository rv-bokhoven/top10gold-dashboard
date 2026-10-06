<!DOCTYPE html>
<html lang="nl" data-flux-appearance="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    <style>[x-cloak]{display:none!important;}</style>
</head>
<body class="min-h-screen bg-bg font-sans text-fg antialiased">
    @php
        $nav = [
            ['route' => 'dashboard', 'label' => 'Overzicht', 'icon' => 'layout-grid'],
            ['route' => 'offers', 'label' => 'Offers', 'icon' => 'panels-top-left', 'children' => [
                ['route' => 'offers', 'label' => 'Overzicht', 'exact' => true],
                ['route' => 'offers.partners', 'label' => 'Partnerbeheer'],
            ]],
            ['route' => 'google-ads', 'label' => 'Google Ads', 'icon' => 'megaphone'],
            ['route' => 'landing-pages', 'label' => "Landingspagina's", 'icon' => 'globe'],
            ['route' => 'logbook', 'label' => 'Logboek', 'icon' => 'book-open'],
        ];
        $navLabels = collect($nav)->pluck('label')->map(fn ($l) => strtolower($l))->values()->toJson();
    @endphp

    <div
        x-data="{
            collapsed: $persist(false).as('pulse.collapsed'),
            search: false,
            mobile: false,
            syncing: false,
            q: '',
            labels: {{ $navLabels }},
            matches(label) { return this.q === '' || label.toLowerCase().includes(this.q.toLowerCase()); },
            get visible() { return this.labels.filter(l => this.matches(l)).length; },
            refresh() { if (this.syncing) return; this.syncing = true; window.Livewire.dispatch('dashboard-refresh'); },
        }"
        @stats-refreshed.window="syncing = false"
        class="flex min-h-screen"
    >
        {{-- Desktop sidebar --}}
        <aside
            :class="collapsed ? 'w-16' : 'w-64'"
            class="sticky top-0 hidden h-screen shrink-0 flex-col overflow-hidden bg-sidebar transition-[width] duration-200 lg:flex"
        >
            {{-- Top: logo + controls --}}
            <div class="flex h-14 shrink-0 items-center gap-1 px-3">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex shrink-0 items-center" :class="collapsed && 'mx-auto'" title="top10">
                    <span class="block size-8 overflow-hidden">
                        <img src="{{ asset('images/logo.svg') }}" alt="top10" class="h-8 w-auto max-w-none">
                    </span>
                </a>
                <div class="ml-auto flex items-center" x-show="!collapsed">
                    <button type="button" @click="search = !search; q = ''"
                        class="flex size-11 items-center justify-center rounded-md text-muted transition hover:bg-elevated"
                        title="Zoeken">
                        <x-lucide name="search" class="size-5" />
                    </button>
                    <button type="button" @click="collapsed = true"
                        class="flex size-11 items-center justify-center rounded-md text-muted transition hover:bg-elevated"
                        title="Inklappen">
                        <x-lucide name="panel-left" class="size-5" />
                    </button>
                </div>
            </div>

            {{-- Expand control when collapsed --}}
            <div x-show="collapsed" class="flex shrink-0 justify-center pb-1">
                <button type="button" @click="collapsed = false"
                    class="flex size-11 items-center justify-center rounded-md text-muted transition hover:bg-elevated"
                    title="Uitklappen">
                    <x-lucide name="panel-left" class="size-5" />
                </button>
            </div>

            {{-- Search --}}
            <div x-show="search && !collapsed" x-cloak class="px-3 pb-2">
                <input type="text" x-model="q" x-ref="searchInput" placeholder="Zoeken…"
                    class="h-9 w-full rounded-md border border-border bg-surface px-3 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none focus:ring-1 focus:ring-ring/20">
            </div>

            {{-- Nav --}}
            <nav class="flex min-h-0 flex-1 flex-col gap-0.5 overflow-y-auto px-3 pt-1">
                @foreach ($nav as $item)
                    @if (empty($item['children']))
                        <a href="{{ route($item['route'], request()->query()) }}" wire:navigate.hover
                            x-show="matches(@js(strtolower($item['label'])))"
                            :title="collapsed ? @js($item['label']) : null"
                            @class([
                                'flex h-11 items-center gap-3 rounded-md px-3 text-sm font-normal transition',
                                'bg-elevated text-fg' => request()->routeIs($item['route']),
                                'text-muted hover:bg-elevated/70' => ! request()->routeIs($item['route']),
                            ])
                            :class="collapsed && 'justify-center px-0'">
                            <x-lucide name="{{ $item['icon'] }}" class="size-4 text-muted" />
                            <span x-show="!collapsed">{{ $item['label'] }}</span>
                        </a>
                    @else
                        @php $groupActive = request()->routeIs($item['route'].'*'); @endphp
                        <div x-data="{ open: @js($groupActive) }" x-show="matches(@js(strtolower($item['label'])))">
                            <button type="button"
                                @click="collapsed ? Livewire.navigate(@js(route($item['route'], request()->query()))) : (open = ! open)"
                                :title="collapsed ? @js($item['label']) : null"
                                @class([
                                    'flex h-11 w-full items-center gap-3 rounded-md px-3 text-left text-sm font-normal transition',
                                    'text-fg' => $groupActive,
                                    'text-muted hover:bg-elevated/70' => ! $groupActive,
                                ])
                                :class="collapsed && 'justify-center px-0'">
                                <x-lucide name="{{ $item['icon'] }}" class="size-4 text-muted" />
                                <span x-show="!collapsed">{{ $item['label'] }}</span>
                                <x-lucide name="chevron-down" class="ml-auto size-4 text-subtle transition-transform" x-show="!collapsed" :class="open && 'rotate-180'" />
                            </button>
                            <div x-show="open && !collapsed" x-cloak class="mt-0.5 flex flex-col gap-0.5">
                                @foreach ($item['children'] as $child)
                                    @php
                                        $childActive = ($child['exact'] ?? false)
                                            ? (request()->routeIs($child['route']) || request()->routeIs('offers.show'))
                                            : request()->routeIs($child['route']);
                                    @endphp
                                    <a href="{{ route($child['route'], request()->query()) }}" wire:navigate.hover
                                        @class([
                                            'flex h-9 items-center rounded-md pl-11 pr-3 text-sm font-normal transition',
                                            'bg-elevated text-fg' => $childActive,
                                            'text-muted hover:bg-elevated/70' => ! $childActive,
                                        ])>
                                        {{ $child['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
                <p x-show="!collapsed && visible === 0" x-cloak class="px-3 py-2 text-sm text-subtle">Geen resultaten</p>
            </nav>

            {{-- Bottom --}}
            <div class="mt-auto shrink-0 px-3 pb-4 pt-2">
                <button type="button" @click="refresh()" :disabled="syncing"
                    class="flex h-11 w-full items-center gap-3 rounded-md px-3 text-sm font-normal text-muted transition hover:bg-elevated/70 disabled:opacity-60"
                    :class="collapsed && 'justify-center px-0'"
                    :title="collapsed ? 'Verversen' : null">
                    <x-lucide name="refresh" class="size-4 text-muted" :class="syncing && 'animate-spin'" />
                    <span x-show="!collapsed" x-text="syncing ? 'Bezig…' : 'Verversen'"></span>
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="flex h-11 w-full items-center gap-3 rounded-md px-3 text-sm font-normal text-muted transition hover:bg-elevated/70"
                        :class="collapsed && 'justify-center px-0'"
                        :title="collapsed ? 'Uitloggen' : null">
                        <x-lucide name="log-out" class="size-4 text-muted" />
                        <span x-show="!collapsed">Uitloggen</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Mobile sheet --}}
        <div x-show="mobile" x-cloak @click="mobile = false" class="fixed inset-0 z-40 bg-black/30 lg:hidden"></div>
        <aside x-show="mobile" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-sidebar lg:hidden">
            <div class="flex h-14 shrink-0 items-center gap-2 px-4 text-fg">
                <span class="block size-8 overflow-hidden">
                    <img src="{{ asset('images/logo.svg') }}" alt="top10" class="h-8 w-auto max-w-none">
                </span>
                <button type="button" @click="mobile = false" class="ml-auto flex size-9 items-center justify-center rounded-md text-muted hover:bg-elevated">
                    <x-lucide name="x" class="size-5" />
                </button>
            </div>
            <nav class="flex min-h-0 flex-1 flex-col gap-0.5 overflow-y-auto px-3 pt-1">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route'], request()->query()) }}" wire:navigate.hover @click="mobile = false"
                        @class([
                            'flex h-11 items-center gap-3 rounded-md px-3 text-sm font-normal transition',
                            'bg-elevated text-fg' => empty($item['children']) && request()->routeIs($item['route']),
                            'text-fg' => ! empty($item['children']) && request()->routeIs($item['route'].'*'),
                            'text-muted hover:bg-elevated/70' => empty($item['children']) ? ! request()->routeIs($item['route']) : ! request()->routeIs($item['route'].'*'),
                        ])>
                        <x-lucide name="{{ $item['icon'] }}" class="size-4 text-muted" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                    @if (! empty($item['children']))
                        <div class="flex flex-col gap-0.5">
                            @foreach ($item['children'] as $child)
                                @php
                                    $childActive = ($child['exact'] ?? false)
                                        ? (request()->routeIs($child['route']) || request()->routeIs('offers.show'))
                                        : request()->routeIs($child['route']);
                                @endphp
                                <a href="{{ route($child['route'], request()->query()) }}" wire:navigate.hover @click="mobile = false"
                                    @class([
                                        'flex h-9 items-center rounded-md pl-11 pr-3 text-sm font-normal transition',
                                        'bg-elevated text-fg' => $childActive,
                                        'text-muted hover:bg-elevated/70' => ! $childActive,
                                    ])>
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </nav>
            <div class="mt-auto shrink-0 px-3 pb-4 pt-2">
                <button type="button" @click="refresh()" :disabled="syncing"
                    class="flex h-11 w-full items-center gap-3 rounded-md px-3 text-sm font-normal text-muted transition hover:bg-elevated/70 disabled:opacity-60">
                    <x-lucide name="refresh" class="size-4 text-muted" :class="syncing && 'animate-spin'" />
                    <span x-text="syncing ? 'Bezig…' : 'Verversen'"></span>
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex h-11 w-full items-center gap-3 rounded-md px-3 text-sm font-normal text-muted transition hover:bg-elevated/70">
                        <x-lucide name="log-out" class="size-4 text-muted" />
                        <span>Uitloggen</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Mobile top bar --}}
            <div class="flex items-center gap-3 px-4 py-3 lg:hidden">
                <button type="button" @click="mobile = true" class="flex size-9 items-center justify-center rounded-md text-muted hover:bg-elevated">
                    <x-lucide name="menu" class="size-6" />
                </button>
                <span class="block size-7 overflow-hidden">
                    <img src="{{ asset('images/logo.svg') }}" alt="top10" class="h-7 w-auto max-w-none">
                </span>
            </div>

            <main class="mx-auto flex w-full max-w-[1600px] flex-1 flex-col gap-4 px-4 py-4 pb-10 lg:gap-5 lg:px-8 lg:py-6">
                {{ $slot }}
            </main>
        </div>
    </div>
    @fluxScripts
</body>
</html>
