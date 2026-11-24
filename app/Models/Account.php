<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasShop;

    protected $fillable = [
        'shop_id',
        'parent_id',
        'level',
        'code',
        'name',
        'type',
        'classification',
        'is_postable',
        'normal_balance',
        'statement_type',
        'currency',
        'is_system',
        'system_key',
        'opening_debit',
        'opening_credit',
        'is_active',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_postable' => 'boolean',
        'is_system' => 'boolean',
        'opening_debit' => 'decimal:2',
        'opening_credit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id')->orderBy('code');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function isDebitNormal(): bool
    {
        if ($this->normal_balance) {
            return $this->normal_balance === 'debit';
        }

        return in_array($this->type, ['asset', 'expense'], true);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'asset' => 'أصول',
            'liability' => 'التزامات',
            'equity' => 'حقوق ملكية',
            'revenue' => 'إيرادات',
            'expense' => 'تكاليف ومصاريف',
            default => $this->type,
        };
    }

    public function statementLabel(): string
    {
        return match ($this->statement_type) {
            'trading' => 'حساب المتاجرة',
            'pnl' => 'الأرباح والخسائر',
            'balance_sheet' => 'الميزانية العمومية',
            default => 'الميزانية العمومية',
        };
    }

    public function normalBalanceLabel(): string
    {
        return $this->isDebitNormal() ? 'مدين' : 'دائن';
    }
}
