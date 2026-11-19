<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait HasShop
{
    protected static function bootHasShop(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (!$model->shop_id && Auth::check() && current_shop_id()) {
                $model->shop_id = current_shop_id();
            }

            if (!$model->shop_id && Auth::check() && session()->has('impersonated_shop_id')) {
                $model->shop_id = session('impersonated_shop_id');
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }
}
