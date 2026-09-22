<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureDashboardAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_and_partner_pages_render(): void
    {
        $this->withSession([EnsureDashboardAuth::SESSION_KEY => true]);

        $this->get('/offers/partners')->assertOk()->assertSee('Partnerbeheer')->assertSee('Offers');
        $this->get('/offers')->assertOk()->assertSee('Partnerbeheer');
    }
}
