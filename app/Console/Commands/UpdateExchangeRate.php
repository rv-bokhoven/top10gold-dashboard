<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\DashboardCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class UpdateExchangeRate extends Command
{
    protected $signature = 'fx:update';

    protected $description = 'Haal de actuele EUR/USD-wisselkoers op (ECB) en sla die op';

    public function handle(DashboardCache $cache): int
    {
        try {
            $response = Http::timeout(15)->get('https://api.frankfurter.dev/v1/latest', [
                'base' => 'EUR',
                'symbols' => 'USD',
            ]);

            $rate = $response->json('rates.USD');

            if (! $rate) {
                $this->warn('Geen koers ontvangen.');

                return self::SUCCESS;
            }

            Setting::put('fx.eur_usd', (string) $rate);
            Setting::put('fx.updated_at', now()->toIso8601String());
            $cache->clear();
            $this->info("EUR/USD = {$rate}");
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }

        return self::SUCCESS;
    }
}
