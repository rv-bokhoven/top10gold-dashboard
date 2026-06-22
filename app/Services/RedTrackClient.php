<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RedTrackClient
{
    public function __construct(
        protected ?string $apiKey = null,
        protected ?string $baseUrl = null,
        protected ?string $rtSource = null,
    ) {
        $this->apiKey ??= config('redtrack.api_key');
        $this->baseUrl ??= rtrim(config('redtrack.base_url'), '/');
        $this->rtSource ??= config('redtrack.rt_source');
    }

    /**
     * Haal een rapport op. De api_key en rt_source worden automatisch toegevoegd.
     *
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>
     */
    public function report(array $params): array
    {
        if (blank($this->apiKey)) {
            throw new RuntimeException('REDTRACK_API_KEY ontbreekt. Zet de key in je .env.');
        }

        $params = array_merge([
            'api_key' => $this->apiKey,
            'rt_source' => $this->rtSource,
        ], $params);

        $response = $this->request()->get('/report', $params);

        if ($response->failed()) {
            throw new RuntimeException(
                "RedTrack /report gaf status {$response->status()}: ".$response->body()
            );
        }

        return $response->json() ?? [];
    }

    /**
     * Rapport gegroepeerd op datum én offer: één rij per (datum, offer).
     * LP Views + cost staan op de campagne-rij (lege offer); LP Clicks +
     * conversies attribueren naar de offers.
     *
     * @return array<int, array<string, mixed>>
     */
    public function reportByDateOffer(string $from, string $to): array
    {
        return $this->report([
            'group' => 'date,offer',
            'date_from' => $from,
            'date_to' => $to,
        ]);
    }

    protected function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout(30)
            ->retry(3, 1000, throw: false);
    }
}
