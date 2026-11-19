<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasShop;

    protected $fillable = ['shop_id', 'name', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function stocks(): HasMany
    {
        return $this->hasMany(ItemLocationStock::class);
    }
}
