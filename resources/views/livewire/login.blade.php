<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="mb-8 flex flex-col items-center gap-4 text-center">
            <img src="{{ asset('images/logo.svg') }}" alt="top10" class="h-8 w-auto">
            <p class="text-sm text-muted">Log in om de campagnestatistieken te bekijken</p>
        </div>

        <form wire:submit="authenticate" class="flex flex-col gap-4 rounded-lg bg-surface p-6 shadow-border">
            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-xs text-muted">Wachtwoord</label>
                <input id="password" wire:model="password" type="password" autofocus placeholder="Gedeeld wachtwoord"
                    class="h-10 rounded-md border border-border bg-surface px-3 text-sm text-fg placeholder:text-subtle focus:border-border-strong focus:outline-none focus:ring-1 focus:ring-border-strong/20">
                @error('password') <span class="text-xs text-negative">{{ $message }}</span> @enderror
            </div>

            <button type="submit"
                class="inline-flex h-10 w-full items-center justify-center rounded-md bg-accent px-4 text-sm font-medium text-accent-fg transition hover:opacity-90">
                Inloggen
            </button>
        </form>
    </div>
</div>
