<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CashMovement;
use App\Models\Contact;
use App\Models\EmployeeMovement;
use App\Models\Expense;
use App\Models\Item;
use App\Models\ItemLocationStock;
use App\Models\PartnerWithdrawal;
use App\Models\SalaryRecord;
use App\Models\StockTransfer;
use App\Models\Transaction;
use App\Models\Voucher;

class ControlService
{
    public function __construct(
        protected AccountingService $accounting,
        protected ShopSetupService $setup,
        protected JournalService $journal,
    ) {}

    public function preApproval(string $shopId, float $rate): array
    {
        $txs = Transaction::where('shop_id', $shopId)->get();
        $sales = $txs->where('type', 'sale');
        $purchases = $txs->where('type', 'purchase');
        $returns = $txs->whereIn('type', ['sale_return', 'purchase_return']);
        $mapping = $this->setup->mappingStatus($shopId);
        $accounts = Account::where('shop_id', $shopId)->get();
        $duplicateCodes = $accounts->groupBy('code')->filter(fn ($g) => $g->count() > 1)->count();
        $locations = $this->accounting->getLocationInventory($shopId);
        $negativeLocations = collect($locations)->where('negative', true)->count();
        $badTransfers = StockTransfer::where('shop_id', $shopId)
            ->get()
            ->filter(fn ($t) => $t->qty <= 0 || $t->from_location_id === $t->to_location_id)
            ->count();
        $purchasesNoSupplier = $purchases->filter(fn ($t) => !$t->contact_id)->count();
        $purchasesNonPositive = $purchases->filter(fn ($t) => (float) $t->total <= 0)->count();
        $overpaid = $txs->filter(fn ($t) => (float) $t->paid_amount > (float) $t->total + 0.001)->count();
        $cashDup = CashMovement::where('shop_id', $shopId)->whereNotNull('voucher_number')
            ->get()->groupBy(fn ($m) => $m->type.'|'.$m->voucher_number)->filter(fn ($g) => $g->count() > 1)->count();
        $unclassified = PartnerWithdrawal::where('shop_id', $shopId)->where('classification_status', '!=', 'مصنف')->count();
        $incompleteSalaries = SalaryRecord::where('shop_id', $shopId)->get()->filter(fn ($s) => !$s->employee_id || (float) $s->net < 0)->count();
        $incompleteEmp = EmployeeMovement::where('shop_id', $shopId)->get()->filter(fn ($m) => !$m->employee_id || (float) $m->amount <= 0)->count();
        $pnl = $this->accounting->getProfitAndLossReport($shopId);
        $expSum = (float) Expense::where('shop_id', $shopId)->sum('amount_usd');
        $expCash = (float) CashMovement::where('shop_id', $shopId)->where('type', 'expense')->sum('amount_usd');
        $salSum = (float) SalaryRecord::where('shop_id', $shopId)->sum('amount_usd');
        $salCash = (float) CashMovement::where('shop_id', $shopId)->where('type', 'salary')->sum('amount_usd');
        $vouchersReceipt = Voucher::where('shop_id', $shopId)->where('type', 'receipt')->where('affects_balance', false)->count();
        $vouchersPayment = Voucher::where('shop_id', $shopId)->where('type', 'payment')->where('affects_balance', false)->count();
        $openingItemsNoCost = Item::where('shop_id', $shopId)->where('opening_qty', '>', 0)->where('opening_cost_usd', '<=', 0)->count();
        $trial = $this->journal->trialBalance($shopId);

        $checks = [
            ['عدد العمليات', $txs->count(), 'رقابي'],
            ['إجمالي المبيعات (عملة الإدخال)', $sales->sum('total'), 'رقابي'],
            ['عدد العملاء', Contact::where('type', 'customer')->count(), 'رقابي'],
            ['عدد الأصناف', Item::count(), 'رقابي'],
            ['رقابة المرتجعات', $returns->count(), 'عدد حركات المرتجع'],
            ['مشتريات بلا مورد', $purchasesNoSupplier, $purchasesNoSupplier ? 'يجب أن تكون 0' : 'سليم'],
            ['مشتريات بمبلغ غير موجب', $purchasesNonPositive, $purchasesNonPositive ? 'يجب أن تكون 0' : 'سليم'],
            ['مدفوع أكبر من الإجمالي', $overpaid, $overpaid ? 'راجع الفواتير' : 'سليم'],
            ['أدوار حسابات غير مربوطة', $mapping['missing_required'], $mapping['ready'] ? 'جاهز للترحيل' : 'لا تفعّل الترحيل'],
            ['أرقام حسابات مكررة', $duplicateCodes, $duplicateCodes ? 'يجب أن تكون 0' : 'سليم'],
            ['حركات الشركاء غير المصنفة', $unclassified, $unclassified ? 'راجع التصنيف' : 'سليم'],
            ['مخزون افتتاحي موجب بلا تكلفة', $openingItemsNoCost, $openingItemsNoCost ? 'راجع التكلفة' : 'سليم'],
            ['أرصدة سالبة على مستوى الموقع', $negativeLocations, $negativeLocations ? 'يجب أن تكون 0' : 'سليم'],
            ['مناقلات غير سليمة', $badTransfers, $badTransfers ? 'يجب أن تكون 0' : 'سليم'],
            ['حركات صندوق مكررة (رقم السند)', $cashDup, $cashDup ? 'يجب أن تكون 0' : 'سليم'],
            ['رواتب غير مكتملة', $incompleteSalaries, $incompleteSalaries ? 'يجب أن تكون 0' : 'سليم'],
            ['حركات موظفين غير مكتملة', $incompleteEmp, $incompleteEmp ? 'يجب أن تكون 0' : 'سليم'],
            ['مطابقة المصاريف العامة (جدول − صندوق)', round($expSum - $expCash, 2), abs($expSum - $expCash) < 0.01 ? 'سليم' : 'راجع المصدر'],
            ['مطابقة الرواتب (جدول − صندوق)', round($salSum - $salCash, 2), abs($salSum - $salCash) < 0.01 ? 'سليم' : 'راجع المصدر'],
            ['سندات قبض توثيق فقط', $vouchersReceipt, 'لا تُحسب في الذمم ما لم يُفعّل الأثر'],
            ['سندات صرف توثيق فقط', $vouchersPayment, 'لا تُحسب في الذمم ما لم يُفعّل الأثر'],
            ['فرق ميزان المراجعة', $trial['difference'], $trial['balanced'] ? 'متوازن' : 'غير متوازن'],
            ['صافي الربح التقديري $', $pnl['estimated_net_profit_usd'], 'رقابي'],
        ];

        $problems = collect($checks)->filter(fn ($c) => is_numeric($c[1]) && (float) $c[1] != 0 && str_contains((string) $c[2], 'يجب'))->count();

        return [
            'checks' => $checks,
            'mapping' => $mapping,
            'problems' => $problems,
            'status' => $problems === 0 ? 'لا توجد فحوص حمراء قبل الاعتماد الرقابي' : 'توجد بنود تحتاج مراجعة',
            'rate' => $rate,
        ];
    }

    public function dailyReport(string $shopId, string $date, float $rate): array
    {
        $txs = Transaction::where('shop_id', $shopId)->whereDate('date', $date)->get();
        $cash = CashMovement::where('shop_id', $shopId)->whereDate('date', $date)->get();
        $sales = $txs->where('type', 'sale')->sum('total_usd') - $txs->where('type', 'sale_return')->sum('total_usd');
        $receipts = $cash->where('type', 'receipt')->sum('amount_usd') + $txs->where('type', 'sale')->sum('paid_amount_usd');
        $payments = $cash->where('type', 'payment')->sum('amount_usd');

        return [
            'date' => $date,
            'net_sales_usd' => round($sales, 2),
            'receipts_usd' => round($receipts, 2),
            'payments_usd' => round($payments, 2),
            'expenses_usd' => round($cash->where('type', 'expense')->sum('amount_usd'), 2),
            'operations' => $txs->count(),
            'cash_usd' => round($cash->where('currency', 'USD')->whereIn('type', ['receipt', 'opening'])->sum('amount') - $cash->where('currency', 'USD')->whereIn('type', ['payment', 'expense', 'salary', 'withdrawal'])->sum('amount'), 2),
        ];
    }

    public function ownerReport(string $shopId, float $rate): array
    {
        $pnl = $this->accounting->getProfitAndLossReport($shopId);
        $cash = $this->accounting->getCashboxSummary($shopId, $rate);
        $bs = $this->accounting->getBalanceSheet($shopId, $rate);
        $low = collect($this->accounting->getInventoryValuation($shopId))->where('is_low_stock', true)->count();

        return compact('pnl', 'cash', 'bs', 'low');
    }

    public function kpis(string $shopId, float $rate): array
    {
        $owner = $this->ownerReport($shopId, $rate);
        $customers = Contact::where('type', 'customer')->get();
        $debtors = 0;
        foreach ($customers as $c) {
            $s = $this->accounting->getContactStatement($c);
            if ($s['final_balance_usd'] > 0 || $s['final_balance_syp'] > 0) {
                $debtors++;
            }
        }
        $stock = $this->accounting->getInventoryValuation($shopId);
        $stale = collect($stock)->filter(fn ($sr) => $sr['sold_qty'] == 0 && $sr['current_stock'] > 0)->count();
        $receiptRatio = $owner['pnl']['net_sales_usd'] > 0
            ? round(($owner['cash']['usd']['details']['customer_receipts'] + $owner['cash']['usd']['details']['cash_sales']) / $owner['pnl']['net_sales_usd'] * 100, 1)
            : 0;

        return [
            'ar_usd' => $owner['bs']['ar_usd'],
            'debtors' => $debtors,
            'receipt_ratio' => $receiptRatio,
            'low_stock' => $owner['low'],
            'stale' => $stale,
            'net_profit' => $owner['pnl']['estimated_net_profit_usd'],
        ];
    }

    public function voucherMatch(string $shopId): array
    {
        $receipts = Voucher::with('contact')->where('type', 'receipt')->orderByDesc('date')->get();
        $payments = Voucher::with('contact')->where('type', 'payment')->orderByDesc('date')->get();
        $cash = CashMovement::where('shop_id', $shopId)->get();

        return [
            'receipts' => $receipts,
            'payments' => $payments,
            'daily_ops' => Transaction::where('shop_id', $shopId)->count(),
            'cash_count' => $cash->count(),
            'unlinked_receipts' => $receipts->whereNull('cash_movement_id')->count(),
            'unlinked_payments' => $payments->whereNull('cash_movement_id')->count(),
        ];
    }
}
