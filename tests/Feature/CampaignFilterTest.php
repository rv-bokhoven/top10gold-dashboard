<?php

namespace Tests\Feature;

use App\Livewire\Pages\Overview;
use App\Models\OfferStat;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CampaignFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Vaste dag midden in de maand; alle externe calls (campagne-opties,
        // recente leads) leeg afvangen.
        CarbonImmutable::setTestNow('2026-10-10 12:00:00');
        Http::fake(['*' => Http::response(['items' => []])]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function seedLeads(string $campaignId, int $leads): void
    {
        OfferStat::create([
            'stat_date' => '2026-10-05',
            'offer_id' => 'offer-'.$campaignId,
            'offer_title' => 'Offer '.$campaignId,
            'source' => 'google',
            'campaign_id' => $campaignId,
            'leads' => $leads,
        ]);
    }

    public function test_overview_totals_split_per_campaign(): void
    {
        $this->seedLeads('camp-a', 211);
        $this->seedLeads('camp-b', 47);

        // Alle campagnes: som (258).
        Livewire::test(Overview::class)
            ->assertSet('campaign', 'all')
            ->assertSee('258');

        // Alleen campagne A: 211, en niet de som.
        Livewire::test(Overview::class)
            ->set('campaign', 'camp-a')
            ->assertSee('211')
            ->assertDontSee('258');
    }
}
