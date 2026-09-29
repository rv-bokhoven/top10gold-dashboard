<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasDashboardFilters;
use App\Models\OfferPartner;
use App\Models\OfferStat;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class OfferPartners extends Component
{
    use HasDashboardFilters;

    public ?string $editingOfferId = null;

    public string $editingOfferTitle = '';

    // Formuliervelden
    public string $partner = '';

    public string $platform_url = '';

    public string $contact_name = '';

    public string $contact_email = '';

    public string $deal_model = '';

    public string $payout = '';

    public string $payout_currency = 'USD';

    public bool $has_revshare = false;

    public string $revshare_pct = '';

    public string $comments = '';

    /** Alle offers uit de stats, verrijkt met partnergegevens. */
    #[Computed]
    public function offers(): Collection
    {
        $partners = OfferPartner::all()->keyBy('offer_id');

        $offers = OfferStat::query()
            ->offers()
            ->selectRaw('offer_id, MAX(offer_title) as offer_title')
            ->groupBy('offer_id')
            ->get()
            ->keyBy('offer_id');

        // Partners zonder (meer) actieve stats toch tonen.
        foreach ($partners as $offerId => $partner) {
            if (! $offers->has($offerId)) {
                $offers->put($offerId, (object) [
                    'offer_id' => $offerId,
                    'offer_title' => $partner->offer_title,
                ]);
            }
        }

        return $offers
            ->map(function ($offer) use ($partners) {
                $offer->partner_data = $partners->get($offer->offer_id);

                return $offer;
            })
            ->sortBy(fn ($o) => strtolower((string) ($o->offer_title ?: $o->offer_id)))
            ->values();
    }

    public function edit(string $offerId): void
    {
        $offer = $this->offers->firstWhere('offer_id', $offerId);
        $this->editingOfferId = $offerId;
        $this->editingOfferTitle = $offer->offer_title ?? $offerId;

        $partner = OfferPartner::where('offer_id', $offerId)->first();

        // Nog geen partner ingevuld? Begin met de offernaam als suggestie.
        $this->partner = $partner->partner ?? $this->editingOfferTitle;
        $this->platform_url = $partner->platform_url ?? '';
        $this->contact_name = $partner->contact_name ?? '';
        $this->contact_email = $partner->contact_email ?? '';
        $this->deal_model = $partner->deal_model ?? '';
        $this->payout = $partner && $partner->payout !== null ? (string) (float) $partner->payout : '';
        $this->payout_currency = $partner->payout_currency ?? 'USD';
        $this->has_revshare = (bool) ($partner->has_revshare ?? false);
        $this->revshare_pct = $partner && $partner->revshare_pct !== null ? (string) (float) $partner->revshare_pct : '';
        $this->comments = $partner->comments ?? '';
    }

    public function save(): void
    {
        $data = $this->validate([
            'partner' => 'nullable|string|max:255',
            'platform_url' => 'nullable|url|max:2000',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'deal_model' => 'nullable|in:cpl,cpql',
            'payout' => 'nullable|numeric|min:0',
            'payout_currency' => 'required|in:USD,EUR',
            'has_revshare' => 'boolean',
            'revshare_pct' => 'nullable|numeric|min:0|max:100',
            'comments' => 'nullable|string|max:2000',
        ]);

        OfferPartner::updateOrCreate(
            ['offer_id' => $this->editingOfferId],
            [
                'offer_title' => $this->editingOfferTitle,
                'partner' => $data['partner'] ?: null,
                'platform_url' => $data['platform_url'] ?: null,
                'contact_name' => $data['contact_name'] ?: null,
                'contact_email' => $data['contact_email'] ?: null,
                'deal_model' => $data['deal_model'] ?: null,
                'payout' => $data['payout'] !== '' ? $data['payout'] : null,
                'payout_currency' => $data['payout_currency'],
                'has_revshare' => $data['has_revshare'],
                'revshare_pct' => $data['has_revshare'] && $data['revshare_pct'] !== '' ? $data['revshare_pct'] : null,
                'comments' => $data['comments'] ?: null,
            ]
        );

        $this->cancel();
        unset($this->offers);
    }

    public function cancel(): void
    {
        $this->reset([
            'editingOfferId', 'editingOfferTitle', 'partner', 'platform_url',
            'contact_name', 'contact_email', 'deal_model', 'payout',
            'has_revshare', 'revshare_pct', 'comments',
        ]);
        $this->payout_currency = 'USD';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.pages.offer-partners')->title('Partnerbeheer · '.config('app.name'));
    }
}
