<?php

namespace Tests\Feature;

use App\Livewire\Pages\OfferPartners;
use App\Models\OfferPartner;
use App\Models\OfferStat;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OfferPartnersTest extends TestCase
{
    use RefreshDatabase;

    private function seedOffer(): void
    {
        OfferStat::create([
            'stat_date' => CarbonImmutable::yesterday()->toDateString(),
            'offer_id' => 'priority-gold',
            'offer_title' => 'Priority Gold',
            'source' => 'google',
            'lp_clicks' => 10,
            'leads' => 2,
        ]);
    }

    public function test_it_lists_offers_from_stats(): void
    {
        $this->seedOffer();

        Livewire::test(OfferPartners::class)
            ->assertSee('Priority Gold')
            ->assertSee('Invullen');
    }

    public function test_it_saves_partner_details_for_an_offer(): void
    {
        $this->seedOffer();

        Livewire::test(OfferPartners::class)
            ->call('edit', 'priority-gold')
            ->set('partner', 'Priority Gold LLC')
            ->set('contact_email', 'deals@prioritygold.com')
            ->set('deal_model', 'cpql')
            ->set('payout', '25')
            ->set('payout_currency', 'USD')
            ->set('has_revshare', true)
            ->set('revshare_pct', '20')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('editingOfferId', null);

        $partner = OfferPartner::where('offer_id', 'priority-gold')->first();
        $this->assertNotNull($partner);
        $this->assertSame('Priority Gold LLC', $partner->partner);
        $this->assertSame('cpql', $partner->deal_model);
        $this->assertTrue($partner->has_revshare);
        $this->assertSame('CPQL $ 25,00 + 20% revshare', $partner->dealSummary());
    }

    public function test_it_validates_the_contact_email(): void
    {
        $this->seedOffer();

        Livewire::test(OfferPartners::class)
            ->call('edit', 'priority-gold')
            ->set('contact_email', 'not-an-email')
            ->call('save')
            ->assertHasErrors(['contact_email' => 'email']);
    }
}
