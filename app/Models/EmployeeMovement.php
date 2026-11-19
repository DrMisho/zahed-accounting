<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeMovement extends Model
{
    use HasShop;

    protected $fillable = [
        'shop_id', 'employee_id', 'date', 'voucher_number', 'type', 'month', 'amount',
        'currency', 'exchange_rate', 'amount_usd', 'payment_method', 'notes', 'cash_movement_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'amount_usd' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (EmployeeMovement $row) {
            $row->amount_usd = Money::toUsd((float) $row->amount, $row->currency, (float) $row->exchange_rate);
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'advance' => 'سلفة عادية (ذمة مستقلة)',
            'salary_payment' => 'دفعة راتب',
            'settlement' => 'تسوية',
            default => $this->type,
        };
    }
}
