<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class CashMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'date',
        'type', // receipt, payment, expense, salary, withdrawal, opening
        'contact_id',
        'category',
        'amount',
        'currency', // USD, SYP
        'exchange_rate',
        'amount_usd',
        'notes',
        'voucher_number',
        'source_type',
        'source_id',
        'affects_balance',
        'document_ref',
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
        static::addGlobalScope(new TenantScope);

        static::creating(function ($cm) {
            if (!$cm->shop_id && Auth::check() && current_shop_id()) {
                $cm->shop_id = current_shop_id();
            }
        });

        // 4.أ. احتساب المعادل بالدولار لحركة الصندوق
        static::saving(function ($cm) {
            $rate = (float) $cm->exchange_rate > 0 ? (float) $cm->exchange_rate : 14500.0;
            $cm->amount_usd = Money::toUsd((float) $cm->amount, (string) ($cm->currency ?: 'USD'), $rate);
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
