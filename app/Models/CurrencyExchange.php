<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use Illuminate\Database\Eloquent\Model;

class CurrencyExchange extends Model
{
    use HasShop;

    protected $fillable = [
        'shop_id',
        'date',
        'reference_number',
        'direction',
        'from_currency',
        'to_currency',
        'from_amount',
        'exchange_rate',
        'to_amount',
        'amount_usd',
        'out_cash_movement_id',
        'in_cash_movement_id',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'from_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'to_amount' => 'decimal:2',
        'amount_usd' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (CurrencyExchange $row) {
            $rate = (float) $row->exchange_rate;
            $from = (float) $row->from_amount;

            if ($row->direction === 'syp_to_usd') {
                $row->from_currency = 'SYP';
                $row->to_currency = 'USD';
                $row->to_amount = $rate > 0 ? round($from / $rate, 2) : 0;
                $row->amount_usd = $row->to_amount;
            } else {
                $row->direction = 'usd_to_syp';
                $row->from_currency = 'USD';
                $row->to_currency = 'SYP';
                $row->to_amount = round($from * $rate, 2);
                $row->amount_usd = round($from, 2);
            }
        });
    }

    public function directionLabel(): string
    {
        return $this->direction === 'syp_to_usd'
            ? 'تحويل من ليرة سورية (SYP) إلى دولار (USD)'
            : 'تحويل من دولار (USD) إلى ليرة سورية (SYP)';
    }
}
