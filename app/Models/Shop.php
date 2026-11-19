<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    use HasFactory;

    // Primary key is string shop_id e.g. shop_amana
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'subscription_status', // active, expired, suspended
        'end_date',
        'exchange_rate',
        'owner_name',
        'phone',
        'plan_price_usd',
        'posting_enabled',
        'default_alert_limit',
        'company_notes',
        'admin_one_time_access',
    ];

    protected $casts = [
        'end_date' => 'date',
        'exchange_rate' => 'decimal:2',
        'plan_price_usd' => 'decimal:2',
        'posting_enabled' => 'boolean',
        'default_alert_limit' => 'integer',
        'admin_one_time_access' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'shop_id', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'shop_id', 'id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'shop_id', 'id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'shop_id', 'id');
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'shop_id', 'id');
    }

    public function isActive(): bool
    {
        return $this->subscription_status === 'active' && $this->end_date->isFuture();
    }
}
