<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'item_name',
        'unit',
        'alert_limit',
        'opening_qty',
        'opening_cost_usd',
        'code',
        'category',
    ];

    protected $casts = [
        'alert_limit' => 'integer',
        'opening_qty' => 'decimal:2',
        'opening_cost_usd' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // 5. تطبيق الـ Global Scope لعزل البيانات تلقائياً
        static::addGlobalScope(new TenantScope);

        // تعيين shop_id تلقائياً عند الإنشاء
        static::creating(function ($item) {
            if (!$item->shop_id && Auth::check() && current_shop_id()) {
                $item->shop_id = current_shop_id();
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
