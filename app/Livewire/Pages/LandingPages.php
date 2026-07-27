<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasDashboardFilters;
use App\Models\LandingPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class LandingPages extends Component
{
    use HasDashboardFilters;

    /** Landingspagina's van de live ads + hun online-status. */
    #[Computed]
    public function landingPages(): Collection
    {
        return LandingPage::orderBy('ok')->orderBy('url')->get();
    }

    public function checkLandingPages(): void
    {
        Artisan::call('landing-pages:check');
        unset($this->landingPages);
        $this->dispatch('landing-pages-checked');
    }

    public function render()
    {
        return view('livewire.pages.landing-pages')->title('Landing pages · '.config('app.name'));
    }
}
