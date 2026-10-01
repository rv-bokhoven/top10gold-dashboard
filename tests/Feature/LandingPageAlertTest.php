<?php

namespace Tests\Feature;

use App\Models\LandingPage;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LandingPageAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['telegram.bot_token' => 'test-token', 'telegram.chat_id' => '123']);

        // Telegram-call stubben; alle overige HTTP (bv. RedTrack conversies)
        // leeg afvangen zodat er geen echte requests uitgaan.
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
            '*' => Http::response([], 200),
        ]);
    }

    private function seedPage(string $url, bool $ok, ?int $status): void
    {
        LandingPage::create([
            'url' => $url,
            'url_hash' => sha1($url),
            'campaigns' => 'Thor Metals',
            'status_code' => $status,
            'ok' => $ok,
            'error' => null,
            'checked_at' => now(),
        ]);
    }

    private function telegramSends(): int
    {
        return collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), 'api.telegram.org'))
            ->count();
    }

    public function test_it_alerts_for_an_offline_page_but_not_an_online_one(): void
    {
        $this->seedPage('https://top10.compare/down', false, 404);
        $this->seedPage('https://top10.compare/up', true, 200);

        $this->artisan('notifications:run')->assertSuccessful();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.telegram.org')
            && str_contains($request['text'] ?? '', 'top10.compare/down')
            && str_contains($request['text'] ?? '', 'HTTP 404'));

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.telegram.org')
            && str_contains($request['text'] ?? '', 'top10.compare/up'));

        $state = json_decode(Setting::get('telegram.landing_state'), true);
        $this->assertArrayHasKey(sha1('https://top10.compare/down'), $state);
    }

    public function test_it_does_not_alert_twice_for_the_same_down_page(): void
    {
        $this->seedPage('https://top10.compare/down', false, 404);

        $this->artisan('notifications:run')->assertSuccessful();
        $this->artisan('notifications:run')->assertSuccessful();

        $this->assertSame(1, $this->telegramSends());
    }

    public function test_recovery_clears_state_without_a_message(): void
    {
        $this->seedPage('https://top10.compare/down', false, 404);
        $this->artisan('notifications:run')->assertSuccessful();

        LandingPage::where('url_hash', sha1('https://top10.compare/down'))
            ->update(['ok' => true, 'status_code' => 200]);

        $this->artisan('notifications:run')->assertSuccessful();

        // Nog steeds maar één melding (de offline-melding); geen herstelmelding.
        $this->assertSame(1, $this->telegramSends());

        $state = json_decode(Setting::get('telegram.landing_state'), true);
        $this->assertArrayNotHasKey(sha1('https://top10.compare/down'), $state);
    }
}
