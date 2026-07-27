<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasDashboardFilters;
use App\Models\LogEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class Logbook extends Component
{
    use HasDashboardFilters;

    public string $newLogDate = '';

    public string $newLogNote = '';

    public function mount(): void
    {
        $this->newLogDate = CarbonImmutable::today()->toDateString();
    }

    /** Logboek-items (nieuwste eerst). */
    #[Computed]
    public function logEntries(): Collection
    {
        return LogEntry::orderByDesc('entry_date')->orderByDesc('id')->get();
    }

    public function addLogEntry(): void
    {
        $data = $this->validate([
            'newLogDate' => 'required|date',
            'newLogNote' => 'required|string|max:500',
        ]);

        LogEntry::create([
            'entry_date' => $data['newLogDate'],
            'note' => $data['newLogNote'],
        ]);

        $this->newLogNote = '';
        unset($this->logEntries);
    }

    public function deleteLogEntry(int $id): void
    {
        LogEntry::whereKey($id)->delete();
        unset($this->logEntries);
    }

    public function render()
    {
        return view('livewire.pages.logbook')->title('Logbook · '.config('app.name'));
    }
}
