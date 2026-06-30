<?php

namespace App\Services;

use App\Models\GoogleAdStat;
use Carbon\CarbonImmutable;
use Throwable;

class CampaignMonitor
{
    public function __construct(protected RedTrackClient $redtrack) {}

    /**
     * Actieve campagnes (>= min lpclicks in laatste 24u) die langer dan de
     * drempel geen lpclick-conversie meer hadden.
     *
     * @return array<int, array{campaign_id: string, campaign: string, last: ?CarbonImmutable, hours: ?int}>
     */
    public function silentCampaigns(): array
    {
        $hours = (int) config('redtrack.lpclick_alert_hours', 4);
        $minDaily = (int) config('redtrack.lpclick_alert_min_daily', 10);

        try {
            $records = $this->redtrack->conversions(
                CarbonImmutable::today()->subDay()->toDateString(),
                CarbonImmutable::tomorrow()->toDateString(),
            );
        } catch (Throwable $e) {
            return [];
        }

        $cutoff = CarbonImmutable::now()->subDay();
        $byCampaign = [];

        foreach ($records as $c) {
            if (($c['type'] ?? null) !== 'lpclick') {
                continue;
            }
            $cid = (string) ($c['sub6'] ?? '');
            $time = $c['created_at'] ?? null;
            if ($cid === '' || ! $time) {
                continue;
            }
            $ts = CarbonImmutable::parse($time);
            if ($ts->lessThan($cutoff)) {
                continue;
            }
            $byCampaign[$cid]['count'] = ($byCampaign[$cid]['count'] ?? 0) + 1;
            if (! isset($byCampaign[$cid]['last']) || $ts->greaterThan($byCampaign[$cid]['last'])) {
                $byCampaign[$cid]['last'] = $ts;
            }
        }

        $names = GoogleAdStat::query()->whereNotNull('campaign_name')->pluck('campaign_name', 'campaign_id');
        $now = CarbonImmutable::now();
        $alerts = [];

        foreach ($byCampaign as $cid => $data) {
            if ($data['count'] < $minDaily) {
                continue;
            }
            $hoursSince = (int) abs($now->diffInHours($data['last']));
            if ($hoursSince >= $hours) {
                $alerts[] = [
                    'campaign_id' => $cid,
                    'campaign' => $names[$cid] ?? ('Campaign '.$cid),
                    'last' => $data['last'],
                    'hours' => $hoursSince,
                ];
            }
        }

        return $alerts;
    }
}
