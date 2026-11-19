<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * تهيئة الأدوار والصلاحيات باستخدام حزمة Spatie
 */
class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // إعادة تعيين كاش الصلاحيات مع حماية في حال عدم تهيئة جدول cache
        try {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // تجاوز الخطأ في حال لم يكن جدول cache موجوداً بعد
        }

        // 1. تعريف الصلاحيات
        $permissions = [
            // صلاحيات السوبر أدمن
            'manage_shops',
            'manage_subscriptions',
            'view_platform_analytics',

            // صلاحيات مدير المحل
            'manage_items',
            'manage_contacts',
            'manage_transactions',
            'manage_cashbox',
            'view_reports',
            'update_exchange_rate',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // 2. إنشاء الدور الأول: السوبر أدمن (مالك المنصة)
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        // 3. إنشاء الدور الثاني: مدير المحل (Shop Manager)
        $shopManagerRole = Role::firstOrCreate(['name' => 'shop_manager', 'guard_name' => 'web']);
        $shopManagerRole->syncPermissions([
            'manage_items',
            'manage_contacts',
            'manage_transactions',
            'manage_cashbox',
            'view_reports',
            'update_exchange_rate',
        ]);
    }
}

