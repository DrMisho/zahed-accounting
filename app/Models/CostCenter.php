<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use Illuminate\Database\Eloquent\Model;

class CostCenter extends Model
{
    use HasShop;

    protected $fillable = ['shop_id', 'code', 'name', 'type', 'cost_amount', 'notes'];

    protected $casts = ['cost_amount' => 'decimal:2'];
}
