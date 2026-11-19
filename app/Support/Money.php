<?php

namespace App\Support;

class Money
{
    public static function toUsd(float $amount, string $currency, float $rate): float
    {
        if (strtoupper($currency) === 'USD') {
            return round($amount, 2);
        }

        if ($rate <= 0) {
            return 0.0;
        }

        return round($amount / $rate, 2);
    }
}
