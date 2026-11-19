<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerWithdrawal extends Model
{
    use HasShop;

    protected $fillable = [
        'shop_id', 'partner_id', 'date', 'voucher_number', 'description', 'amount', 'currency',
        'exchange_rate', 'amount_usd', 'method', 'nature', 'classification_status', 'notes', 'cash_movement_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'amount_usd' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (PartnerWithdrawal $row) {
            $row->amount_usd = Money::toUsd((float) $row->amount, $row->currency, (float) $row->exchange_rate);
        });
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function isFinal(): bool
    {
        return $this->classification_status === 'مصنف' && $this->nature === 'مسحوب شخصي نهائي';
    }
}
