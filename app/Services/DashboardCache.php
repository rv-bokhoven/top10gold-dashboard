<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Kleine, gedeelde cache voor afgeleide dashboard-statistieken.
 *
 * Op Vercel gebruiken we de database-cache: in-memory cache verdwijnt daar
 * namelijk na elk serverless verzoek. De RedTrack-sync wist deze cache zodra
 * er verse data is opgeslagen.
 */
class DashboardCache
{
    private const TTL_MINUTES = 15;

    public function remember(string $key, Closure $callback): mixed
    {
        return Cache::remember(
            'dashboard.'.$key,
            now()->addMinutes(self::TTL_MINUTES),
            $callback,
        );
    }

    public function clear(): void
    {
        // De database-cache is alleen voor dit dashboard gereserveerd.
        Cache::flush();
    }
}
