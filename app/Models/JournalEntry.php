<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasShop;

    protected $fillable = ['shop_id', 'date', 'document_number', 'source_type', 'source_id', 'description'];

    protected $casts = ['date' => 'date'];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function debitTotal(): float
    {
        return round((float) $this->lines()->sum('debit'), 2);
    }

    public function creditTotal(): float
    {
        return round((float) $this->lines()->sum('credit'), 2);
    }

    public function isBalanced(): bool
    {
        return abs($this->debitTotal() - $this->creditTotal()) < 0.01;
    }

    public function sourceLabel(): string
    {
        return match ($this->source_type) {
            'transaction' => 'فاتورة',
            'voucher' => 'سند قبض / صرف',
            'cash_movement' => 'حركة صندوق مباشرة',
            'expense' => 'مصروف عام',
            'shipping_expense' => 'أجور شحن',
            'salary' => 'راتب موظف',
            'employee_movement' => 'حركة موظف',
            'withdrawal' => 'مسحوب شريك',
            'currency_exchange' => 'تحويل عملة',
            'fx_revaluation' => 'إعادة تقييم فروق صرف',
            'opening_sync' => 'أرصدة افتتاحية',
            'manual' => 'قيد يدوي',
            default => 'قيد محاسبي',
        };
    }
}
