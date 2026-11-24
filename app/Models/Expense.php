<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasShop;

    protected $fillable = [
        'shop_id', 'date', 'voucher_number', 'expense_type', 'account_id', 'description', 'amount', 'currency',
        'exchange_rate', 'amount_usd', 'payment_method', 'cost_center_id', 'notes', 'cash_movement_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'amount_usd' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Expense $row) {
            $row->amount_usd = Money::toUsd((float) $row->amount, $row->currency, (float) $row->exchange_rate);
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
