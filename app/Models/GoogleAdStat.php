<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleAdStat extends Model
{
    protected $guarded = [];

    protected $casts = [
        'stat_date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'cost' => 'decimal:2',
        'conversions' => 'decimal:2',
        'conversions_value' => 'decimal:2',
        'synced_at' => 'datetime',
    ];
}
