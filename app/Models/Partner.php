<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    use HasShop;

    protected $fillable = ['shop_id', 'name', 'notes'];

    public function withdrawals(): HasMany
    {
        return $this->hasMany(PartnerWithdrawal::class);
    }
}
