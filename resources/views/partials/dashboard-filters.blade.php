@php
    [$rangeFrom, $rangeTo] = $this->range();
    $sourceOptions = ['all' => 'All'] + collect(config('redtrack.sources'))->map(fn ($c) => $c['label'])->all();
    $currentSourceLabel = $sourceOptions[$source] ?? 'All';
@endphp

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <flux:subheading>
        {{ $source === 'all' ? 'All sources' : $currentSourceLabel.' traffic' }} ·
        {{ \Carbon\CarbonImmutable::parse($rangeFrom)->format('d M Y') }} – {{ \Carbon\CarbonImmutable::parse($rangeTo)->format('d M Y') }}
    </flux:subheading>

    <div class="flex shrink-0 items-center gap-2">
        <div class="inline-flex h-8 shrink-0 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            @foreach ($sourceOptions as $val => $label)
                <button type="button" wire:click="$set('source', '{{ $val }}')"
                    @class([
                        'inline-flex items-center px-3 text-sm font-medium transition',
                        'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $source === $val,
                        'bg-white text-zinc-600 hover:bg-zinc-50 dark:bg-zinc-900 dark:text-zinc-300' => $source !== $val,
                    ])>{{ $label }}</button>
            @endforeach
        </div>

        <flux:select wire:model.live="period" size="sm" class="max-w-44">
            @foreach ($this::PERIODS as $value => $label)
                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        @if ($period === 'custom')
            <flux:input type="date" wire:model.live="from" size="sm" />
            <span class="text-zinc-400">–</span>
            <flux:input type="date" wire:model.live="to" size="sm" />
        @endif

        <div wire:loading.flex class="hidden items-center gap-1 text-xs text-zinc-500">
            <flux:icon icon="arrow-path" class="size-4 animate-spin" />
        </div>

        <div class="inline-flex h-8 shrink-0 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            @foreach (['USD' => '$', 'EUR' => '€'] as $val => $label)
                <button type="button" wire:click="$set('currency', '{{ $val }}')"
                    @class([
                        'inline-flex items-center px-3 text-sm font-medium transition',
                        'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $currency === $val,
                        'bg-white text-zinc-600 hover:bg-zinc-50 dark:bg-zinc-900 dark:text-zinc-300' => $currency !== $val,
                    ])>{{ $label }}</button>
            @endforeach
        </div>

        @if ($this->syncedAt)
            <span class="hidden text-xs text-zinc-500 sm:inline">{{ $lastSyncMessage ?? 'Updated '.$this->syncedAt->diffForHumans() }}</span>
        @endif

        <flux:button wire:click="refreshData" wire:loading.attr="disabled" wire:target="refreshData" icon="arrow-path" size="sm">
            <span wire:loading.remove wire:target="refreshData">Refresh</span>
            <span wire:loading wire:target="refreshData">…</span>
        </flux:button>
    </div>
</div>
