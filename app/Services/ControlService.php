<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CashMovement;
use App\Models\Contact;
use App\Models\CurrencyExchange;
use App\Models\EmployeeMovement;
use App\Models\Expense;
use App\Models\Item;
use App\Models\ItemLocationStock;
use App\Models\PartnerWithdrawal;
use App\Models\SalaryRecord;
use App\Models\ShippingExpense;
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
        $txs = Transaction::with(['contact', 'item', 'lines'])
            ->where('shop_id', $shopId)
            ->whereDate('date', $date)
            ->orderBy('id')
            ->get();

        $cash = CashMovement::with('contact')
            ->where('shop_id', $shopId)
            ->whereDate('date', $date)
            ->orderBy('id')
            ->get();

        $withdrawals = PartnerWithdrawal::with('partner')
            ->where('shop_id', $shopId)
            ->whereDate('date', $date)
            ->orderBy('id')
            ->get();

        $salaries = SalaryRecord::with('employee')
            ->where('shop_id', $shopId)
            ->whereDate('date', $date)
            ->orderBy('id')
            ->get();

        $employeeMovements = EmployeeMovement::with('employee')
            ->where('shop_id', $shopId)
            ->whereDate('date', $date)
            ->orderBy('id')
            ->get();

        $expenses = Expense::with('costCenter')
            ->where('shop_id', $shopId)
            ->whereDate('date', $date)
            ->orderBy('id')
            ->get();

        $shippings = ShippingExpense::with('transaction')
            ->where('shop_id', $shopId)
            ->whereDate('date', $date)
            ->orderBy('id')
            ->get();

        $exchanges = CurrencyExchange::where('shop_id', $shopId)
            ->whereDate('date', $date)
            ->orderBy('id')
            ->get();

        $linkedTxIds = $cash->where('source_type', 'transaction')->pluck('source_id')->filter()->all();
        $unlinkedTxs = $txs->reject(fn ($t) => in_array($t->id, $linkedTxIds, true));

        $sales = (float) $txs->where('type', 'sale')->sum('total_usd')
            - (float) $txs->where('type', 'sale_return')->sum('total_usd');
        $purchases = (float) $txs->where('type', 'purchase')->sum('total_usd')
            - (float) $txs->where('type', 'purchase_return')->sum('total_usd');

        $salesDiscountUsd = (float) $txs->where('type', 'sale')->sum('discount_usd');
        $purchaseDiscountUsd = (float) $txs->where('type', 'purchase')->sum('discount_usd');

        $receiptsFromCash = (float) $cash
            ->where('type', 'receipt')
            ->reject(fn ($m) => $m->source_type === 'currency_exchange_in')
            ->sum('amount_usd');
        $unlinkedCashSalesUsd = (float) $unlinkedTxs
            ->whereIn('type', ['sale', 'purchase_return'])
            ->sum(fn ($t) => (float) ($t->paid_amount_usd ?: ($t->payment_type === 'cash' ? $t->total_usd : 0)));
        $receipts = $receiptsFromCash + $unlinkedCashSalesUsd;

        $paymentsFromCash = (float) $cash
            ->where('type', 'payment')
            ->reject(fn ($m) => in_array($m->source_type, ['currency_exchange_out', 'shipping_expense', 'employee_movement'], true))
            ->sum('amount_usd');
        $unlinkedCashPurchasesUsd = (float) $unlinkedTxs
            ->whereIn('type', ['purchase', 'sale_return'])
            ->sum(fn ($t) => (float) ($t->paid_amount_usd ?: ($t->payment_type === 'cash' ? $t->total_usd : 0)));
        $payments = $paymentsFromCash + $unlinkedCashPurchasesUsd;

        $manualCashExpensesUsd = (float) $cash->where('type', 'expense')->whereNull('source_type')->sum('amount_usd');
        $expensesUsd = (float) $expenses->sum('amount_usd') + $manualCashExpensesUsd;

        $shippingExpensesUsd = (float) $shippings->sum('amount_usd');

        $manualCashWithdrawalsUsd = (float) $cash->where('type', 'withdrawal')->whereNull('source_type')->sum('amount_usd');
        $partnerWithdrawalsUsd = (float) $withdrawals->sum('amount_usd') + $manualCashWithdrawalsUsd;

        $manualCashSalariesUsd = (float) $cash->where('type', 'salary')->whereNull('source_type')->sum('amount_usd');
        $salariesUsd = (float) $salaries->sum('amount_usd') + $manualCashSalariesUsd;
        $employeeMovementsUsd = (float) $employeeMovements->sum('amount_usd');
        $employeeTotalUsd = $salariesUsd + $employeeMovementsUsd;

        $currencyExchangesUsd = (float) $exchanges->sum('amount_usd');

        // Dual currency daily net cashbox movements (includes currency exchanges & unlinked cash invoices)
        $cashUsdIn = (float) $cash->where('currency', 'USD')->whereIn('type', ['receipt', 'opening'])->sum('amount')
            + (float) $unlinkedTxs->where('currency', 'USD')->whereIn('type', ['sale', 'purchase_return'])->sum(fn ($t) => (float) ($t->paid_amount ?: ($t->payment_type === 'cash' ? $t->total : 0)));
        $cashUsdOut = (float) $cash->where('currency', 'USD')->whereIn('type', ['payment', 'expense', 'salary', 'withdrawal'])->sum('amount')
            + (float) $unlinkedTxs->where('currency', 'USD')->whereIn('type', ['purchase', 'sale_return'])->sum(fn ($t) => (float) ($t->paid_amount ?: ($t->payment_type === 'cash' ? $t->total : 0)));

        $cashSypIn = (float) $cash->where('currency', 'SYP')->whereIn('type', ['receipt', 'opening'])->sum('amount')
            + (float) $unlinkedTxs->where('currency', 'SYP')->whereIn('type', ['sale', 'purchase_return'])->sum(fn ($t) => (float) ($t->paid_amount ?: ($t->payment_type === 'cash' ? $t->total : 0)));
        $cashSypOut = (float) $cash->where('currency', 'SYP')->whereIn('type', ['payment', 'expense', 'salary', 'withdrawal'])->sum('amount')
            + (float) $unlinkedTxs->where('currency', 'SYP')->whereIn('type', ['purchase', 'sale_return'])->sum(fn ($t) => (float) ($t->paid_amount ?: ($t->payment_type === 'cash' ? $t->total : 0)));

        $movements = [];

        foreach ($txs as $tx) {
            $typeLabel = match ($tx->type) {
                'sale' => 'فاتورة بيع',
                'purchase' => 'فاتورة شراء',
                'sale_return' => 'مرتجع مبيعات',
                'purchase_return' => 'مرتجع مشتريات',
                default => $tx->type,
            };
            $discountText = (float) $tx->discount > 0
                ? ' · ' . $tx->discountLabel() . ': ' . number_format((float) $tx->discount, 2) . ' ' . $tx->currency
                : '';
            $movements[] = [
                'category' => 'invoice',
                'category_label' => 'فواتير',
                'type_label' => $typeLabel . ' (' . ($tx->payment_type === 'cash' ? 'نقدي' : 'آجل') . ')',
                'reference' => $tx->invoice_number,
                'party' => $tx->contact->name ?? '—',
                'description' => trim(($tx->item->item_name ?? 'بنود متعددة') . $discountText . ($tx->notes ? ' — ' . $tx->notes : '')),
                'direction' => in_array($tx->type, ['sale', 'purchase_return'], true) ? 'in' : 'out',
                'amount' => (float) $tx->total,
                'currency' => $tx->currency,
                'amount_usd' => (float) $tx->total_usd,
                'url' => route('transactions.show', $tx),
            ];
        }

        foreach ($withdrawals as $w) {
            $movements[] = [
                'category' => 'partner',
                'category_label' => 'مسحوبات الشركاء',
                'type_label' => $w->nature ?: 'مسحوب شريك',
                'reference' => $w->voucher_number ?: 'WD-' . $w->id,
                'party' => $w->partner->name ?? 'شريك',
                'description' => trim(($w->description ?: $w->notes ?: 'مسحوب شريك') . ' (' . $w->classification_status . ')'),
                'direction' => 'out',
                'amount' => (float) $w->amount,
                'currency' => $w->currency,
                'amount_usd' => (float) $w->amount_usd,
                'url' => route('partners.index'),
            ];
        }

        foreach ($salaries as $s) {
            $movements[] = [
                'category' => 'employee',
                'category_label' => 'الموظفون',
                'type_label' => 'راتب شهر ' . $s->month,
                'reference' => $s->voucher_number ?: 'SAL-' . $s->id,
                'party' => $s->employee->name ?? 'موظف',
                'description' => $s->notes ?: ('أساسي ' . number_format((float) $s->basic, 2) . ' + إضافي ' . number_format((float) $s->extra, 2) . ' - حسم ' . number_format((float) $s->deduction, 2)),
                'direction' => 'out',
                'amount' => (float) $s->net,
                'currency' => $s->currency,
                'amount_usd' => (float) $s->amount_usd,
                'url' => route('salaries.index'),
            ];
        }

        foreach ($employeeMovements as $em) {
            $movements[] = [
                'category' => 'employee',
                'category_label' => 'الموظفون',
                'type_label' => $em->typeLabel(),
                'reference' => $em->voucher_number ?: 'EMP-' . $em->id,
                'party' => $em->employee->name ?? 'موظف',
                'description' => trim(($em->month ? 'شهر ' . $em->month . ' — ' : '') . ($em->notes ?: $em->typeLabel())),
                'direction' => 'out',
                'amount' => (float) $em->amount,
                'currency' => $em->currency,
                'amount_usd' => (float) $em->amount_usd,
                'url' => route('employee-movements.index'),
            ];
        }

        foreach ($expenses as $e) {
            $movements[] = [
                'category' => 'expense',
                'category_label' => 'مصاريف عامة',
                'type_label' => $e->expense_type,
                'reference' => $e->voucher_number ?: 'EXP-' . $e->id,
                'party' => $e->costCenter->name ?? 'مصروف عام',
                'description' => $e->description ?: ($e->notes ?: $e->expense_type),
                'direction' => 'out',
                'amount' => (float) $e->amount,
                'currency' => $e->currency,
                'amount_usd' => (float) $e->amount_usd,
                'url' => route('expenses.index'),
            ];
        }

        foreach ($shippings as $sh) {
            $movements[] = [
                'category' => 'shipping',
                'category_label' => 'أجور شحن',
                'type_label' => $sh->shipping_type ?: 'أجور شحن مشتريات',
                'reference' => $sh->voucher_number ?: 'SHP-' . $sh->id,
                'party' => $sh->carrier_name ?: ($sh->transaction?->invoice_number ? 'فاتورة ' . $sh->transaction->invoice_number : 'شحن مشتريات'),
                'description' => trim(($sh->transaction ? 'مرتبطة بفاتورة شراء #' . $sh->transaction->invoice_number . ' — ' : '') . ($sh->description ?: $sh->notes ?: 'مصاريف شحن')),
                'direction' => 'out',
                'amount' => (float) $sh->amount,
                'currency' => $sh->currency,
                'amount_usd' => (float) $sh->amount_usd,
                'url' => route('shipping.index'),
            ];
        }

        foreach ($exchanges as $ex) {
            $movements[] = [
                'category' => 'exchange',
                'category_label' => 'تحويل عملة',
                'type_label' => $ex->direction === 'syp_to_usd' ? 'تحويل ل.س ← دولار' : 'تحويل دولار ← ل.س',
                'reference' => $ex->reference_number ?: 'EX-' . $ex->id,
                'party' => 'سعر الصرف ' . number_format((float) $ex->exchange_rate),
                'description' => number_format((float) $ex->from_amount, 2) . ' ' . $ex->from_currency . ' ← ' . number_format((float) $ex->to_amount, 2) . ' ' . $ex->to_currency . ($ex->notes ? ' — ' . $ex->notes : ''),
                'direction' => 'neutral',
                'amount' => (float) $ex->from_amount,
                'currency' => $ex->from_currency,
                'amount_usd' => (float) $ex->amount_usd,
                'url' => route('currency-exchanges.index'),
            ];
        }

        $handledSources = [
            'transaction',
            'expense',
            'salary',
            'employee_movement',
            'withdrawal',
            'shipping_expense',
            'currency_exchange_in',
            'currency_exchange_out',
        ];

        foreach ($cash as $cm) {
            if ($cm->source_type && in_array($cm->source_type, $handledSources, true)) {
                continue;
            }
            $cat = match ($cm->type) {
                'withdrawal' => 'partner',
                'salary' => 'employee',
                'expense' => 'expense',
                default => 'cashbox',
            };
            $catLabel = match ($cat) {
                'partner' => 'مسحوبات الشركاء',
                'employee' => 'الموظفون',
                'expense' => 'مصاريف عامة',
                default => $cm->source_type === 'voucher' ? 'سندات' : 'حركة صندوق',
            };
            $typeLabel = match ($cm->type) {
                'receipt' => 'سند قبض / وارد صندوق',
                'payment' => 'سند صرف / صادر صندوق',
                'expense' => 'مصروف نقدي مباشر',
                'salary' => 'راتب نقدي مباشر',
                'withdrawal' => 'مسحوب شريك نقدي مباشر',
                'opening' => 'رصيد افتتاحي',
                default => $cm->type,
            };
            $movements[] = [
                'category' => $cat,
                'category_label' => $catLabel,
                'type_label' => $typeLabel,
                'reference' => $cm->voucher_number ?: 'CM-' . $cm->id,
                'party' => $cm->contact->name ?? $cm->category ?? 'الصندوق',
                'description' => $cm->notes ?: ($cm->category ?: $typeLabel),
                'direction' => in_array($cm->type, ['receipt', 'opening'], true) ? 'in' : 'out',
                'amount' => (float) $cm->amount,
                'currency' => $cm->currency,
                'amount_usd' => (float) $cm->amount_usd,
                'url' => route('cashbox.index'),
            ];
        }

        return [
            'date' => $date,
            'net_sales_usd' => round($sales, 2),
            'net_purchases_usd' => round($purchases, 2),
            'sales_discount_usd' => round($salesDiscountUsd, 2),
            'purchase_discount_usd' => round($purchaseDiscountUsd, 2),
            'receipts_usd' => round($receipts, 2),
            'payments_usd' => round($payments, 2),
            'expenses_usd' => round($expensesUsd, 2),
            'shipping_expenses_usd' => round($shippingExpensesUsd, 2),
            'partner_withdrawals_usd' => round($partnerWithdrawalsUsd, 2),
            'salaries_usd' => round($salariesUsd, 2),
            'employee_movements_usd' => round($employeeMovementsUsd, 2),
            'employee_total_usd' => round($employeeTotalUsd, 2),
            'currency_exchanges_usd' => round($currencyExchangesUsd, 2),
            'currency_exchanges_count' => $exchanges->count(),
            'operations' => $txs->count(),
            'total_movements' => count($movements),
            'cash_usd' => round($cashUsdIn - $cashUsdOut, 2),
            'cash_syp' => round($cashSypIn - $cashSypOut, 2),
            'movements' => $movements,
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
