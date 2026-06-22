<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureDashboardAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_redirects_to_login_when_not_authenticated(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_login_page_is_accessible(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_dashboard_is_accessible_when_authenticated(): void
    {
        $this->withSession([EnsureDashboardAuth::SESSION_KEY => true])
            ->get('/')
            ->assertOk();
    }
}
