<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Twee bekende correcties op een postback-fout (bevestigd door de gebruiker,
     * 2026-08-20). Idempotent via de note-sleutel.
     */
    public function up(): void
    {
        $now = now();

        $corrections = [
            [
                // Augusta 03-08: 3 conversies stonden als sale door een postback-fout,
                // moeten leads zijn. Netto conversies blijven gelijk.
                'note' => 'postback-fix: augusta 2026-08-03 3 sales -> leads (clickids 6a6e9cd1,6a6e702d,6a5c26b5)',
                'stat_date' => '2026-08-03',
                'offer_id' => '69f86945866fcfef76cba9bf',
                'source' => 'google',
                'd_leads' => 3,
                'd_sales' => -3,
                'revenue' => 0,
                'revenue_currency' => 'USD',
            ],
            [
                // Goldencrest 23-07: echte revshare-sale van EUR 4600 die nooit in
                // RedTrack terechtkwam. Lead + qlead (EUR 200) blijven staan.
                'note' => 'missing-sale: goldencrest 2026-07-23 revshare EUR4600 (clickid 6a622b48)',
                'stat_date' => '2026-07-23',
                'offer_id' => '69f86961e31cc0c64c23bd84',
                'source' => 'google',
                'd_sales' => 1,
                'revenue' => 4600,
                'revenue_currency' => 'EUR',
            ],
        ];

        foreach ($corrections as $c) {
            DB::table('stat_corrections')->updateOrInsert(
                ['note' => $c['note']],
                array_merge($c, ['created_at' => $now, 'updated_at' => $now]),
            );
        }
    }

    public function down(): void
    {
        DB::table('stat_corrections')->whereIn('note', [
            'postback-fix: augusta 2026-08-03 3 sales -> leads (clickids 6a6e9cd1,6a6e702d,6a5c26b5)',
            'missing-sale: goldencrest 2026-07-23 revshare EUR4600 (clickid 6a622b48)',
        ])->delete();
    }
};
