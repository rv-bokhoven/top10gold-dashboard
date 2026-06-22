<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <flux:heading size="xl">{{ config('app.name') }}</flux:heading>
            <flux:subheading>Sign in to view the campaign stats</flux:subheading>
        </div>

        <form wire:submit="authenticate" class="flex flex-col gap-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <flux:input
                wire:model="password"
                type="password"
                label="Password"
                placeholder="Shared password"
                autofocus
            />

            <flux:button type="submit" variant="primary" class="w-full">
                Sign in
            </flux:button>
        </form>
    </div>
</div>
