<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasShop;

    protected $fillable = [
        'shop_id', 'code', 'name', 'type', 'classification', 'opening_debit', 'opening_credit', 'is_active',
    ];

    protected $casts = [
        'opening_debit' => 'decimal:2',
        'opening_credit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'asset' => 'أصل',
            'liability' => 'التزام',
            'equity' => 'حقوق ملكية',
            'revenue' => 'إيراد',
            'expense' => 'مصروف',
            default => $this->type,
        };
    }
}
