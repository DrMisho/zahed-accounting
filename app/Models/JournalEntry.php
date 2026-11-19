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
        return (float) $this->lines()->sum('debit');
    }

    public function creditTotal(): float
    {
        return (float) $this->lines()->sum('credit');
    }

    public function isBalanced(): bool
    {
        return abs($this->debitTotal() - $this->creditTotal()) < 0.01;
    }
}
