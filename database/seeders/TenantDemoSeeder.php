<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Shop;
use App\Models\User;
use App\Models\Item;
use App\Models\Contact;
use App\Models\Transaction;
use App\Models\CashMovement;

class TenantDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. إنشاء السوبر أدمن
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@zahed-saas.com'],
            [
                'name' => 'أحمد الزاهد (مالك المنصة)',
                'phone' => '0900000000',
                'password' => Hash::make('password'),
                'shop_id' => null,
            ]
        );
        if (! $superAdmin->phone) {
            $superAdmin->phone = '0900000000';
            $superAdmin->save();
        }
        $superAdmin->syncRoles(['super_admin']);

        // 2. إنشاء المحل الأول: محلات الأمانة
        $shopAmana = Shop::firstOrCreate(
            ['id' => 'shop_amana'],
            [
                'name' => 'محلات الأمانة للأجهزة والتجهيزات',
                'subscription_status' => 'active',
                'end_date' => now()->addYear(),
                'exchange_rate' => 14500.00,
                'owner_name' => 'خالد عبد الرحمن',
                'phone' => '0944123456',
                'plan_price_usd' => 35.00,
            ]
        );

        $managerAmana = User::firstOrCreate(
            ['email' => 'khaled@amana-shop.com'],
            [
                'name' => 'خالد عبد الرحمن (مدير الأمانة)',
                'phone' => '0944123456',
                'password' => Hash::make('password'),
                'shop_id' => 'shop_amana',
            ]
        );
        $managerAmana->syncRoles(['shop_manager']);

        // أصناف محلات الأمانة
        $itemTv = Item::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => 'shop_amana', 'item_name' => 'شاشة سمارت سامسونج 55 بوصة 4K'],
            ['unit' => 'قطعة', 'alert_limit' => 3, 'opening_qty' => 8, 'opening_cost_usd' => 310]
        );

        $itemWash = Item::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => 'shop_amana', 'item_name' => 'غسالة ال جي 8 كغ فول أوتوماتيك'],
            ['unit' => 'قطعة', 'alert_limit' => 2, 'opening_qty' => 5, 'opening_cost_usd' => 420]
        );

        $itemFan = Item::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => 'shop_amana', 'item_name' => 'مروحة عمودية توشيبا 4 سرعات'],
            ['unit' => 'قطعة', 'alert_limit' => 5, 'opening_qty' => 24, 'opening_cost_usd' => 38]
        );

        // جهات اتصال محلات الأمانة
        $clientSamer = Contact::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => 'shop_amana', 'name' => 'المهندس سامر عبد الله'],
            ['type' => 'customer', 'phone' => '0944888777', 'notes' => 'عميل مميز']
        );

        $clientBinaa = Contact::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => 'shop_amana', 'name' => 'شركة البناء الحديث للمقاولات'],
            ['type' => 'customer', 'phone' => '0112334455', 'notes' => 'حساب جاري']
        );

        $supplierSharq = Contact::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => 'shop_amana', 'name' => 'شركة الشرق الأوسط للإلكترونيات'],
            ['type' => 'supplier', 'phone' => '0116655443', 'notes' => 'مورد شاشات']
        );

        // فواتير تجريبية للأمانة
        Transaction::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => 'shop_amana', 'invoice_number' => 'PUR-2026-001'],
            [
                'date' => now()->subDays(5),
                'type' => 'purchase',
                'payment_type' => 'credit',
                'contact_id' => $supplierSharq->id,
                'item_id' => $itemTv->id,
                'qty' => 4,
                'unit_price' => 320,
                'total' => 1280,
                'currency' => 'USD',
                'exchange_rate' => 14500,
                'total_usd' => 1280,
                'notes' => 'توريد شاشات بالآجل',
            ]
        );

        Transaction::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => 'shop_amana', 'invoice_number' => 'INV-2026-101'],
            [
                'date' => now()->subDays(3),
                'type' => 'sale',
                'payment_type' => 'cash',
                'contact_id' => $clientSamer->id,
                'item_id' => $itemTv->id,
                'qty' => 2,
                'unit_price' => 380,
                'total' => 760,
                'currency' => 'USD',
                'exchange_rate' => 14500,
                'total_usd' => 760,
                'notes' => 'بيع نقدي',
            ]
        );

        // حركات صندوق للأمانة
        CashMovement::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => 'shop_amana', 'notes' => 'رصيد افتتاحي بالدولار'],
            [
                'date' => now()->startOfMonth(),
                'type' => 'opening',
                'amount' => 1500,
                'currency' => 'USD',
                'exchange_rate' => 14500,
                'amount_usd' => 1500,
            ]
        );

        CashMovement::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => 'shop_amana', 'notes' => 'رصيد افتتاحي بالليرة'],
            [
                'date' => now()->startOfMonth(),
                'type' => 'opening',
                'amount' => 14500000,
                'currency' => 'SYP',
                'exchange_rate' => 14500,
                'amount_usd' => 1000,
            ]
        );

        // 3. إنشاء المحل الثاني: شركة النور (بيانات معزولة)
        $shopNour = Shop::firstOrCreate(
            ['id' => 'shop_nour'],
            [
                'name' => 'شركة النور للمواد الغذائية والتموينية',
                'subscription_status' => 'active',
                'end_date' => now()->addMonths(8),
                'exchange_rate' => 14600.00,
                'owner_name' => 'طارق سليم',
                'phone' => '0933987654',
                'plan_price_usd' => 45.00,
            ]
        );

        $managerNour = User::firstOrCreate(
            ['email' => 'tareq@nour-trading.com'],
            [
                'name' => 'طارق سليم (مدير النور)',
                'phone' => '0933987654',
                'password' => Hash::make('password'),
                'shop_id' => 'shop_nour',
            ]
        );
        $managerNour->syncRoles(['shop_manager']);

        Item::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => 'shop_nour', 'item_name' => 'أرز بسمتي هندي درجة أولى (10 كغ)'],
            ['unit' => 'كيس', 'alert_limit' => 15, 'opening_qty' => 60, 'opening_cost_usd' => 14]
        );

        $setup = app(\App\Services\ShopSetupService::class);
        $setup->bootstrap($shopAmana);
        $setup->bootstrap($shopNour);
    }
}
