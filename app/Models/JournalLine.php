<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    use HasShop;

    protected $fillable = [
        'shop_id',
        'journal_entry_id',
        'account_id',
        'contact_id',
        'cost_center_id',
        'description',
        'debit',
        'credit',
        'currency',
        'exchange_rate',
        'foreign_debit',
        'foreign_credit',
        'amount_usd',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'foreign_debit' => 'decimal:2',
        'foreign_credit' => 'decimal:2',
        'amount_usd' => 'decimal:2',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
