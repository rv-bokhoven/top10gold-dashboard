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

    /**
     * Conversie-log (individuele conversies met tijdstip). Gebruikt voor de
     * lpclick-alert. Geeft de ruwe records terug.
     *
     * @return array<int, array<string, mixed>>
     */
    public function conversions(string $from, string $to, int $per = 1000): array
    {
        if (blank($this->apiKey)) {
            throw new RuntimeException('REDTRACK_API_KEY ontbreekt.');
        }

        $response = $this->request()->get('/conversions', [
            'api_key' => $this->apiKey,
            'date_from' => $from,
            'date_to' => $to,
            'per' => $per,
        ]);

        if ($response->failed()) {
            throw new RuntimeException("RedTrack /conversions gaf status {$response->status()}.");
        }

        $data = $response->json();

        return $data['items'] ?? $data ?? [];
    }

    /**
     * Laatste lpclick-tijdstip per Google-campagne-id (uit sub6).
     *
     * @return array<string, \Carbon\CarbonImmutable>
     */
    public function lastLpclickPerCampaign(string $from, string $to): array
    {
        $latest = [];

        foreach ($this->conversions($from, $to) as $c) {
            if (($c['type'] ?? null) !== 'lpclick') {
                continue;
            }

            $campaignId = (string) ($c['sub6'] ?? '');
            $time = $c['created_at'] ?? null;

            if ($campaignId === '' || ! $time) {
                continue;
            }

            $ts = \Carbon\CarbonImmutable::parse($time);

            if (! isset($latest[$campaignId]) || $ts->greaterThan($latest[$campaignId])) {
                $latest[$campaignId] = $ts;
            }
        }

        return $latest;
    }

    protected function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout(30)
            ->retry(3, 1000, throw: false);
    }
}
