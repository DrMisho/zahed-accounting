<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'name',
        'type', // customer, supplier
        'phone',
        'notes',
        'code',
        'opening_usd',
        'opening_syp',
    ];

    protected $casts = [
        'opening_usd' => 'decimal:2',
        'opening_syp' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // 5. تطبيق الـ Global Scope
        static::addGlobalScope(new TenantScope);

        static::creating(function ($contact) {
            if (!$contact->shop_id && Auth::check() && current_shop_id()) {
                $contact->shop_id = current_shop_id();
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

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }
}
