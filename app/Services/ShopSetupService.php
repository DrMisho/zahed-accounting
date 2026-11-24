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
        'cash_usd' => ['الصندوق الرئيسي (دولار USD)', true],
        'cash_syp' => ['الصندوق الثانوي (ليرة سورية SYP)', true],
        'bank_usd' => ['المصارف والحسابات البنكية', false],
        'ar_usd' => ['ذمم العملاء (دولار USD)', true],
        'ar_syp' => ['ذمم العملاء (ليرة سورية SYP)', true],
        'employee_advances' => ['سلف وعهد الموظفين', false],
        'inventory' => ['مخزون البضاعة', true],
        'ap_usd' => ['ذمم الموردين (دولار USD)', true],
        'ap_syp' => ['ذمم الموردين (ليرة سورية SYP)', true],
        'capital' => ['رأس المال المدفوع', true],
        'withdrawals' => ['مسحوبات الشركاء الشخصية', true],
        'retained_earnings' => ['أرباح / خسائر مدوّرة', false],
        'sales' => ['المبيعات', true],
        'sales_returns' => ['مردودات المبيعات', true],
        'sales_discount' => ['الحسم الممنوح للمبيعات', false],
        'other_revenue' => ['إيرادات متنوعة وخدمات', false],
        'fx_gain' => ['أرباح فروق صرف العملة', true],
        'cogs' => ['تكلفة البضاعة المباعة (COGS)', true],
        'purchases' => ['المشتريات المباشرة', false],
        'shipping_in' => ['أجور شحن ومصاريف نقل المشتريات', false],
        'purchase_discount' => ['الحسم المكتسب من المشتريات', false],
        'salaries' => ['الرواتب والأجور', true],
        'expenses' => ['المصاريف العمومية والإدارية', true],
        'fx_loss' => ['خسائر فروق صرف العملة', true],
        'fx_diff' => ['فروق صرف العملة (مرجعي)', false],
    ];

    /**
     * Complete standard 4-level hierarchical Arabic Chart of Accounts.
     * Format: [code, parent_code, level, name, type, classification, is_postable, normal_balance, statement_type, currency, system_key]
     */
    public const STANDARD_TREE = [
        // 1 — الأصول
        ['1', null, 1, 'الأصول', 'asset', 'رئيسي', false, 'debit', 'balance_sheet', 'USD', 'root_assets'],
        ['11', '1', 2, 'الأصول المتداولة', 'asset', 'أصول متداولة', false, 'debit', 'balance_sheet', 'USD', 'grp_current_assets'],
        ['111', '11', 3, 'النقدية وما يعادلها', 'asset', 'نقدية', false, 'debit', 'balance_sheet', 'USD', 'grp_cash'],
        ['1111', '111', 4, 'الصندوق الرئيسي (دولار أمريكي USD)', 'asset', 'نقدية', true, 'debit', 'balance_sheet', 'USD', 'cash_usd'],
        ['1112', '111', 4, 'الصندوق الثانوي (ليرة سورية SYP)', 'asset', 'نقدية', true, 'debit', 'balance_sheet', 'SYP', 'cash_syp'],
        ['1113', '111', 4, 'المصارف والحسابات البنكية (دولار)', 'asset', 'بنوك', true, 'debit', 'balance_sheet', 'USD', 'bank_usd'],
        ['1114', '111', 4, 'الحوالات وشركات الصرافة قيد التحصيل', 'asset', 'حوالات', true, 'debit', 'balance_sheet', 'USD', 'transfers_clearing'],

        ['112', '11', 3, 'أوراق القبض والشيكات', 'asset', 'أوراق قبض', false, 'debit', 'balance_sheet', 'USD', 'grp_notes_rec'],
        ['1121', '112', 4, 'أوراق قبض وشيكات برسم التحصيل', 'asset', 'أوراق قبض', true, 'debit', 'balance_sheet', 'USD', 'notes_receivable'],

        ['113', '11', 3, 'الذمم المدينة (العملاء والمدينون)', 'asset', 'ذمم مدينة', false, 'debit', 'balance_sheet', 'USD', 'grp_ar'],
        ['1131', '113', 4, 'ذمم العملاء (دولار أمريكي USD)', 'asset', 'ذمم عملاء', true, 'debit', 'balance_sheet', 'USD', 'ar_usd'],
        ['1132', '113', 4, 'ذمم العملاء (ليرة سورية SYP)', 'asset', 'ذمم عملاء', true, 'debit', 'balance_sheet', 'SYP', 'ar_syp'],
        ['1133', '113', 4, 'سلف وعهد الموظفين', 'asset', 'سلف موظفين', true, 'debit', 'balance_sheet', 'USD', 'employee_advances'],
        ['1134', '113', 4, 'مدينون متنوعون ومصاريف مدفوعة مقدماً', 'asset', 'مدينون', true, 'debit', 'balance_sheet', 'USD', 'other_receivables'],

        ['114', '11', 3, 'المخزون السلعي', 'asset', 'مخزون', false, 'debit', 'balance_sheet', 'USD', 'grp_inventory'],
        ['1141', '114', 4, 'مخزون البضاعة (المستودعات والمحل)', 'asset', 'مخزون', true, 'debit', 'balance_sheet', 'USD', 'inventory'],
        ['1142', '114', 4, 'بضاعة بالطريق وتكاليف شحن قيد التوزيع', 'asset', 'مخزون', true, 'debit', 'balance_sheet', 'USD', 'goods_in_transit'],

        ['12', '1', 2, 'الأصول الثابتة', 'asset', 'أصول ثابتة', false, 'debit', 'balance_sheet', 'USD', 'grp_fixed_assets'],
        ['121', '12', 3, 'الأصول الثابتة الملموسة', 'asset', 'أصول ثابتة', false, 'debit', 'balance_sheet', 'USD', 'grp_tangible_assets'],
        ['1211', '121', 4, 'الأثاث والمفروشات والتجهيزات', 'asset', 'أصول ثابتة', true, 'debit', 'balance_sheet', 'USD', 'furniture'],
        ['1212', '121', 4, 'الأجهزة الإلكترونية والحواسيب', 'asset', 'أصول ثابتة', true, 'debit', 'balance_sheet', 'USD', 'equipment'],
        ['1213', '121', 4, 'السيارات ووسائل النقل', 'asset', 'أصول ثابتة', true, 'debit', 'balance_sheet', 'USD', 'vehicles'],
        ['1214', '121', 4, 'العقارات والمباني', 'asset', 'أصول ثابتة', true, 'debit', 'balance_sheet', 'USD', 'buildings'],
        ['122', '12', 3, 'مجمّع الإهلاك', 'asset', 'مجمع إهلاك', false, 'credit', 'balance_sheet', 'USD', 'grp_accum_dep'],
        ['1221', '122', 4, 'مجمّع إهلاك الأصول الثابتة', 'asset', 'مجمع إهلاك', true, 'credit', 'balance_sheet', 'USD', 'accumulated_depreciation'],

        // 2 — الالتزامات
        ['2', null, 1, 'الالتزامات', 'liability', 'رئيسي', false, 'credit', 'balance_sheet', 'USD', 'root_liabilities'],
        ['21', '2', 2, 'الالتزامات المتداولة', 'liability', 'التزامات متداولة', false, 'credit', 'balance_sheet', 'USD', 'grp_current_liabilities'],
        ['211', '21', 3, 'الذمم الدائنة (الموردون والدائنون)', 'liability', 'ذمم دائنة', false, 'credit', 'balance_sheet', 'USD', 'grp_ap'],
        ['2111', '211', 4, 'ذمم الموردين (دولار أمريكي USD)', 'liability', 'ذمم موردين', true, 'credit', 'balance_sheet', 'USD', 'ap_usd'],
        ['2112', '211', 4, 'ذمم الموردين (ليرة سورية SYP)', 'liability', 'ذمم موردين', true, 'credit', 'balance_sheet', 'SYP', 'ap_syp'],
        ['2113', '211', 4, 'رواتب وأجور مستحقة الدفع', 'liability', 'مستحقات', true, 'credit', 'balance_sheet', 'USD', 'accrued_salaries'],
        ['2114', '211', 4, 'مصاريف مستحقة ودائنون متنوعون', 'liability', 'دائنون', true, 'credit', 'balance_sheet', 'USD', 'other_payables'],
        ['2115', '211', 4, 'ضرائب ورسوم مستحقة', 'liability', 'ضرائب', true, 'credit', 'balance_sheet', 'USD', 'taxes_payable'],

        ['212', '21', 3, 'أوراق الدفع والدفعات المقدمة', 'liability', 'أوراق دفع', false, 'credit', 'balance_sheet', 'USD', 'grp_notes_pay'],
        ['2121', '212', 4, 'أوراق دفع وشيكات صادرة', 'liability', 'أوراق دفع', true, 'credit', 'balance_sheet', 'USD', 'notes_payable'],
        ['2122', '212', 4, 'دفعات مقدمة من العملاء', 'liability', 'دفعات مقدمة', true, 'credit', 'balance_sheet', 'USD', 'customer_advances'],

        ['22', '2', 2, 'الالتزامات طويلة الأجل', 'liability', 'التزامات طويلة الأجل', false, 'credit', 'balance_sheet', 'USD', 'grp_lt_liabilities'],
        ['221', '22', 3, 'القروض والتمويل', 'liability', 'قروض', false, 'credit', 'balance_sheet', 'USD', 'grp_loans'],
        ['2211', '221', 4, 'قروض طويلة الأجل', 'liability', 'قروض', true, 'credit', 'balance_sheet', 'USD', 'long_term_loans'],

        // 3 — حقوق الملكية
        ['3', null, 1, 'حقوق الملكية', 'equity', 'رئيسي', false, 'credit', 'balance_sheet', 'USD', 'root_equity'],
        ['31', '3', 2, 'رأس المال', 'equity', 'رأس المال', false, 'credit', 'balance_sheet', 'USD', 'grp_capital'],
        ['311', '31', 3, 'رأس المال المدفوع', 'equity', 'رأس المال', false, 'credit', 'balance_sheet', 'USD', 'grp_paid_capital'],
        ['3111', '311', 4, 'رأس المال المدفوع', 'equity', 'رأس المال', true, 'credit', 'balance_sheet', 'USD', 'capital'],

        ['32', '3', 2, 'جاري ومسحوبات الشركاء', 'equity', 'شركاء', false, 'credit', 'balance_sheet', 'USD', 'grp_partners'],
        ['321', '32', 3, 'حسابات الشركاء', 'equity', 'شركاء', false, 'credit', 'balance_sheet', 'USD', 'grp_partner_accounts'],
        ['3211', '321', 4, 'مسحوبات الشركاء الشخصية', 'equity', 'مسحوبات', true, 'debit', 'balance_sheet', 'USD', 'withdrawals'],
        ['3212', '321', 4, 'جاري الشركاء', 'equity', 'جاري شركاء', true, 'credit', 'balance_sheet', 'USD', 'partner_current'],

        ['33', '3', 2, 'الأرباح المحتجزة والاحتياطيات', 'equity', 'احتياطيات', false, 'credit', 'balance_sheet', 'USD', 'grp_retained'],
        ['331', '33', 3, 'الأرباح المدوّرة والاحتياطيات', 'equity', 'احتياطيات', false, 'credit', 'balance_sheet', 'USD', 'grp_retained_sub'],
        ['3311', '331', 4, 'أرباح / خسائر مدوّرة من سنوات سابقة', 'equity', 'أرباح مدورة', true, 'credit', 'balance_sheet', 'USD', 'retained_earnings'],
        ['3312', '331', 4, 'احتياطي عام', 'equity', 'احتياطي', true, 'credit', 'balance_sheet', 'USD', 'general_reserve'],

        // 4 — الإيرادات
        ['4', null, 1, 'الإيرادات', 'revenue', 'رئيسي', false, 'credit', 'trading', 'USD', 'root_revenues'],
        ['41', '4', 2, 'إيرادات النشاط التجاري', 'revenue', 'مبيعات', false, 'credit', 'trading', 'USD', 'grp_sales'],
        ['411', '41', 3, 'المبيعات ومردوداتها وحسوماتها', 'revenue', 'مبيعات', false, 'credit', 'trading', 'USD', 'grp_sales_sub'],
        ['4111', '411', 4, 'المبيعات', 'revenue', 'مبيعات', true, 'credit', 'trading', 'USD', 'sales'],
        ['4112', '411', 4, 'مردودات المبيعات', 'revenue', 'مردودات مبيعات', true, 'debit', 'trading', 'USD', 'sales_returns'],
        ['4113', '411', 4, 'الحسم الممنوح للمبيعات', 'revenue', 'حسم مبيعات', true, 'debit', 'trading', 'USD', 'sales_discount'],

        ['42', '4', 2, 'إيرادات أخرى وفروق عملة', 'revenue', 'إيرادات أخرى', false, 'credit', 'pnl', 'USD', 'grp_other_rev'],
        ['421', '42', 3, 'الإيرادات غير التشغيلية وفروق الصرف', 'revenue', 'إيرادات أخرى', false, 'credit', 'pnl', 'USD', 'grp_other_rev_sub'],
        ['4211', '421', 4, 'إيرادات متنوعة وخدمات', 'revenue', 'إيرادات متنوعة', true, 'credit', 'pnl', 'USD', 'other_revenue'],
        ['4212', '421', 4, 'أرباح فروق صرف العملة', 'revenue', 'فروق عملة', true, 'credit', 'pnl', 'USD', 'fx_gain'],

        // 5 — التكاليف والمصاريف
        ['5', null, 1, 'التكاليف والمصاريف', 'expense', 'رئيسي', false, 'debit', 'pnl', 'USD', 'root_expenses'],
        ['51', '5', 2, 'تكلفة المبيعات والمشتريات', 'expense', 'تكلفة المبيعات', false, 'debit', 'trading', 'USD', 'grp_cogs'],
        ['511', '51', 3, 'حسابات تكلفة البضاعة والمتاجرة', 'expense', 'تكلفة المبيعات', false, 'debit', 'trading', 'USD', 'grp_cogs_sub'],
        ['5111', '511', 4, 'تكلفة البضاعة المباعة (COGS)', 'expense', 'تكلفة المبيعات', true, 'debit', 'trading', 'USD', 'cogs'],
        ['5112', '511', 4, 'المشتريات المباشرة', 'expense', 'مشتريات', true, 'debit', 'trading', 'USD', 'purchases'],
        ['5113', '511', 4, 'أجور شحن ومصاريف نقل المشتريات', 'expense', 'شحن مشتريات', true, 'debit', 'trading', 'USD', 'shipping_in'],
        ['5114', '511', 4, 'الحسم المكتسب من المشتريات', 'expense', 'حسم مشتريات', true, 'credit', 'trading', 'USD', 'purchase_discount'],

        ['52', '5', 2, 'المصاريف التشغيلية والإدارية', 'expense', 'مصاريف تشغيلية', false, 'debit', 'pnl', 'USD', 'grp_opex'],
        ['521', '52', 3, 'المصاريف العمومية والإدارية والرواتب', 'expense', 'مصاريف إدارية', false, 'debit', 'pnl', 'USD', 'grp_opex_sub'],
        ['5211', '521', 4, 'الرواتب والأجور', 'expense', 'رواتب', true, 'debit', 'pnl', 'USD', 'salaries'],
        ['5212', '521', 4, 'إيجار المحل والمستودعات', 'expense', 'إيجارات', true, 'debit', 'pnl', 'USD', 'exp_rent'],
        ['5213', '521', 4, 'كهرباء ومياه وإنترنت واتصالات', 'expense', 'مرافق واتصالات', true, 'debit', 'pnl', 'USD', 'exp_utilities'],
        ['5214', '521', 4, 'نقل وتوصيل مبيعات', 'expense', 'نقل وتوصيل', true, 'debit', 'pnl', 'USD', 'exp_delivery'],
        ['5215', '521', 4, 'صيانة وتشغيل', 'expense', 'صيانة', true, 'debit', 'pnl', 'USD', 'exp_maintenance'],
        ['5216', '521', 4, 'ضيافة ونظافة وقرطاسية', 'expense', 'ضيافة ومستلزمات', true, 'debit', 'pnl', 'USD', 'exp_office'],
        ['5217', '521', 4, 'تسويق وإعلان', 'expense', 'تسويق', true, 'debit', 'pnl', 'USD', 'exp_marketing'],
        ['5218', '521', 4, 'مصاريف إهلاك الأصول الثابتة', 'expense', 'إهلاك', true, 'debit', 'pnl', 'USD', 'exp_depreciation'],
        ['5219', '521', 4, 'مصاريف عمومية وإدارية أخرى', 'expense', 'مصاريف عامة', true, 'debit', 'pnl', 'USD', 'expenses'],

        ['53', '5', 2, 'المصاريف التمويلية وفروق العملة', 'expense', 'مصاريف تمويلية', false, 'debit', 'pnl', 'USD', 'grp_finance_exp'],
        ['531', '53', 3, 'فروق العملة والعمولات البنكية', 'expense', 'فروق عملة', false, 'debit', 'pnl', 'USD', 'grp_fx_loss'],
        ['5311', '531', 4, 'خسائر فروق صرف العملة', 'expense', 'فروق عملة', true, 'debit', 'pnl', 'USD', 'fx_loss'],
        ['5312', '531', 4, 'عمولات بنكية وتحويل', 'expense', 'عمولات', true, 'debit', 'pnl', 'USD', 'bank_fees'],
    ];

    /**
     * Map legacy flat account codes to new hierarchical system codes so existing shops
     * upgrade cleanly in-place without breaking any journal_lines foreign keys.
     */
    private const LEGACY_CODE_UPGRADE = [
        '1000' => '1111',
        '1010' => '1112',
        '1100' => '1131',
        '1110' => '1132',
        '1200' => '1141',
        '2000' => '2111',
        '2010' => '2112',
        '3000' => '3111',
        '3100' => '3211',
        '4000' => '4111',
        '4100' => '4112',
        '4200' => '4113',
        '4300' => '4212',
        '5000' => '5111',
        '5100' => '5112',
        '5150' => '5114',
        '5200' => '5219',
        '5300' => '5211',
    ];

    public function bootstrap(Shop $shop): void
    {
        if (!$shop->posting_enabled) {
            $shop->forceFill(['posting_enabled' => true])->saveQuietly();
        }
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
        // Step 1: Migrate any legacy flat accounts in-place if target new code does not exist yet
        foreach (self::LEGACY_CODE_UPGRADE as $oldCode => $newCode) {
            $old = Account::withoutGlobalScopes()->where('shop_id', $shopId)->where('code', $oldCode)->first();
            if (!$old) {
                continue;
            }
            $newExists = Account::withoutGlobalScopes()->where('shop_id', $shopId)->where('code', $newCode)->exists();
            if (!$newExists) {
                $old->code = $newCode;
                $old->saveQuietly();
            }
        }

        // Step 2: Create or sync the full 4-level hierarchical tree
        $idByCode = [];
        foreach (self::STANDARD_TREE as [$code, $parentCode, $level, $name, $type, $classification, $isPostable, $normalBalance, $statementType, $currency, $systemKey]) {
            $parentId = $parentCode ? ($idByCode[$parentCode] ?? null) : null;

            $account = Account::withoutGlobalScopes()
                ->where('shop_id', $shopId)
                ->where(function ($q) use ($code, $systemKey) {
                    $q->where('code', $code)->orWhere('system_key', $systemKey);
                })
                ->first();

            if ($account) {
                $account->fill([
                    'parent_id' => $parentId,
                    'level' => $level,
                    'type' => $type,
                    'classification' => $account->classification ?: $classification,
                    'is_postable' => $isPostable,
                    'normal_balance' => $normalBalance,
                    'statement_type' => $statementType,
                    'currency' => $currency,
                    'is_system' => true,
                    'system_key' => $systemKey,
                ]);
                // Keep custom shop name on postable accounts if user renamed it, else upgrade legacy defaults
                if (!$account->name) {
                    $account->name = $name;
                }
                $account->saveQuietly();
            } else {
                $account = Account::withoutGlobalScopes()->create([
                    'shop_id' => $shopId,
                    'parent_id' => $parentId,
                    'level' => $level,
                    'code' => $code,
                    'name' => $name,
                    'type' => $type,
                    'classification' => $classification,
                    'is_postable' => $isPostable,
                    'normal_balance' => $normalBalance,
                    'statement_type' => $statementType,
                    'currency' => $currency,
                    'is_system' => true,
                    'system_key' => $systemKey,
                    'is_active' => true,
                ]);
            }

            $idByCode[$code] = $account->id;
        }

        // Step 3: Attach any custom user-created accounts missing parent_id under their root type group
        $fallbackParentByType = [
            'asset' => $idByCode['113'] ?? $idByCode['1'] ?? null,
            'liability' => $idByCode['211'] ?? $idByCode['2'] ?? null,
            'equity' => $idByCode['331'] ?? $idByCode['3'] ?? null,
            'revenue' => $idByCode['421'] ?? $idByCode['4'] ?? null,
            'expense' => $idByCode['521'] ?? $idByCode['5'] ?? null,
        ];

        $orphans = Account::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->whereNull('parent_id')
            ->where('level', '>', 1)
            ->get();

        foreach ($orphans as $orphan) {
            if (in_array($orphan->code, ['1', '2', '3', '4', '5'], true)) {
                continue;
            }
            $orphan->parent_id = $fallbackParentByType[$orphan->type] ?? null;
            $orphan->level = 4;
            $orphan->is_postable = true;
            $orphan->normal_balance = in_array($orphan->type, ['asset', 'expense'], true) ? 'debit' : 'credit';
            $orphan->statement_type = in_array($orphan->type, ['revenue', 'expense'], true) ? 'pnl' : 'balance_sheet';
            $orphan->saveQuietly();
        }
    }

    public function ensureMappings(string $shopId): void
    {
        $codeByRole = [
            'cash_usd' => '1111',
            'cash_syp' => '1112',
            'bank_usd' => '1113',
            'ar_usd' => '1131',
            'ar_syp' => '1132',
            'employee_advances' => '1133',
            'inventory' => '1141',
            'ap_usd' => '2111',
            'ap_syp' => '2112',
            'capital' => '3111',
            'withdrawals' => '3211',
            'retained_earnings' => '3311',
            'sales' => '4111',
            'sales_returns' => '4112',
            'sales_discount' => '4113',
            'other_revenue' => '4211',
            'fx_gain' => '4212',
            'cogs' => '5111',
            'purchases' => '5112',
            'shipping_in' => '5113',
            'purchase_discount' => '5114',
            'salaries' => '5211',
            'expenses' => '5219',
            'fx_loss' => '5311',
            'fx_diff' => '4212',
        ];

        foreach ($codeByRole as $role => $code) {
            $account = Account::withoutGlobalScopes()
                ->where('shop_id', $shopId)
                ->where('code', $code)
                ->first();

            if (!$account) {
                continue;
            }

            $existing = AccountMapping::withoutGlobalScopes()
                ->where('shop_id', $shopId)
                ->where('role', $role)
                ->first();

            if (!$existing) {
                AccountMapping::withoutGlobalScopes()->create([
                    'shop_id' => $shopId,
                    'role' => $role,
                    'account_id' => $account->id,
                ]);
            } elseif (!$existing->account_id || !Account::withoutGlobalScopes()->where('id', $existing->account_id)->where('is_postable', true)->exists()) {
                $existing->update(['account_id' => $account->id]);
            }
        }
    }

    public function nextChildCode(string $shopId, Account $parent): string
    {
        $children = Account::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('parent_id', $parent->id)
            ->pluck('code');

        $prefix = (string) $parent->code;
        $maxSuffix = 0;

        foreach ($children as $code) {
            $str = (string) $code;
            if (str_starts_with($str, $prefix) && strlen($str) > strlen($prefix)) {
                $suffix = substr($str, strlen($prefix));
                if (ctype_digit($suffix)) {
                    $maxSuffix = max($maxSuffix, (int) $suffix);
                }
            }
        }

        if ($parent->level <= 2) {
            return $prefix . ($maxSuffix + 1);
        }

        $pad = $parent->level === 3 ? 1 : 2;
        return $prefix . str_pad((string) ($maxSuffix + 1), $pad, '0', STR_PAD_LEFT);
    }

    public function mappingStatus(string $shopId): array
    {
        $this->ensureAccounts($shopId);
        $this->ensureMappings($shopId);

        $rows = [];
        $missingRequired = 0;
        foreach (self::ROLES as $role => [$label, $required]) {
            $map = AccountMapping::withoutGlobalScopes()->with('account')->where('shop_id', $shopId)->where('role', $role)->first();
            $linked = (bool) ($map?->account_id && $map?->account?->is_postable);
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
