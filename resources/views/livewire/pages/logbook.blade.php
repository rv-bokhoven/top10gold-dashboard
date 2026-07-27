<div>
    <flux:heading size="xl">Logbook</flux:heading>
    <flux:subheading class="mb-4">Important changes — shown as markers on the Overview trend chart</flux:subheading>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <form wire:submit="addLogEntry" class="mb-4 flex flex-wrap items-end gap-2">
            <flux:input type="date" wire:model="newLogDate" label="Date" class="max-w-44" />
            <flux:input wire:model="newLogNote" label="Change" placeholder="e.g. Moved Thor Metals to position 1" class="min-w-64 flex-1" />
            <flux:button type="submit" variant="primary" icon="plus">Add</flux:button>
        </form>

        <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
            @forelse ($this->logEntries as $log)
                <div class="flex items-center justify-between gap-3 py-2 text-sm">
                    <div class="flex items-center gap-3">
                        <span class="w-24 shrink-0 tabular-nums text-zinc-500">{{ $log->entry_date->format('d M Y') }}</span>
                        <span class="text-zinc-800 dark:text-zinc-200">{{ $log->note }}</span>
                    </div>
                    <flux:button wire:click="deleteLogEntry({{ $log->id }})" wire:confirm="Delete this log entry?" variant="subtle" size="sm" icon="trash" />
                </div>
            @empty
                <div class="py-6 text-center text-zinc-400">No log entries yet.</div>
            @endforelse
        </div>
    </div>
</div>
