<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasDashboardFilters;
use App\Models\OfferPartner;
use App\Models\OfferStat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class OfferPartners extends Component
{
    use HasDashboardFilters;

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

    /**
     * Slaat de partnergegevens van één offer op. Wordt vanuit Alpine
     * aangeroepen ($wire.savePartner) zodat openen/sluiten/typen geen
     * server-round-trips zijn — alleen het opslaan is er één.
     */
    public function savePartner(array $data): array
    {
        // Lege strings uit JS naar null zodat 'nullable' netjes werkt.
        foreach (['payout', 'revshare_pct'] as $numeric) {
            if (($data[$numeric] ?? '') === '') {
                $data[$numeric] = null;
            }
        }

        $validator = Validator::make($data, [
            'offer_id' => 'required|string|max:255',
            'offer_title' => 'nullable|string|max:255',
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

        if ($validator->fails()) {
            return ['ok' => false, 'errors' => $validator->errors()->toArray()];
        }

        $v = $validator->validated();
        $hasRevshare = (bool) ($v['has_revshare'] ?? false);

        OfferPartner::updateOrCreate(
            ['offer_id' => $v['offer_id']],
            [
                'offer_title' => $v['offer_title'] ?: null,
                'partner' => $v['partner'] ?: null,
                'platform_url' => $v['platform_url'] ?: null,
                'contact_name' => $v['contact_name'] ?: null,
                'contact_email' => $v['contact_email'] ?: null,
                'deal_model' => $v['deal_model'] ?: null,
                'payout' => $v['payout'] ?? null,
                'payout_currency' => $v['payout_currency'],
                'has_revshare' => $hasRevshare,
                'revshare_pct' => $hasRevshare ? ($v['revshare_pct'] ?? null) : null,
                'comments' => $v['comments'] ?: null,
            ]
        );

        unset($this->offers);

        return ['ok' => true];
    }

    public function render()
    {
        return view('livewire.pages.offer-partners')->title('Partnerbeheer · '.config('app.name'));
    }
}
