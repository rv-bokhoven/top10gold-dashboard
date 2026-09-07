<?php

namespace Tests\Feature;

use App\Livewire\Pages\OfferDetail;
use App\Livewire\Pages\Offers;
use App\Models\OfferStat;
use App\Models\StatCorrection;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class OfferDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_offers_default_to_the_current_month(): void
    {
        Livewire::test(Offers::class)
            ->assertSet('period', 'this_month');
    }

    public function test_offer_detail_aggregates_daily_stats_and_corrections(): void
    {
        // SQLite bewaart een Eloquent date-cast als middernacht; kies gisteren
        // zodat de test niet afhankelijk is van een eindgrens op dezelfde dag.
        $date = CarbonImmutable::yesterday()->toDateString();

        OfferStat::create([
            'stat_date' => $date,
            'offer_id' => 'priority-gold',
            'offer_title' => 'Priority Gold',
            'source' => 'google',
            'lp_clicks' => 20,
            'leads' => 4,
            'qleads' => 2,
            'revenue' => 120,
        ]);

        StatCorrection::create([
            'stat_date' => $date,
            'offer_id' => 'priority-gold',
            'source' => 'google',
            'd_leads' => 1,
        ]);

        // RefreshDatabase reset de SQLite-tabellen, maar de in-memory cache
        // van een eerdere test bestaat nog binnen hetzelfde testproces.
        Cache::flush();

        $this->assertSame(20, OfferStat::query()->sum('lp_clicks'));
        $this->assertSame('priority-gold', OfferStat::query()->value('offer_id'));
        $this->assertSame(1, OfferStat::query()
            ->offers()
            ->where('offer_id', 'priority-gold')
            ->whereBetween('stat_date', [CarbonImmutable::today()->startOfMonth()->toDateString(), CarbonImmutable::today()->toDateString()])
            ->count());

        $component = Livewire::test(OfferDetail::class, ['offerId' => 'priority-gold']);
        $this->assertSame(20, (int) $component->instance()->dailyOfferStats->first()->lp_clicks);

        $component
            ->assertSee('Priority Gold')
            ->assertSee('Daily breakdown')
            ->assertSee('25.0%')
            ->assertSee('$120.00');
    }
}
