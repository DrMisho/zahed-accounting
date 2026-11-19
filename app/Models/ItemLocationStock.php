<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemLocationStock extends Model
{
    use HasShop;

    protected $fillable = ['shop_id', 'location_id', 'item_id', 'opening_qty', 'opening_cost_usd'];

    protected $casts = [
        'opening_qty' => 'decimal:2',
        'opening_cost_usd' => 'decimal:2',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
