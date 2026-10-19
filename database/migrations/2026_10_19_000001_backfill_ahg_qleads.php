<?php

use App\Models\StatCorrection;
use Illuminate\Database\Migrations\Migration;

/**
 * Historische qleads (Qualified) van American Hartford Gold toevoegen uit de
 * 301 Ads-export (2026-10-19). Deze qualification-milestones kwamen vroeger
 * niet via de postback in RedTrack binnen; nieuwe qleads komen dat wel.
 * Alleen de telling (d_qleads), geen omzet — de $250 zit al op de leads.
 */
return new class extends Migration
{
    private const OFFER_ID = '6a99bb216af817663bd3b11e'; // American Hartford Gold

    private const NOTE = 'AHG qlead backfill (301 Ads export 2026-10-19)';

    private const DATES = [
        '2026-09-05', '2026-09-09', '2026-09-28', '2026-09-29',
        '2026-09-30', '2026-10-08', '2026-10-13', '2026-10-17',
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
