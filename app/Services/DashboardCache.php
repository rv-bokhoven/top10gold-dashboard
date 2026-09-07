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
 */
class DashboardCache
{
    private const TTL_MINUTES = 15;

    public function remember(string $key, Closure $callback): mixed
    {
        $cacheKey = 'dashboard.'.$key;
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
            Cache::put($cacheKey, $value, now()->addMinutes(self::TTL_MINUTES));
        }

        return $value;
    }

    public function clear(): void
    {
        // De database-cache is alleen voor dit dashboard gereserveerd.
        Cache::flush();
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
