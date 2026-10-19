<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Kleine, gedeelde cache voor afgeleide dashboard-statistieken.
 *
 * Op Vercel gebruiken we de database-cache: in-memory cache verdwijnt daar
 * namelijk na elk serverless verzoek. De database-cache deserialiseert om
 * veiligheidsredenen geen PHP-objecten, dus deze service slaat uitsluitend
 * arrays en scalar waarden op.
 *
 * Invalidatie gebeurt via een versienummer: een sync hoogt de versie op
 * (goedkoop) i.p.v. de hele cache te flushen. Afgeleide (DB-)caches hangen aan
 * die versie en vervallen zo meteen; dure live-API-caches (rememberStable)
 * hangen er NIET aan en blijven dus tussen syncs bewaard.
 */
class DashboardCache
{
    private const TTL_MINUTES = 15;

    private const VERSION_KEY = 'dashboard.cache_version';

    /** Versie één keer per request lezen (scheelt cache-round-trips). */
    private static ?int $version = null;

    /** Versie-gebonden cache: wordt bij elke sync ongeldig. */
    public function remember(string $key, Closure $callback): mixed
    {
        return $this->cacheGet('dashboard.v'.$this->version().'.'.$key, self::TTL_MINUTES, $callback);
    }

    /**
     * Stabiele cache met eigen TTL die NIET door een sync wordt geïnvalideerd.
     * Voor dure, traag wisselende live-API-data (campagne-lijst, change history).
     */
    public function rememberStable(string $key, int $ttlMinutes, Closure $callback): mixed
    {
        return $this->cacheGet('dashboard.stable.'.$key, $ttlMinutes, $callback);
    }

    public function clear(): void
    {
        // Versie ophogen i.p.v. Cache::flush(): versie-gebonden keys worden
        // meteen onbereikbaar (en verlopen via hun TTL), stabiele caches blijven.
        $next = $this->version() + 1;
        Cache::forever(self::VERSION_KEY, $next);
        self::$version = $next;
    }

    private function version(): int
    {
        return self::$version ??= (int) (Cache::get(self::VERSION_KEY) ?: 1);
    }

    private function cacheGet(string $cacheKey, int $ttlMinutes, Closure $callback): mixed
    {
        $missing = new \stdClass;
        $value = Cache::get($cacheKey, $missing);

        if ($value !== $missing) {
            // Oude cachewaarden konden Collections bevatten. Laravel leest die
            // als __PHP_Incomplete_Class terug wanneer objecten niet zijn
            // toegestaan. Verwijder ze automatisch en bouw ze opnieuw op.
            if ($this->isSafe($value)) {
                return $value;
            }

            Cache::forget($cacheKey);
        }

        $value = $callback();

        if ($this->isSafe($value)) {
            Cache::put($cacheKey, $value, now()->addMinutes($ttlMinutes));
        }

        return $value;
    }

    /** Alleen waarden die de database-cache veilig kan teruglezen. */
    private function isSafe(mixed $value): bool
    {
        if (is_null($value) || is_scalar($value)) {
            return true;
        }

        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (! $this->isSafe($item)) {
                return false;
            }
        }

        return true;
    }
}
