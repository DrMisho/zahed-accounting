<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    use HasShop;

    protected $fillable = [
        'shop_id', 'type', 'number', 'date', 'contact_id', 'beneficiary', 'description', 'amount',
        'currency', 'exchange_rate', 'amount_usd', 'payment_method', 'original_ref', 'affects_balance',
        'notes', 'cash_movement_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'amount_usd' => 'decimal:2',
        'affects_balance' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Voucher $row) {
            $row->amount_usd = Money::toUsd((float) $row->amount, $row->currency, (float) $row->exchange_rate);
        });
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
