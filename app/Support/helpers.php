<?php

use App\Models\Shop;

if (! function_exists('normalize_phone')) {
    function normalize_phone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?: '';
    }
}

if (! function_exists('current_shop_id')) {
    function current_shop_id(): ?string
    {
        return session('impersonated_shop_id') ?: auth()->user()?->shop_id;
    }
}

if (! function_exists('current_shop')) {
    function current_shop(): ?Shop
    {
        $id = current_shop_id();

        return $id ? Shop::find($id) : null;
    }
}
