<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Handmatige correctie op de gesynchte RedTrack-aggregaten. Wordt bij het
 * weergeven bovenop offer_stats opgeteld, zodat een RedTrack re-sync de
 * correctie niet wist. Zie HasDashboardFilters::correctionDeltas().
 */
class StatCorrection extends Model
{
    protected $guarded = [];

    protected $casts = [
        'stat_date' => 'date',
        'd_lp_views' => 'integer',
        'd_lp_clicks' => 'integer',
        'd_clicks' => 'integer',
        'd_leads' => 'integer',
        'd_qleads' => 'integer',
        'd_sales' => 'integer',
        'revenue' => 'decimal:4',
    ];

    /** Afgeleide conversies-delta (lead + qlead + sale). */
    public function getDConversionsAttribute(): int
    {
        return $this->d_leads + $this->d_qleads + $this->d_sales;
    }
}
