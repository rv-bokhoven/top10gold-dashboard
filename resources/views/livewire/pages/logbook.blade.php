<div class="flex flex-col gap-4 lg:gap-5">
    <div>
        <h1 class="text-base font-normal text-fg">Logboek</h1>
        <p class="text-xs text-subtle">Belangrijke wijzigingen — getoond als markers op de verloopgrafiek</p>
    </div>

    <div class="rounded-lg bg-surface p-4 sm:p-5">
        <form wire:submit="addLogEntry" class="mb-4 flex flex-wrap items-end gap-2">
            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted">Datum</label>
                <input type="date" wire:model="newLogDate" class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg focus:border-border-strong focus:outline-none">
            </div>
            <div class="flex min-w-64 flex-1 flex-col gap-1">
                <label class="text-xs text-muted">Wijziging</label>
                <input type="text" wire:model="newLogNote" placeholder="bijv. Thor Metals op positie 1 gezet" class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none">
            </div>
            <button type="submit" class="inline-flex h-10 items-center gap-1.5 rounded-md bg-accent px-4 text-sm font-medium text-accent-fg transition hover:opacity-90">Toevoegen</button>
        </form>

        <div class="divide-y divide-border/60">
            @forelse ($this->logEntries as $log)
                <div class="flex items-center justify-between gap-3 py-2 text-sm">
                    <div class="flex items-center gap-3">
                        <span class="w-24 shrink-0 tabular-nums text-subtle">{{ $log->entry_date->locale('nl')->isoFormat('D MMM YYYY') }}</span>
                        <span class="text-fg">{{ $log->note }}</span>
                    </div>
                    <button type="button" wire:click="deleteLogEntry({{ $log->id }})" wire:confirm="Deze logregel verwijderen?"
                        class="flex size-8 items-center justify-center rounded-md text-subtle transition hover:bg-elevated hover:text-negative" title="Verwijderen">
                        <x-lucide name="x" class="size-4" />
                    </button>
                </div>
            @empty
                <div class="py-6 text-center text-subtle">Nog geen logregels.</div>
            @endforelse
        </div>
    </div>
</div>
