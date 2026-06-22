<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureNeonSni();
        $this->configureDefaults();
    }

    /**
     * Neon's pooler vereist SNI, maar de libpq in sommige (serverless) PHP-builds
     * stuurt dat niet mee. Workaround: geef de endpoint-id mee via PGOPTIONS, dat
     * libpq automatisch oppakt. We leiden de id af uit de DB-host, zodat dit
     * zonder extra configuratie werkt.
     */
    protected function configureNeonSni(): void
    {
        if (getenv('PGOPTIONS') !== false) {
            return; // al expliciet ingesteld
        }

        $url = config('database.connections.pgsql.url');
        $host = $url ? parse_url($url, PHP_URL_HOST) : config('database.connections.pgsql.host');

        if (! is_string($host) || ! str_contains($host, '.neon.tech')) {
            return;
        }

        // De endpoint-id is de volledige eerste host-label (incl. een eventueel
        // "-pooler"-achtervoegsel), zodat het overeenkomt met wat Neon via SNI ziet.
        $endpoint = explode('.', $host)[0];

        putenv("PGOPTIONS=endpoint={$endpoint}");
        $_ENV['PGOPTIONS'] = $_SERVER['PGOPTIONS'] = "endpoint={$endpoint}";
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
