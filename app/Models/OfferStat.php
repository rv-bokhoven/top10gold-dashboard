<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferStat extends Model
{
    /**
     * Sentinel voor de campagne-/landing-rij (RedTrack geeft daar een lege
     * offer terug). We gebruiken een vaste waarde i.p.v. NULL zodat de
     * unique-index op (stat_date, offer_id) en upserts betrouwbaar werken.
     */
    public const CAMPAIGN = '__campaign__';

    protected $guarded = [];

    protected $casts = [
        'stat_date' => 'date',
        'lp_views' => 'integer',
        'lp_clicks' => 'integer',
        'clicks' => 'integer',
        'leads' => 'integer',
        'qleads' => 'integer',
        'sales' => 'integer',
        'conversions' => 'integer',
        'cost' => 'decimal:4',
        'revenue' => 'decimal:4',
        'synced_at' => 'datetime',
    ];

    /** Rijen die bij een echte offer horen (dus niet de campagne-/landing-rij). */
    public function scopeOffers($query)
    {
        return $query->where('offer_id', '!=', self::CAMPAIGN);
    }

    public function getIsCampaignAttribute(): bool
    {
        return $this->offer_id === self::CAMPAIGN;
    }
}
