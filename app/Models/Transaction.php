<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'subtotal',
        'subtotal_usd',
        'discount',
        'discount_usd',
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
        'subtotal' => 'decimal:2',
        'subtotal_usd' => 'decimal:2',
        'discount' => 'decimal:2',
        'discount_usd' => 'decimal:2',
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
            $gross = round((float) $tx->qty * (float) $tx->unit_price, 2);
            $discount = round(max(0, min((float) ($tx->discount ?? 0), $gross)), 2);
            $rate = (float) ($tx->exchange_rate > 0 ? $tx->exchange_rate : 14500);

            $tx->subtotal = $gross;
            $tx->discount = $discount;
            $tx->total = round(max(0, $gross - $discount), 2);
            $tx->subtotal_usd = Money::toUsd($tx->subtotal, $tx->currency, $rate);
            $tx->discount_usd = Money::toUsd($tx->discount, $tx->currency, $rate);
            $tx->total_usd = Money::toUsd($tx->total, $tx->currency, $rate);
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

    public function lines(): HasMany
    {
        return $this->hasMany(TransactionLine::class);
    }

    public function shippingExpenses(): HasMany
    {
        return $this->hasMany(ShippingExpense::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function discountLabel(): string
    {
        return in_array($this->type, ['purchase', 'purchase_return'], true)
            ? 'حسم مكتسب'
            : 'حسم ممنوح';
    }
}
