<?php

namespace App\Models;

use App\Models\Concerns\HasShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasShop;

    protected $fillable = ['shop_id', 'name', 'phone', 'notes'];

    public function salaries(): HasMany
    {
        return $this->hasMany(SalaryRecord::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(EmployeeMovement::class);
    }
}
