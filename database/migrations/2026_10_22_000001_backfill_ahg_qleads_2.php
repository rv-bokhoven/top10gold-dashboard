<?php

use App\Models\StatCorrection;
use Illuminate\Database\Migrations\Migration;

/**
 * Vervolg op de AHG qlead-backfill: 2 nieuwe Qualified-conversies uit de
 * 301 Ads-export van 2026-10-22 (de postback werkt nog niet). Alleen de
 * telling (d_qleads), geen omzet — de $250 zit al op de leads.
 */
return new class extends Migration
{
    private const OFFER_ID = '6a99bb216af817663bd3b11e'; // American Hartford Gold

    private const NOTE = 'AHG qlead backfill (301 Ads export 2026-10-22)';

    private const DATES = [
        '2026-10-19', '2026-10-20',
    ];

    public function up(): void
    {
        foreach (self::DATES as $date) {
            StatCorrection::updateOrCreate(
                [
                    'stat_date' => $date,
                    'offer_id' => self::OFFER_ID,
                    'source' => 'google',
                    'note' => self::NOTE,
                ],
                ['d_qleads' => 1],
            );
        }
    }

    public function down(): void
    {
        StatCorrection::where('offer_id', self::OFFER_ID)
            ->where('note', self::NOTE)
            ->delete();
    }
};
