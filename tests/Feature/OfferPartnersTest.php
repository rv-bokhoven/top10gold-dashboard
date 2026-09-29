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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'offer_id' => 'priority-gold',
            'offer_title' => 'Priority Gold',
            'partner' => '',
            'platform_url' => '',
            'contact_name' => '',
            'contact_email' => '',
            'deal_model' => '',
            'payout' => '',
            'payout_currency' => 'USD',
            'has_revshare' => false,
            'revshare_pct' => '',
            'comments' => '',
        ], $overrides);
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
            ->call('savePartner', $this->payload([
                'partner' => 'Priority Gold LLC',
                'contact_email' => 'deals@prioritygold.com',
                'deal_model' => 'cpql',
                'payout' => '25',
                'has_revshare' => true,
                'revshare_pct' => '20',
            ]))
            ->assertReturned(['ok' => true]);

        $partner = OfferPartner::where('offer_id', 'priority-gold')->first();
        $this->assertNotNull($partner);
        $this->assertSame('Priority Gold LLC', $partner->partner);
        $this->assertSame('cpql', $partner->deal_model);
        $this->assertTrue($partner->has_revshare);
        $this->assertSame('CPQL $ 25,00 + 20% revshare', $partner->dealSummary());
    }

    public function test_it_returns_validation_errors_for_a_bad_email(): void
    {
        $this->seedOffer();

        Livewire::test(OfferPartners::class)
            ->call('savePartner', $this->payload(['contact_email' => 'not-an-email']))
            ->assertReturned(fn ($result) => $result['ok'] === false && isset($result['errors']['contact_email']));

        $this->assertNull(OfferPartner::where('offer_id', 'priority-gold')->first());
    }
}
