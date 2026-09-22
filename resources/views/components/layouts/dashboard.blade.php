<!DOCTYPE html>
<html lang="nl" data-flux-appearance="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>

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
            ['route' => 'offers', 'label' => 'Offers', 'icon' => 'panels-top-left'],
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
            q: '',
            labels: {{ $navLabels }},
            matches(label) { return this.q === '' || label.toLowerCase().includes(this.q.toLowerCase()); },
            get visible() { return this.labels.filter(l => this.matches(l)).length; },
        }"
        class="flex min-h-screen"
    >
        {{-- Desktop sidebar --}}
        <aside
            :class="collapsed ? 'w-16' : 'w-64'"
            class="hidden shrink-0 flex-col bg-sidebar transition-[width] duration-200 lg:flex"
        >
            {{-- Top: compass + controls --}}
            <div class="flex h-14 items-center gap-1 px-3" :class="collapsed && 'flex-col justify-center gap-2 py-2'">
                <div class="flex size-11 items-center justify-center text-fg">
                    <x-lucide name="compass" class="size-5" />
                </div>
                <div class="ml-auto flex items-center" :class="collapsed && 'ml-0'">
                    <button type="button" x-show="!collapsed" @click="search = !search; q = ''"
                        class="flex size-11 items-center justify-center rounded-md text-muted transition hover:bg-elevated"
                        title="Zoeken">
                        <x-lucide name="search" class="size-5" />
                    </button>
                    <button type="button" @click="collapsed = !collapsed"
                        class="flex size-11 items-center justify-center rounded-md text-muted transition hover:bg-elevated"
                        title="Inklappen">
                        <x-lucide name="panel-left" class="size-5" />
                    </button>
                </div>
            </div>

            {{-- Search --}}
            <div x-show="search && !collapsed" x-cloak class="px-3 pb-2">
                <input type="text" x-model="q" x-ref="searchInput" placeholder="Zoeken…"
                    class="h-9 w-full rounded-md border border-border bg-surface px-3 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none focus:ring-1 focus:ring-ring/20">
            </div>

            {{-- Nav --}}
            <nav class="flex flex-1 flex-col gap-0.5 px-3 pt-1">
                @foreach ($nav as $item)
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
                @endforeach
                <p x-show="!collapsed && visible === 0" x-cloak class="px-3 py-2 text-sm text-subtle">Geen resultaten</p>
            </nav>

            {{-- Bottom --}}
            <div class="mt-auto px-3 pb-4">
                <p x-show="!collapsed" class="px-3 pb-1 text-xs text-subtle">Projecten</p>
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
            <div class="flex h-14 items-center gap-2 px-4 text-fg">
                <x-lucide name="compass" class="size-5" />
                <span class="text-sm font-semibold">Pulse</span>
                <button type="button" @click="mobile = false" class="ml-auto flex size-9 items-center justify-center rounded-md text-muted hover:bg-elevated">
                    <x-lucide name="x" class="size-5" />
                </button>
            </div>
            <nav class="flex flex-1 flex-col gap-0.5 px-3 pt-1">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route'], request()->query()) }}" wire:navigate.hover @click="mobile = false"
                        @class([
                            'flex h-11 items-center gap-3 rounded-md px-3 text-sm font-normal transition',
                            'bg-elevated text-fg' => request()->routeIs($item['route']),
                            'text-muted hover:bg-elevated/70' => ! request()->routeIs($item['route']),
                        ])>
                        <x-lucide name="{{ $item['icon'] }}" class="size-4 text-muted" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
            <div class="mt-auto px-3 pb-4">
                <p class="px-3 pb-1 text-xs text-subtle">Projecten</p>
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
                <span class="flex items-center gap-2 text-sm font-semibold text-fg">
                    <x-lucide name="compass" class="size-4" /> Pulse
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
