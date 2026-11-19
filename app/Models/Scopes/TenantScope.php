<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * 5. قواعد الأمان وعزل البيانات (Security & Data Isolation - Global Scope)
 * تطبيق الـ Global Scope على جميع الـ Models (Item, Transaction, Contact, CashMovement)
 * لضمان إضافة شرط WHERE shop_id = current_shop_id() تلقائياً لأي استعلام لمنع تسرب البيانات.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // مدير المحل مقيد فقط ببيانات المحل الخاص به
            if ($user->hasRole('shop_manager') && $user->shop_id) {
                $builder->where($model->getTable() . '.shop_id', $user->shop_id);
            }
            
            // السوبر أدمن إذا اختار فحص محل معين عبر الجلسة
            if ($user->hasRole('super_admin') && session()->has('impersonated_shop_id')) {
                $builder->where($model->getTable() . '.shop_id', session('impersonated_shop_id'));
            }
        }
    }
}
