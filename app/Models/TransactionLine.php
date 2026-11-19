<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionLine extends Model
{
    use HasShop;

    protected $fillable = [
        'shop_id', 'transaction_id', 'item_id', 'location_id', 'qty', 'unit_price', 'total', 'total_usd',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'total_usd' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (TransactionLine $line) {
            $line->total = (float) $line->qty * (float) $line->unit_price;
            $tx = $line->relationLoaded('transaction') ? $line->transaction : Transaction::withoutGlobalScopes()->find($line->transaction_id);
            if ($tx) {
                $line->total_usd = Money::toUsd((float) $line->total, $tx->currency, (float) $tx->exchange_rate);
                if (!$line->shop_id) {
                    $line->shop_id = $tx->shop_id;
                }
            }
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
