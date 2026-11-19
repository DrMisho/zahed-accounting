<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryRecord extends Model
{
    use HasShop;

    protected $fillable = [
        'shop_id', 'employee_id', 'date', 'voucher_number', 'month', 'basic', 'extra', 'deduction',
        'net', 'currency', 'exchange_rate', 'amount_usd', 'payment_method', 'notes', 'cash_movement_id',
    ];

    protected $casts = [
        'date' => 'date',
        'basic' => 'decimal:2',
        'extra' => 'decimal:2',
        'deduction' => 'decimal:2',
        'net' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'amount_usd' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (SalaryRecord $row) {
            $row->net = (float) $row->basic + (float) $row->extra - (float) $row->deduction;
            $row->amount_usd = Money::toUsd((float) $row->net, $row->currency, (float) $row->exchange_rate);
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
