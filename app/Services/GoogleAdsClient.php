<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleAdsClient
{
    protected ?string $accessToken = null;

    public function __construct(
        protected ?array $config = null,
    ) {
        $this->config ??= config('google_ads');
    }

    /**
     * Voer een GAQL-query uit via searchStream en geef alle rijen (results) plat terug.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $gaql): array
    {
        $customerId = $this->digits($this->config['customer_id']);

        $version = $this->config['api_version'];
        $url = "https://googleads.googleapis.com/{$version}/customers/{$customerId}/googleAds:searchStream";

        $response = Http::withToken($this->accessToken())
            ->withHeaders([
                'developer-token' => $this->config['developer_token'],
                'login-customer-id' => $this->digits($this->config['login_customer_id']),
            ])
            ->timeout(60)
            ->retry(2, 1000, throw: false)
            ->post($url, ['query' => $gaql]);

        if ($response->failed()) {
            throw new RuntimeException(
                "Google Ads searchStream gaf status {$response->status()}: ".$response->body()
            );
        }

        // searchStream geeft een array van batches terug, elk met een "results"-lijst.
        return collect($response->json() ?? [])
            ->flatMap(fn ($batch) => $batch['results'] ?? [])
            ->all();
    }

    /**
     * Dagelijkse stats per advertentie (incl. campagne + ad group) voor de periode.
     *
     * @return array<int, array<string, mixed>>
     */
    public function reportAdsByDate(string $from, string $to): array
    {
        $gaql = <<<GAQL
            SELECT
                segments.date,
                campaign.id, campaign.name,
                ad_group.id, ad_group.name,
                ad_group_ad.ad.id, ad_group_ad.ad.type, ad_group_ad.ad.name,
                metrics.impressions, metrics.clicks, metrics.cost_micros,
                metrics.conversions, metrics.conversions_value
            FROM ad_group_ad
            WHERE segments.date BETWEEN '{$from}' AND '{$to}'
            GAQL;

        return $this->search($gaql);
    }

    /**
     * Wissel het refresh token in voor een access token (OAuth2).
     */
    protected function accessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        foreach (['developer_token', 'client_id', 'client_secret', 'refresh_token'] as $key) {
            if (blank($this->config[$key] ?? null)) {
                throw new RuntimeException("Google Ads config ontbreekt: {$key} (zet GOOGLE_ADS_* in .env).");
            }
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'refresh_token' => $this->config['refresh_token'],
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed() || blank($response->json('access_token'))) {
            throw new RuntimeException('Kon geen Google Ads access token ophalen: '.$response->body());
        }

        return $this->accessToken = $response->json('access_token');
    }

    protected function digits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }
}
