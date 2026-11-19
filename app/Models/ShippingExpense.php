<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingExpense extends Model
{
    use HasShop;

    protected $fillable = [
        'shop_id',
        'transaction_id',
        'date',
        'voucher_number',
        'carrier_name',
        'shipping_type',
        'description',
        'amount',
        'currency',
        'exchange_rate',
        'amount_usd',
        'payment_method',
        'notes',
        'cash_movement_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'amount_usd' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (ShippingExpense $row) {
            $row->amount_usd = Money::toUsd((float) $row->amount, $row->currency, (float) $row->exchange_rate);
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
