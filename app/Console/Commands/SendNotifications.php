<?php

namespace App\Console\Commands;

use App\Models\GoogleAdStat;
use App\Models\Setting;
use App\Services\CampaignMonitor;
use App\Services\RedTrackClient;
use App\Services\TelegramNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class SendNotifications extends Command
{
    protected $signature = 'notifications:run';

    protected $description = 'Stuur Telegram-meldingen: nieuwe conversies (lead/qlead/sale) + campagne-waarschuwingen';

    /** Conversie-types waarvoor we melden, met label. */
    protected array $types = ['lead' => 'Lead', 'qlead' => 'Q-Lead', 'sale' => 'Sale'];

    public function handle(TelegramNotifier $telegram, CampaignMonitor $monitor, RedTrackClient $redtrack): int
    {
        if (! $telegram->configured()) {
            $this->warn('Telegram niet geconfigureerd (TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID).');

            return self::SUCCESS;
        }

        $this->notifyConversions($telegram, $redtrack);
        $this->notifyAlerts($telegram, $monitor);

        return self::SUCCESS;
    }

    protected function notifyConversions(TelegramNotifier $telegram, RedTrackClient $redtrack): void
    {
        $watermark = Setting::get('telegram.conv_watermark');

        // Eerste run: watermerk op nu zetten en geen historie sturen.
        if (! $watermark) {
            Setting::put('telegram.conv_watermark', CarbonImmutable::now()->toIso8601String());

            return;
        }

        $watermarkTs = CarbonImmutable::parse($watermark);

        try {
            $records = $redtrack->conversions(
                CarbonImmutable::today()->subDay()->toDateString(),
                CarbonImmutable::tomorrow()->toDateString(),
            );
        } catch (Throwable $e) {
            return;
        }

        $names = GoogleAdStat::query()->whereNotNull('campaign_name')->pluck('campaign_name', 'campaign_id');

        $new = [];
        $maxTs = $watermarkTs;

        foreach ($records as $c) {
            $type = $c['type'] ?? null;
            if (! isset($this->types[$type])) {
                continue;
            }
            $time = $c['created_at'] ?? null;
            if (! $time) {
                continue;
            }
            $ts = CarbonImmutable::parse($time);
            if (! $ts->greaterThan($watermarkTs)) {
                continue;
            }

            $campaign = $names[(string) ($c['sub6'] ?? '')] ?? ($c['campaign'] ?? 'onbekend');
            $new[] = ['type' => $type, 'campaign' => $campaign, 'ts' => $ts, 'payout' => (float) ($c['payout'] ?? 0)];

            if ($ts->greaterThan($maxTs)) {
                $maxTs = $ts;
            }
        }

        if (empty($new)) {
            return;
        }

        usort($new, fn ($a, $b) => $a['ts'] <=> $b['ts']);

        $lines = ['🟢 <b>'.count($new).' nieuwe conversie(s)</b>'];
        foreach ($new as $n) {
            $line = '• '.$this->types[$n['type']].' — '.e($n['campaign']).' ('.$n['ts']->format('H:i').')';
            if ($n['payout'] > 0) {
                $line .= ' — $'.number_format($n['payout'], 2);
            }
            $lines[] = $line;
        }

        if ($telegram->send(implode("\n", $lines))) {
            Setting::put('telegram.conv_watermark', $maxTs->toIso8601String());
            $this->info(count($new).' conversie(s) gemeld.');
        }
    }

    protected function notifyAlerts(TelegramNotifier $telegram, CampaignMonitor $monitor): void
    {
        $silent = collect($monitor->silentCampaigns())->keyBy('campaign_id');
        $state = json_decode((string) Setting::get('telegram.alert_state', '{}'), true) ?: [];

        // Nieuw stil → waarschuwen.
        foreach ($silent as $cid => $a) {
            if (! isset($state[$cid])) {
                $last = $a['last'] ? $a['last']->diffForHumans() : 'onbekend';
                $telegram->send("⚠️ <b>Campagne stil</b>: ".e($a['campaign'])
                    ."\nGeen lpclick-conversie in &gt;".config('redtrack.lpclick_alert_hours', 4)
                    ."u (laatste: {$last}).");
                $state[$cid] = $a['campaign'];
            }
        }

        // Weer actief → herstelmelding.
        foreach ($state as $cid => $name) {
            if (! $silent->has($cid)) {
                $telegram->send("✅ <b>".e($name)."</b> weer actief — lpclick-conversies komen weer binnen.");
                unset($state[$cid]);
            }
        }

        Setting::put('telegram.alert_state', json_encode($state));
    }
}
