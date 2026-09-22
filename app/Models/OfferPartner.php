<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferPartner extends Model
{
    protected $guarded = [];

    protected $casts = [
        'has_revshare' => 'boolean',
        'payout' => 'decimal:2',
        'revshare_pct' => 'decimal:2',
    ];

    /** Compacte omschrijving van de deal, bv. "CPQL $ 25,00 + 20% revshare". */
    public function dealSummary(): string
    {
        if (! $this->deal_model) {
            return '—';
        }

        $parts = [strtoupper($this->deal_model)];

        if ($this->payout !== null && (float) $this->payout > 0) {
            $symbol = $this->payout_currency === 'EUR' ? '€' : '$';
            $parts[] = $symbol.' '.number_format((float) $this->payout, 2, ',', '.');
        }

        $deal = implode(' ', $parts);

        if ($this->has_revshare && $this->revshare_pct !== null) {
            $deal .= ' + '.rtrim(rtrim(number_format((float) $this->revshare_pct, 2, ',', '.'), '0'), ',').'% revshare';
        } elseif ($this->has_revshare) {
            $deal .= ' + revshare';
        }

        return $deal;
    }
}
