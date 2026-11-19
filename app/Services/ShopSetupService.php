<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CostCenter;
use App\Models\Location;
use App\Models\Shop;

class ShopSetupService
{
    public const ROLES = [
        'cash_usd' => ['الصندوق بالدولار', true],
        'cash_syp' => ['الصندوق بالليرة السورية', true],
        'ar_usd' => ['ذمم العملاء بالدولار', true],
        'ar_syp' => ['ذمم العملاء بالليرة', true],
        'ap_usd' => ['ذمم الموردين بالدولار', true],
        'ap_syp' => ['ذمم الموردين بالليرة', true],
        'inventory' => ['المخزون', true],
        'sales' => ['المبيعات', true],
        'sales_returns' => ['مردودات المبيعات', false],
        'cogs' => ['تكلفة المبيعات', true],
        'purchases' => ['المشتريات', false],
        'expenses' => ['المصاريف العامة', true],
        'salaries' => ['الرواتب', true],
        'withdrawals' => ['مسحوبات الشركاء', true],
        'capital' => ['رأس المال', false],
    ];

    public function bootstrap(Shop $shop): void
    {
        $this->ensureLocations($shop->id);
        $this->ensureAccounts($shop->id);
        $this->ensureMappings($shop->id);
        $this->ensureCostCenters($shop->id);
    }

    public function ensureLocations(string $shopId): void
    {
        Location::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => $shopId, 'name' => 'المستودع الرئيسي'],
            ['is_default' => true]
        );
        Location::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => $shopId, 'name' => 'محل البيع المباشر'],
            ['is_default' => false]
        );
    }

    public function ensureCostCenters(string $shopId): void
    {
        CostCenter::withoutGlobalScopes()->firstOrCreate(
            ['shop_id' => $shopId, 'code' => 'CC-01'],
            ['name' => 'المحل', 'type' => 'تشغيلي']
        );
    }

    public function ensureAccounts(string $shopId): void
    {
        $defaults = [
            ['1000', 'الصندوق دولار', 'asset', 'نقدية'],
            ['1010', 'الصندوق ليرة سورية', 'asset', 'نقدية'],
            ['1100', 'العملاء دولار', 'asset', 'ذمم'],
            ['1110', 'العملاء ليرة سورية', 'asset', 'ذمم'],
            ['1200', 'المخزون', 'asset', 'مخزون'],
            ['2000', 'الموردون دولار', 'liability', 'ذمم'],
            ['2010', 'الموردون ليرة سورية', 'liability', 'ذمم'],
            ['3000', 'رأس المال', 'equity', 'حقوق ملكية'],
            ['3100', 'مسحوبات الشركاء', 'equity', 'مسحوبات'],
            ['4000', 'المبيعات', 'revenue', 'إيراد'],
            ['4100', 'مردودات المبيعات', 'revenue', 'مردودات'],
            ['5000', 'تكلفة المبيعات', 'expense', 'تكلفة'],
            ['5100', 'المشتريات', 'expense', 'تكلفة'],
            ['5200', 'المصاريف العامة', 'expense', 'تشغيل'],
            ['5300', 'الرواتب', 'expense', 'تشغيل'],
        ];

        foreach ($defaults as [$code, $name, $type, $classification]) {
            Account::withoutGlobalScopes()->firstOrCreate(
                ['shop_id' => $shopId, 'code' => $code],
                ['name' => $name, 'type' => $type, 'classification' => $classification, 'is_active' => true]
            );
        }
    }

    public function ensureMappings(string $shopId): void
    {
        $codeByRole = [
            'cash_usd' => '1000',
            'cash_syp' => '1010',
            'ar_usd' => '1100',
            'ar_syp' => '1110',
            'ap_usd' => '2000',
            'ap_syp' => '2010',
            'inventory' => '1200',
            'sales' => '4000',
            'sales_returns' => '4100',
            'cogs' => '5000',
            'purchases' => '5100',
            'expenses' => '5200',
            'salaries' => '5300',
            'withdrawals' => '3100',
            'capital' => '3000',
        ];

        foreach ($codeByRole as $role => $code) {
            $account = Account::withoutGlobalScopes()->where('shop_id', $shopId)->where('code', $code)->first();
            AccountMapping::withoutGlobalScopes()->firstOrCreate(
                ['shop_id' => $shopId, 'role' => $role],
                ['account_id' => $account?->id]
            );
        }
    }

    public function mappingStatus(string $shopId): array
    {
        $this->ensureMappings($shopId);
        $rows = [];
        $missingRequired = 0;
        foreach (self::ROLES as $role => [$label, $required]) {
            $map = AccountMapping::withoutGlobalScopes()->with('account')->where('shop_id', $shopId)->where('role', $role)->first();
            $linked = (bool) ($map?->account_id);
            if ($required && !$linked) {
                $missingRequired++;
            }
            $rows[] = [
                'role' => $role,
                'label' => $label,
                'required' => $required,
                'account' => $map?->account,
                'ok' => $linked,
            ];
        }

        return [
            'rows' => $rows,
            'missing_required' => $missingRequired,
            'ready' => $missingRequired === 0,
        ];
    }
}
