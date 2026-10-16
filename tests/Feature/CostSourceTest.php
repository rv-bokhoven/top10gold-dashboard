<?php

namespace Tests\Feature;

use App\Livewire\Pages\Overview;
use App\Models\GoogleAdStat;
use App\Models\OfferStat;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CostSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-10-10 12:00:00');
        // Koers 1.0 zodat USD (RedTrack) en EUR (Google Ads) 1-op-1 weergegeven
        // worden en de test niet afhangt van de FX-koers.
        Setting::put('fx.eur_usd', 1.0);
        Http::fake(['*' => Http::response(['items' => []])]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function googleAdStat(array $overrides): void
    {
        GoogleAdStat::create(array_merge([
            'stat_date' => '2026-10-05',
            'campaign_id' => 'camp-a',
            'campaign_name' => 'us-gold-search',
            'ad_group_id' => '',
            'ad_group_name' => null,
            'ad_id' => 'ad-1',
            'ad_name' => null,
            'ad_type' => 'TEXT_AD',
            'impressions' => 100,
            'clicks' => 10,
            'cost' => 0,
            'conversions' => 0,
            'conversions_value' => 0,
            'conv_lpclick' => 0,
            'conv_lead' => 0,
            'conv_qlead' => 0,
            'conv_sale' => 0,
        ], $overrides));
    }

    public function test_google_campaign_cost_comes_from_google_ads_not_redtrack(): void
    {
        OfferStat::create([
            'stat_date' => '2026-10-05',
            'offer_id' => OfferStat::CAMPAIGN,
            'source' => 'google',
            'campaign_id' => 'camp-a',
            'cost' => 1234,
        ]);

        $this->googleAdStat(['cost' => 789]);

        Livewire::test(Overview::class)
            ->assertSee('789')        // echte Google Ads-spend
            ->assertDontSee('1.234'); // niet de RedTrack-kost
    }

    public function test_cost_falls_back_to_redtrack_without_google_ads_data(): void
    {
        OfferStat::create([
            'stat_date' => '2026-10-05',
            'offer_id' => OfferStat::CAMPAIGN,
            'source' => 'google',
            'campaign_id' => 'camp-b',
            'cost' => 1515,
        ]);

        // Geen google_ad_stats → RedTrack-kost blijft staan.
        Livewire::test(Overview::class)
            ->assertSee('1.515');
    }
}
