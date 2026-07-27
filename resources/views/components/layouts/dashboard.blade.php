<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    <style>[x-cloak]{display:none!important;}</style>
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-800 antialiased dark:bg-zinc-950 dark:text-zinc-200">
    @php
        $nav = [
            ['route' => 'dashboard', 'label' => 'Overview', 'icon' => 'chart-bar'],
            ['route' => 'offers', 'label' => 'Offers', 'icon' => 'rectangle-stack'],
            ['route' => 'google-ads', 'label' => 'Google Ads', 'icon' => 'megaphone'],
            ['route' => 'landing-pages', 'label' => 'Landing pages', 'icon' => 'globe-alt'],
            ['route' => 'logbook', 'label' => 'Logbook', 'icon' => 'book-open'],
        ];
    @endphp

    <div x-data="{ open: false }" class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside
            :class="open ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 flex w-60 transform flex-col border-r border-zinc-200 bg-white p-4 transition-transform dark:border-zinc-800 dark:bg-zinc-900 lg:static lg:translate-x-0"
        >
            <div class="mb-6 px-2 text-sm font-semibold text-zinc-900 dark:text-white">
                {{ config('app.name') }}
            </div>

            <nav class="flex flex-col gap-1">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" data-nav wire:navigate x-on:click="open = false"
                        @class([
                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                            'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => request()->routeIs($item['route']),
                            'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' => ! request()->routeIs($item['route']),
                        ])>
                        <flux:icon :icon="$item['icon']" class="size-5 shrink-0" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="mt-auto pt-4">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                    <flux:icon icon="arrow-right-start-on-rectangle" class="size-5 shrink-0" />
                    <span>Sign out</span>
                </button>
            </form>
        </aside>

        {{-- Mobile overlay --}}
        <div x-show="open" x-cloak @click="open = false" class="fixed inset-0 z-30 bg-black/30 lg:hidden"></div>

        {{-- Main --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <div class="flex items-center gap-3 border-b border-zinc-200 p-4 lg:hidden dark:border-zinc-800">
                <button type="button" @click="open = true"><flux:icon icon="bars-3" class="size-6" /></button>
                <span class="text-sm font-semibold">{{ config('app.name') }}</span>
            </div>

            <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Behoud de actieve filters (querystring) bij het klikken op een sidebar-link. --}}
    <script>
        document.addEventListener('click', function (e) {
            var link = e.target.closest('a[data-nav]');
            if (link && window.location.search) {
                var url = new URL(link.href, window.location.origin);
                url.search = window.location.search;
                link.setAttribute('href', url.pathname + url.search);
            }
        }, true);
    </script>

    @fluxScripts
</body>
</html>
