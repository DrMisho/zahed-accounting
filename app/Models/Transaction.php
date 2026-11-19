<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'invoice_number',
        'date',
        'type',
        'payment_type',
        'operation_subtype',
        'contact_id',
        'item_id',
        'qty',
        'unit_price',
        'total',
        'paid_amount',
        'paid_amount_usd',
        'currency',
        'exchange_rate',
        'total_usd',
        'location_id',
        'cost_center_id',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'total_usd' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'paid_amount_usd' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($tx) {
            if (!$tx->shop_id && Auth::check() && current_shop_id()) {
                $tx->shop_id = current_shop_id();
            }
        });

        static::saving(function ($tx) {
            if ($tx->exists && $tx->lines()->exists()) {
                return;
            }
            $tx->total = (float) $tx->qty * (float) $tx->unit_price;
            if ($tx->currency === 'USD') {
                $tx->total_usd = $tx->total;
            } else {
                $rate = $tx->exchange_rate > 0 ? $tx->exchange_rate : 14500;
                $tx->total_usd = round($tx->total / $rate, 2);
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function lines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransactionLine::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
