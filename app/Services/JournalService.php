<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CashMovement;
use App\Models\Contact;
use App\Models\CurrencyExchange;
use App\Models\EmployeeMovement;
use App\Models\Expense;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\PartnerWithdrawal;
use App\Models\SalaryRecord;
use App\Models\ShippingExpense;
use App\Models\Shop;
use App\Models\Transaction;
use App\Models\Voucher;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class JournalService
{
    public const BASE_CURRENCY = 'USD';
    public const SECONDARY_CURRENCY = 'SYP';

    public function __construct(
        protected ShopSetupService $setup,
    ) {}

    public function ensureShopReady(string $shopId): ?Shop
    {
        $shop = Shop::find($shopId);
        if (!$shop) {
            return null;
        }
        $hasTree = Account::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('system_key', 'cash_usd')
            ->exists();

        if (!$hasTree) {
            $this->setup->bootstrap($shop);
        }

        return $shop;
    }

    public function resolveRate(string $shopId, ?float $rate = null): float
    {
        if ($rate !== null && $rate > 0) {
            return $rate;
        }
        $shopRate = (float) (Shop::where('id', $shopId)->value('exchange_rate') ?? 0);
        return $shopRate > 0 ? $shopRate : 14500.0;
    }

    public function accountId(string $shopId, string $role): ?int
    {
        $id = AccountMapping::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('role', $role)
            ->value('account_id');

        if ($id) {
            $isPostable = Account::withoutGlobalScopes()->where('id', $id)->where('is_postable', true)->exists();
            if ($isPostable) {
                return (int) $id;
            }
        }

        $this->ensureShopReady($shopId);
        $this->setup->ensureMappings($shopId);

        $fallbackId = AccountMapping::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('role', $role)
            ->value('account_id');

        if ($fallbackId) {
            return (int) $fallbackId;
        }

        $byKey = Account::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('system_key', $role)
            ->where('is_postable', true)
            ->value('id');

        return $byKey ? (int) $byKey : null;
    }

    public function cashRole(string $currency): string
    {
        return strtoupper($currency) === 'SYP' ? 'cash_syp' : 'cash_usd';
    }

    public function arRole(string $currency): string
    {
        return strtoupper($currency) === 'SYP' ? 'ar_syp' : 'ar_usd';
    }

    public function apRole(string $currency): string
    {
        return strtoupper($currency) === 'SYP' ? 'ap_syp' : 'ap_usd';
    }

    /**
     * Match Arabic expense type label to the best postable sub-account under 521x.
     */
    public function resolveExpenseAccountId(string $shopId, ?int $explicitAccountId, ?string $expenseType): ?int
    {
        if ($explicitAccountId) {
            $exists = Account::withoutGlobalScopes()
                ->where('shop_id', $shopId)
                ->where('id', $explicitAccountId)
                ->where('is_postable', true)
                ->exists();
            if ($exists) {
                return $explicitAccountId;
            }
        }

        $label = mb_strtolower(trim((string) $expenseType));
        $key = 'expenses';

        if (str_contains($label, 'إيجار') || str_contains($label, 'ايجار') || str_contains($label, 'أجار')) {
            $key = 'exp_rent';
        } elseif (str_contains($label, 'كهرب') || str_contains($label, 'ماء') || str_contains($label, 'مياه') || str_contains($label, 'نت') || str_contains($label, 'إنترنت') || str_contains($label, 'اتصال') || str_contains($label, 'فاتورة هاتف')) {
            $key = 'exp_utilities';
        } elseif (str_contains($label, 'نقل') || str_contains($label, 'توصيل') || str_contains($label, 'شحن')) {
            $key = 'exp_delivery';
        } elseif (str_contains($label, 'صيان') || str_contains($label, 'تصليح') || str_contains($label, 'وقود') || str_contains($label, 'مولد')) {
            $key = 'exp_maintenance';
        } elseif (str_contains($label, 'ضياف') || str_contains($label, 'نظاف') || str_contains($label, 'قرطاس') || str_contains($label, 'مكتبي')) {
            $key = 'exp_office';
        } elseif (str_contains($label, 'تسويق') || str_contains($label, 'إعلان') || str_contains($label, 'اعلان') || str_contains($label, 'دعاي')) {
            $key = 'exp_marketing';
        } elseif (str_contains($label, 'إهلاك') || str_contains($label, 'اهلاك')) {
            $key = 'exp_depreciation';
        } elseif (str_contains($label, 'عمول') || str_contains($label, 'بنك') || str_contains($label, 'حوال')) {
            $key = 'bank_fees';
        }

        return $this->accountId($shopId, $key) ?? $this->accountId($shopId, 'expenses');
    }

    /**
     * Build a standardized dual-currency line payload.
     * Base currency is ALWAYS USD (`debit` / `credit`).
     * Secondary currency (SYP) preserves original amount & rate.
     */
    private function lineByRole(
        string $role,
        float $debitUsd,
        float $creditUsd,
        string $description,
        string $currency = 'USD',
        float $rate = 1.0,
        float $foreignDebit = 0.0,
        float $foreignCredit = 0.0,
        ?int $contactId = null,
        ?int $costCenterId = null
    ): array {
        $cur = strtoupper($currency) === 'SYP' ? 'SYP' : 'USD';
        return [
            'role' => $role,
            'account_id' => null,
            'debit' => round(max(0, $debitUsd), 2),
            'credit' => round(max(0, $creditUsd), 2),
            'currency' => $cur,
            'exchange_rate' => $cur === 'SYP' ? max(1.0, $rate) : 1.0,
            'foreign_debit' => round(max(0, $cur === 'SYP' ? $foreignDebit : $debitUsd), 2),
            'foreign_credit' => round(max(0, $cur === 'SYP' ? $foreignCredit : $creditUsd), 2),
            'description' => $description,
            'contact_id' => $contactId,
            'cost_center_id' => $costCenterId,
        ];
    }

    private function lineByAccountId(
        int $accountId,
        float $debitUsd,
        float $creditUsd,
        string $description,
        string $currency = 'USD',
        float $rate = 1.0,
        float $foreignDebit = 0.0,
        float $foreignCredit = 0.0,
        ?int $contactId = null,
        ?int $costCenterId = null
    ): array {
        $cur = strtoupper($currency) === 'SYP' ? 'SYP' : 'USD';
        return [
            'role' => null,
            'account_id' => $accountId,
            'debit' => round(max(0, $debitUsd), 2),
            'credit' => round(max(0, $creditUsd), 2),
            'currency' => $cur,
            'exchange_rate' => $cur === 'SYP' ? max(1.0, $rate) : 1.0,
            'foreign_debit' => round(max(0, $cur === 'SYP' ? $foreignDebit : $debitUsd), 2),
            'foreign_credit' => round(max(0, $cur === 'SYP' ? $foreignCredit : $creditUsd), 2),
            'description' => $description,
            'contact_id' => $contactId,
            'cost_center_id' => $costCenterId,
        ];
    }

    public function postTransaction(Transaction $tx, AccountingService $accounting, ?array $wacMap = null): void
    {
        $shop = $this->ensureShopReady($tx->shop_id);
        if (!$shop) {
            return;
        }

        $this->forgetSource($tx->shop_id, 'transaction', $tx->id);

        $cur = strtoupper((string) ($tx->currency ?: 'USD'));
        $rate = (float) ($tx->exchange_rate > 0 ? $tx->exchange_rate : $this->resolveRate($tx->shop_id));

        $amount = round((float) $tx->total, 2);
        $amountUsd = round(Money::toUsd($amount, $cur, $rate), 2);
        $discount = round((float) ($tx->discount ?? 0), 2);
        $discountUsd = round(Money::toUsd($discount, $cur, $rate), 2);
        $gross = round((float) ($tx->subtotal > 0 ? $tx->subtotal : ($amount + $discount)), 2);
        $grossUsd = round($amountUsd + $discountUsd, 2);

        $paid = round((float) ($tx->paid_amount ?: ($tx->payment_type === 'cash' ? $amount : 0)), 2);
        $paid = min($paid, $amount);
        $paidUsd = round(Money::toUsd($paid, $cur, $rate), 2);
        $remainder = round(max(0, $amount - $paid), 2);
        $remainderUsd = round(max(0, $amountUsd - $paidUsd), 2);

        $lines = [];
        $desc = "فاتورة {$tx->invoice_number}";
        $contactId = $tx->contact_id ? (int) $tx->contact_id : null;
        $ccId = $tx->cost_center_id ? (int) $tx->cost_center_id : null;

        if (in_array($tx->type, ['sale', 'sale_return'], true)) {
            if ($tx->type === 'sale') {
                if ($paidUsd > 0) {
                    $lines[] = $this->lineByRole($this->cashRole($cur), $paidUsd, 0, $desc . ' (نقداً)', $cur, $rate, $paid, 0, $contactId, $ccId);
                }
                if ($remainderUsd > 0) {
                    $lines[] = $this->lineByRole($this->arRole($cur), $remainderUsd, 0, $desc . ' (آجل)', $cur, $rate, $remainder, 0, $contactId, $ccId);
                }
                if ($discountUsd > 0 && $this->accountId($tx->shop_id, 'sales_discount')) {
                    $lines[] = $this->lineByRole('sales_discount', $discountUsd, 0, 'حسم ممنوح - ' . $desc, $cur, $rate, $discount, 0, $contactId, $ccId);
                    $lines[] = $this->lineByRole('sales', 0, $grossUsd, $desc, $cur, $rate, 0, $gross, $contactId, $ccId);
                } else {
                    $lines[] = $this->lineByRole('sales', 0, $amountUsd, $desc, $cur, $rate, 0, $amount, $contactId, $ccId);
                }
            } else {
                $lines[] = $this->lineByRole('sales_returns', $amountUsd, 0, 'مرتجع مبيعات - ' . $desc, $cur, $rate, $amount, 0, $contactId, $ccId);
                if ($paidUsd > 0) {
                    $lines[] = $this->lineByRole($this->cashRole($cur), 0, $paidUsd, $desc, $cur, $rate, 0, $paid, $contactId, $ccId);
                }
                if ($remainderUsd > 0) {
                    $lines[] = $this->lineByRole($this->arRole($cur), 0, $remainderUsd, $desc, $cur, $rate, 0, $remainder, $contactId, $ccId);
                }
            }

            // Perpetual inventory COGS leg (strictly USD Base)
            $cogs = $this->cogsFor($tx, $accounting, $wacMap);
            if ($cogs > 0) {
                if ($tx->type === 'sale') {
                    $lines[] = $this->lineByRole('cogs', $cogs, 0, 'تكلفة البضاعة المباعة - ' . $desc, 'USD', 1.0, $cogs, 0, $contactId, $ccId);
                    $lines[] = $this->lineByRole('inventory', 0, $cogs, 'صرف مخزون - ' . $desc, 'USD', 1.0, 0, $cogs, $contactId, $ccId);
                } else {
                    $lines[] = $this->lineByRole('inventory', $cogs, 0, 'إرجاع مخزون - ' . $desc, 'USD', 1.0, $cogs, 0, $contactId, $ccId);
                    $lines[] = $this->lineByRole('cogs', 0, $cogs, 'عكس تكلفة مبيعات - ' . $desc, 'USD', 1.0, 0, $cogs, $contactId, $ccId);
                }
            }
        }

        if (in_array($tx->type, ['purchase', 'purchase_return'], true)) {
            if ($tx->type === 'purchase') {
                if ($discountUsd > 0 && $this->accountId($tx->shop_id, 'purchase_discount')) {
                    $lines[] = $this->lineByRole('inventory', $grossUsd, 0, 'توريد مخزون - ' . $desc, $cur, $rate, $gross, 0, $contactId, $ccId);
                    $lines[] = $this->lineByRole('purchase_discount', 0, $discountUsd, 'حسم مكتسب - ' . $desc, $cur, $rate, 0, $discount, $contactId, $ccId);
                } else {
                    $lines[] = $this->lineByRole('inventory', $amountUsd, 0, 'توريد مخزون - ' . $desc, $cur, $rate, $amount, 0, $contactId, $ccId);
                }
                if ($paidUsd > 0) {
                    $lines[] = $this->lineByRole($this->cashRole($cur), 0, $paidUsd, $desc . ' (نقداً)', $cur, $rate, 0, $paid, $contactId, $ccId);
                }
                if ($remainderUsd > 0) {
                    $lines[] = $this->lineByRole($this->apRole($cur), 0, $remainderUsd, $desc . ' (آجل)', $cur, $rate, 0, $remainder, $contactId, $ccId);
                }
            } else {
                if ($paidUsd > 0) {
                    $lines[] = $this->lineByRole($this->cashRole($cur), $paidUsd, 0, $desc, $cur, $rate, $paid, 0, $contactId, $ccId);
                }
                if ($remainderUsd > 0) {
                    $lines[] = $this->lineByRole($this->apRole($cur), $remainderUsd, 0, $desc, $cur, $rate, $remainder, 0, $contactId, $ccId);
                }
                $lines[] = $this->lineByRole('inventory', 0, $amountUsd, 'مرتجع مشتريات - ' . $desc, $cur, $rate, 0, $amount, $contactId, $ccId);
            }
        }

        $this->writeEntry($tx->shop_id, $tx->date->format('Y-m-d'), $tx->invoice_number, 'transaction', $tx->id, $desc, $lines);
    }

    public function postVoucher(Voucher $voucher): void
    {
        $shop = $this->ensureShopReady($voucher->shop_id);
        if (!$shop) {
            return;
        }
        $this->forgetSource($voucher->shop_id, 'voucher', $voucher->id);

        if (!$voucher->affects_balance) {
            return;
        }

        $cur = strtoupper((string) ($voucher->currency ?: 'USD'));
        $rate = (float) ($voucher->exchange_rate > 0 ? $voucher->exchange_rate : $this->resolveRate($voucher->shop_id));
        $amount = round((float) $voucher->amount, 2);
        $usd = round(Money::toUsd($amount, $cur, $rate), 2);
        if ($usd <= 0) {
            return;
        }

        $contactId = $voucher->contact_id ? (int) $voucher->contact_id : null;
        $desc = $voucher->description ?: ($voucher->type === 'receipt' ? 'سند قبض ' . $voucher->number : 'سند صرف ' . $voucher->number);

        if ($voucher->type === 'receipt') {
            $creditRole = $contactId ? $this->arRole($cur) : 'other_revenue';
            $lines = [
                $this->lineByRole($this->cashRole($cur), $usd, 0, $desc, $cur, $rate, $amount, 0, $contactId),
                $this->lineByRole($creditRole, 0, $usd, $desc, $cur, $rate, 0, $amount, $contactId),
            ];
        } else {
            $debitRole = $contactId ? $this->apRole($cur) : 'expenses';
            $lines = [
                $this->lineByRole($debitRole, $usd, 0, $desc, $cur, $rate, $amount, 0, $contactId),
                $this->lineByRole($this->cashRole($cur), 0, $usd, $desc, $cur, $rate, 0, $amount, $contactId),
            ];
        }

        $this->writeEntry($voucher->shop_id, $voucher->date->format('Y-m-d'), $voucher->number, 'voucher', $voucher->id, $desc, $lines);
    }

    public function postCashMovement(CashMovement $cm): void
    {
        // Only post direct/manual cashbox movements (domain records post their own entries)
        if ($cm->source_type !== null && $cm->source_type !== '') {
            return;
        }

        $shop = $this->ensureShopReady($cm->shop_id);
        if (!$shop) {
            return;
        }
        $this->forgetSource($cm->shop_id, 'cash_movement', $cm->id);

        $cur = strtoupper((string) ($cm->currency ?: 'USD'));
        $rate = (float) ($cm->exchange_rate > 0 ? $cm->exchange_rate : $this->resolveRate($cm->shop_id));
        $amount = round((float) $cm->amount, 2);
        $usd = round(Money::toUsd($amount, $cur, $rate), 2);
        if ($usd <= 0) {
            return;
        }

        $contactId = $cm->contact_id ? (int) $cm->contact_id : null;
        $doc = $cm->voucher_number ?: ('CASH-' . $cm->id);
        $desc = trim(($cm->category ?: '') . ($cm->notes ? ' — ' . $cm->notes : '')) ?: ('حركة صندوق ' . $doc);
        $cashRole = $this->cashRole($cur);

        if ($cm->type === 'opening') {
            $lines = [
                $this->lineByRole($cashRole, $usd, 0, 'رصيد افتتاحي صندوق - ' . $desc, $cur, $rate, $amount, 0),
                $this->lineByRole('capital', 0, $usd, 'مقابل رصيد افتتاحي - ' . $desc, $cur, $rate, 0, $amount),
            ];
        } elseif ($cm->type === 'receipt') {
            $counterRole = $contactId ? $this->arRole($cur) : 'other_revenue';
            $lines = [
                $this->lineByRole($cashRole, $usd, 0, $desc, $cur, $rate, $amount, 0, $contactId),
                $this->lineByRole($counterRole, 0, $usd, $desc, $cur, $rate, 0, $amount, $contactId),
            ];
        } elseif ($cm->type === 'withdrawal') {
            $lines = [
                $this->lineByRole('withdrawals', $usd, 0, $desc, $cur, $rate, $amount, 0),
                $this->lineByRole($cashRole, 0, $usd, $desc, $cur, $rate, 0, $amount),
            ];
        } elseif ($cm->type === 'salary') {
            $lines = [
                $this->lineByRole('salaries', $usd, 0, $desc, $cur, $rate, $amount, 0),
                $this->lineByRole($cashRole, 0, $usd, $desc, $cur, $rate, 0, $amount),
            ];
        } elseif ($cm->type === 'expense') {
            $expAccId = $this->resolveExpenseAccountId($cm->shop_id, null, $cm->category ?: $cm->notes);
            $lines = [
                $expAccId
                    ? $this->lineByAccountId($expAccId, $usd, 0, $desc, $cur, $rate, $amount, 0)
                    : $this->lineByRole('expenses', $usd, 0, $desc, $cur, $rate, $amount, 0),
                $this->lineByRole($cashRole, 0, $usd, $desc, $cur, $rate, 0, $amount),
            ];
        } else {
            // payment
            $counterRole = $contactId ? $this->apRole($cur) : 'expenses';
            $lines = [
                $this->lineByRole($counterRole, $usd, 0, $desc, $cur, $rate, $amount, 0, $contactId),
                $this->lineByRole($cashRole, 0, $usd, $desc, $cur, $rate, 0, $amount, $contactId),
            ];
        }

        $this->writeEntry($cm->shop_id, $cm->date->format('Y-m-d'), $doc, 'cash_movement', $cm->id, $desc, $lines);
    }

    public function postExpense(Expense $expense): void
    {
        $shop = $this->ensureShopReady($expense->shop_id);
        if (!$shop) {
            return;
        }
        $this->forgetSource($expense->shop_id, 'expense', $expense->id);

        $cur = strtoupper((string) ($expense->currency ?: 'USD'));
        $rate = (float) ($expense->exchange_rate > 0 ? $expense->exchange_rate : $this->resolveRate($expense->shop_id));
        $amount = round((float) $expense->amount, 2);
        $usd = round(Money::toUsd($amount, $cur, $rate), 2);
        if ($usd <= 0) {
            return;
        }

        $doc = $expense->voucher_number ?: 'EXP-' . $expense->id;
        $desc = trim($expense->expense_type . ($expense->description ? ' — ' . $expense->description : ''));
        $expAccId = $this->resolveExpenseAccountId($expense->shop_id, $expense->account_id ? (int) $expense->account_id : null, $expense->expense_type);
        $ccId = $expense->cost_center_id ? (int) $expense->cost_center_id : null;

        $lines = [
            $expAccId
                ? $this->lineByAccountId($expAccId, $usd, 0, $desc, $cur, $rate, $amount, 0, null, $ccId)
                : $this->lineByRole('expenses', $usd, 0, $desc, $cur, $rate, $amount, 0, null, $ccId),
            $this->lineByRole($this->cashRole($cur), 0, $usd, $desc, $cur, $rate, 0, $amount, null, $ccId),
        ];

        $this->writeEntry($expense->shop_id, $expense->date->format('Y-m-d'), $doc, 'expense', $expense->id, $desc, $lines);
    }

    public function postShippingExpense(ShippingExpense $shipping): void
    {
        $shop = $this->ensureShopReady($shipping->shop_id);
        if (!$shop) {
            return;
        }
        $this->forgetSource($shipping->shop_id, 'shipping_expense', $shipping->id);

        $cur = strtoupper((string) ($shipping->currency ?: 'USD'));
        $rate = (float) ($shipping->exchange_rate > 0 ? $shipping->exchange_rate : $this->resolveRate($shipping->shop_id));
        $amount = round((float) $shipping->amount, 2);
        $usd = round(Money::toUsd($amount, $cur, $rate), 2);
        if ($usd <= 0) {
            return;
        }

        $doc = $shipping->voucher_number ?: 'SHP-' . $shipping->id;
        $desc = 'أجور شحن مشتريات' . ($shipping->transaction ? ' - فاتورة ' . $shipping->transaction->invoice_number : '');
        // Linked shipping is capitalized into Inventory (1141); unlinked shipping goes to Freight-In (5113)
        $debitRole = $shipping->transaction_id ? 'inventory' : 'shipping_in';

        $lines = [
            $this->lineByRole($debitRole, $usd, 0, $desc, $cur, $rate, $amount, 0),
            $this->lineByRole($this->cashRole($cur), 0, $usd, $desc, $cur, $rate, 0, $amount),
        ];

        $this->writeEntry($shipping->shop_id, $shipping->date->format('Y-m-d'), $doc, 'shipping_expense', $shipping->id, $desc, $lines);
    }

    public function postSalary(SalaryRecord $salary): void
    {
        $shop = $this->ensureShopReady($salary->shop_id);
        if (!$shop) {
            return;
        }
        $this->forgetSource($salary->shop_id, 'salary', $salary->id);

        $cur = strtoupper((string) ($salary->currency ?: 'USD'));
        $rate = (float) ($salary->exchange_rate > 0 ? $salary->exchange_rate : $this->resolveRate($salary->shop_id));
        $amount = round((float) $salary->net, 2);
        $usd = round(Money::toUsd($amount, $cur, $rate), 2);
        if ($usd <= 0) {
            return;
        }

        $doc = $salary->voucher_number ?: 'SAL-' . $salary->id;
        $empName = $salary->employee?->name ? (' - ' . $salary->employee->name) : '';
        $desc = 'راتب شهر ' . $salary->month . $empName;

        $lines = [
            $this->lineByRole('salaries', $usd, 0, $desc, $cur, $rate, $amount, 0),
            $this->lineByRole($this->cashRole($cur), 0, $usd, $desc, $cur, $rate, 0, $amount),
        ];

        $this->writeEntry($salary->shop_id, $salary->date->format('Y-m-d'), $doc, 'salary', $salary->id, $desc, $lines);
    }

    public function postEmployeeMovement(EmployeeMovement $move): void
    {
        $shop = $this->ensureShopReady($move->shop_id);
        if (!$shop) {
            return;
        }
        $this->forgetSource($move->shop_id, 'employee_movement', $move->id);

        $cur = strtoupper((string) ($move->currency ?: 'USD'));
        $rate = (float) ($move->exchange_rate > 0 ? $move->exchange_rate : $this->resolveRate($move->shop_id));
        $amount = round((float) $move->amount, 2);
        $usd = round(Money::toUsd($amount, $cur, $rate), 2);
        if ($usd <= 0) {
            return;
        }

        $doc = $move->voucher_number ?: 'EMP-' . $move->id;
        $empName = $move->employee?->name ? (' - ' . $move->employee->name) : '';
        $desc = $move->typeLabel() . $empName;
        $debitRole = $move->type === 'advance' ? 'employee_advances' : 'salaries';

        $lines = [
            $this->lineByRole($debitRole, $usd, 0, $desc, $cur, $rate, $amount, 0),
            $this->lineByRole($this->cashRole($cur), 0, $usd, $desc, $cur, $rate, 0, $amount),
        ];

        $this->writeEntry($move->shop_id, $move->date->format('Y-m-d'), $doc, 'employee_movement', $move->id, $desc, $lines);
    }

    public function postWithdrawal(PartnerWithdrawal $withdrawal): void
    {
        $shop = $this->ensureShopReady($withdrawal->shop_id);
        if (!$shop) {
            return;
        }
        $this->forgetSource($withdrawal->shop_id, 'withdrawal', $withdrawal->id);

        $cur = strtoupper((string) ($withdrawal->currency ?: 'USD'));
        $rate = (float) ($withdrawal->exchange_rate > 0 ? $withdrawal->exchange_rate : $this->resolveRate($withdrawal->shop_id));
        $amount = round((float) $withdrawal->amount, 2);
        $usd = round(Money::toUsd($amount, $cur, $rate), 2);
        if ($usd <= 0) {
            return;
        }

        $doc = $withdrawal->voucher_number ?: 'WD-' . $withdrawal->id;
        $partnerName = $withdrawal->partner?->name ? (' - ' . $withdrawal->partner->name) : '';
        $desc = 'مسحوبات شريك' . $partnerName . ($withdrawal->description ? ' (' . $withdrawal->description . ')' : '');

        $lines = [
            $this->lineByRole('withdrawals', $usd, 0, $desc, $cur, $rate, $amount, 0),
            $this->lineByRole($this->cashRole($cur), 0, $usd, $desc, $cur, $rate, 0, $amount),
        ];

        $this->writeEntry($withdrawal->shop_id, $withdrawal->date->format('Y-m-d'), $doc, 'withdrawal', $withdrawal->id, $desc, $lines);
    }

    public function postCurrencyExchange(CurrencyExchange $exchange): void
    {
        $shop = $this->ensureShopReady($exchange->shop_id);
        if (!$shop) {
            return;
        }
        $this->forgetSource($exchange->shop_id, 'currency_exchange', $exchange->id);

        $usd = round((float) $exchange->amount_usd, 2);
        if ($usd <= 0) {
            return;
        }

        $doc = $exchange->reference_number ?: 'EX-' . $exchange->id;
        $desc = $exchange->directionLabel();
        $baseRate = $this->resolveRate($exchange->shop_id);
        $exRate = (float) ($exchange->exchange_rate > 0 ? $exchange->exchange_rate : $baseRate);

        $lines = [];
        if ($exchange->direction === 'syp_to_usd') {
            $sypOut = round((float) $exchange->from_amount, 2);
            $sypBookUsd = round($sypOut / $baseRate, 2);
            $fxDiff = round($usd - $sypBookUsd, 2);

            $lines[] = $this->lineByRole('cash_usd', $usd, 0, $desc, 'USD', 1.0, $usd, 0);
            $lines[] = $this->lineByRole('cash_syp', 0, $sypBookUsd, $desc, 'SYP', $exRate, 0, $sypOut);

            if ($fxDiff >= 0.01) {
                $lines[] = $this->lineByRole('fx_gain', 0, $fxDiff, 'ربح فرق صرف عملة محقق - ' . $doc, 'USD', 1.0, 0, $fxDiff);
            } elseif ($fxDiff <= -0.01) {
                $loss = abs($fxDiff);
                $lines[] = $this->lineByRole('fx_loss', $loss, 0, 'خسارة فرق صرف عملة محقق - ' . $doc, 'USD', 1.0, $loss, 0);
            }
        } else {
            $sypIn = round((float) $exchange->to_amount, 2);
            $sypBookUsd = round($sypIn / $baseRate, 2);
            $fxDiff = round($sypBookUsd - $usd, 2);

            $lines[] = $this->lineByRole('cash_syp', $sypBookUsd, 0, $desc, 'SYP', $exRate, $sypIn, 0);
            $lines[] = $this->lineByRole('cash_usd', 0, $usd, $desc, 'USD', 1.0, 0, $usd);

            if ($fxDiff >= 0.01) {
                $lines[] = $this->lineByRole('fx_gain', 0, $fxDiff, 'ربح فرق صرف عملة محقق - ' . $doc, 'USD', 1.0, 0, $fxDiff);
            } elseif ($fxDiff <= -0.01) {
                $loss = abs($fxDiff);
                $lines[] = $this->lineByRole('fx_loss', $loss, 0, 'خسارة فرق صرف عملة محقق - ' . $doc, 'USD', 1.0, $loss, 0);
            }
        }

        $this->writeEntry($exchange->shop_id, $exchange->date->format('Y-m-d'), $doc, 'currency_exchange', $exchange->id, $desc, $lines);
    }

    public function postSimple(
        string $shopId,
        string $date,
        string $doc,
        string $sourceType,
        int $sourceId,
        string $description,
        string $debitRole,
        string $creditRole,
        float $amount,
        string $currency,
        float $rate
    ): void {
        $shop = $this->ensureShopReady($shopId);
        if (!$shop) {
            return;
        }
        $cur = strtoupper($currency) === 'SYP' ? 'SYP' : 'USD';
        $safeRate = $rate > 0 ? $rate : $this->resolveRate($shopId);
        $usd = round(Money::toUsd($amount, $cur, $safeRate), 2);
        if ($usd <= 0) {
            return;
        }

        $this->forgetSource($shopId, $sourceType, $sourceId);
        $this->writeEntry($shopId, $date, $doc, $sourceType, $sourceId, $description, [
            $this->lineByRole($debitRole, $usd, 0, $description, $cur, $safeRate, $amount, 0),
            $this->lineByRole($creditRole, 0, $usd, $description, $cur, $safeRate, 0, $amount),
        ]);
    }

    public function forgetSource(string $shopId, string $sourceType, int $sourceId): void
    {
        JournalEntry::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->each(fn (JournalEntry $e) => $e->delete());
    }

    /**
     * Rebuild all automatic double-entry journal entries from operational documents
     * strictly in Base USD while preserving manual entries and FX revaluations.
     */
    public function rebuildAllEntries(string $shopId, AccountingService $accounting): array
    {
        $shop = $this->ensureShopReady($shopId);
        if (!$shop) {
            return ['entries' => 0, 'balanced' => true];
        }

        $rate = $this->resolveRate($shopId);

        // Preserve manual entries and manual FX revaluations; clear all system-synced entries
        JournalEntry::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->whereNotIn('source_type', ['manual', 'fx_revaluation'])
            ->each(fn (JournalEntry $e) => $e->delete());

        // Precompute item WAC map once for fast, consistent COGS posting
        $wacMap = [];
        foreach ($accounting->getInventoryValuation($shopId) as $sr) {
            $wacMap[$sr['item']->id] = (float) $sr['wac_usd'];
        }

        // 1. Sync Opening Inventory & Contact Opening Balances into a balanced opening_sync entry
        $openingLines = [];
        $openingInvUsd = 0.0;
        foreach (Item::withoutGlobalScopes()->where('shop_id', $shopId)->get() as $item) {
            $openingInvUsd += round((float) $item->opening_qty * (float) $item->opening_cost_usd, 2);
        }
        $openingInvUsd = round($openingInvUsd, 2);
        if ($openingInvUsd > 0) {
            $openingLines[] = $this->lineByRole('inventory', $openingInvUsd, 0, 'بضاعة أول المدة (أرصدة افتتاحية للمخزون)', 'USD', 1.0, $openingInvUsd, 0);
            $openingLines[] = $this->lineByRole('capital', 0, $openingInvUsd, 'مقابل مخزون أول المدة', 'USD', 1.0, 0, $openingInvUsd);
        }

        foreach (Contact::withoutGlobalScopes()->where('shop_id', $shopId)->get() as $contact) {
            $opUsd = round((float) ($contact->opening_usd ?? 0), 2);
            $opSyp = round((float) ($contact->opening_syp ?? 0), 2);
            $opSypUsd = round(Money::toUsd(abs($opSyp), 'SYP', $rate), 2);

            if ($contact->type === 'customer') {
                if (abs($opUsd) >= 0.01) {
                    if ($opUsd > 0) {
                        $openingLines[] = $this->lineByRole('ar_usd', $opUsd, 0, 'رصيد افتتاحي عميل - ' . $contact->name, 'USD', 1.0, $opUsd, 0, $contact->id);
                        $openingLines[] = $this->lineByRole('capital', 0, $opUsd, 'مقابل رصيد افتتاحي عميل - ' . $contact->name, 'USD', 1.0, 0, $opUsd, $contact->id);
                    } else {
                        $v = abs($opUsd);
                        $openingLines[] = $this->lineByRole('capital', $v, 0, 'مقابل رصيد افتتاحي دائن عميل - ' . $contact->name, 'USD', 1.0, $v, 0, $contact->id);
                        $openingLines[] = $this->lineByRole('ar_usd', 0, $v, 'رصيد افتتاحي عميل - ' . $contact->name, 'USD', 1.0, 0, $v, $contact->id);
                    }
                }
                if ($opSypUsd >= 0.01) {
                    if ($opSyp > 0) {
                        $openingLines[] = $this->lineByRole('ar_syp', $opSypUsd, 0, 'رصيد افتتاحي عميل (ل.س) - ' . $contact->name, 'SYP', $rate, abs($opSyp), 0, $contact->id);
                        $openingLines[] = $this->lineByRole('capital', 0, $opSypUsd, 'مقابل رصيد افتتاحي عميل - ' . $contact->name, 'SYP', $rate, 0, abs($opSyp), $contact->id);
                    } else {
                        $openingLines[] = $this->lineByRole('capital', $opSypUsd, 0, 'مقابل رصيد افتتاحي عميل - ' . $contact->name, 'SYP', $rate, abs($opSyp), 0, $contact->id);
                        $openingLines[] = $this->lineByRole('ar_syp', 0, $opSypUsd, 'رصيد افتتاحي عميل (ل.س) - ' . $contact->name, 'SYP', $rate, 0, abs($opSyp), $contact->id);
                    }
                }
            } else {
                if (abs($opUsd) >= 0.01) {
                    if ($opUsd > 0) {
                        $openingLines[] = $this->lineByRole('capital', $opUsd, 0, 'مقابل رصيد افتتاحي مورد - ' . $contact->name, 'USD', 1.0, $opUsd, 0, $contact->id);
                        $openingLines[] = $this->lineByRole('ap_usd', 0, $opUsd, 'رصيد افتتاحي مورد - ' . $contact->name, 'USD', 1.0, 0, $opUsd, $contact->id);
                    } else {
                        $v = abs($opUsd);
                        $openingLines[] = $this->lineByRole('ap_usd', $v, 0, 'رصيد افتتاحي مورد - ' . $contact->name, 'USD', 1.0, $v, 0, $contact->id);
                        $openingLines[] = $this->lineByRole('capital', 0, $v, 'مقابل رصيد افتتاحي مورد - ' . $contact->name, 'USD', 1.0, 0, $v, $contact->id);
                    }
                }
                if ($opSypUsd >= 0.01) {
                    if ($opSyp > 0) {
                        $openingLines[] = $this->lineByRole('capital', $opSypUsd, 0, 'مقابل رصيد افتتاحي مورد - ' . $contact->name, 'SYP', $rate, abs($opSyp), 0, $contact->id);
                        $openingLines[] = $this->lineByRole('ap_syp', 0, $opSypUsd, 'رصيد افتتاحي مورد (ل.س) - ' . $contact->name, 'SYP', $rate, 0, abs($opSyp), $contact->id);
                    } else {
                        $openingLines[] = $this->lineByRole('ap_syp', $opSypUsd, 0, 'رصيد افتتاحي مورد (ل.س) - ' . $contact->name, 'SYP', $rate, abs($opSyp), 0, $contact->id);
                        $openingLines[] = $this->lineByRole('capital', 0, $opSypUsd, 'مقابل رصيد افتتاحي مورد - ' . $contact->name, 'SYP', $rate, 0, abs($opSyp), $contact->id);
                    }
                }
            }
        }

        if (!empty($openingLines)) {
            $firstDate = Transaction::withoutGlobalScopes()->where('shop_id', $shopId)->min('date') ?: date('Y-m-d');
            $this->writeEntry($shopId, (string) $firstDate, 'OPEN-SYNC', 'opening_sync', 1, 'قيد الأرصدة الافتتاحية (المخزون والذمم)', $openingLines);
        }

        // 2. Repost all operational documents in order
        $count = !empty($openingLines) ? 1 : 0;

        foreach (Transaction::withoutGlobalScopes()->with('lines')->where('shop_id', $shopId)->orderBy('date')->orderBy('id')->get() as $tx) {
            $this->postTransaction($tx, $accounting, $wacMap);
            $count++;
        }

        foreach (Voucher::withoutGlobalScopes()->where('shop_id', $shopId)->where('affects_balance', true)->orderBy('date')->orderBy('id')->get() as $v) {
            $this->postVoucher($v);
            $count++;
        }

        foreach (CashMovement::withoutGlobalScopes()->where('shop_id', $shopId)->where(function ($q) {
            $q->whereNull('source_type')->orWhere('source_type', '');
        })->orderBy('date')->orderBy('id')->get() as $cm) {
            $this->postCashMovement($cm);
            $count++;
        }

        foreach (Expense::withoutGlobalScopes()->where('shop_id', $shopId)->orderBy('date')->orderBy('id')->get() as $exp) {
            $this->postExpense($exp);
            $count++;
        }

        foreach (ShippingExpense::withoutGlobalScopes()->with('transaction')->where('shop_id', $shopId)->orderBy('date')->orderBy('id')->get() as $shp) {
            $this->postShippingExpense($shp);
            $count++;
        }

        foreach (SalaryRecord::withoutGlobalScopes()->with('employee')->where('shop_id', $shopId)->orderBy('date')->orderBy('id')->get() as $sal) {
            $this->postSalary($sal);
            $count++;
        }

        foreach (EmployeeMovement::withoutGlobalScopes()->with('employee')->where('shop_id', $shopId)->orderBy('date')->orderBy('id')->get() as $em) {
            $this->postEmployeeMovement($em);
            $count++;
        }

        foreach (PartnerWithdrawal::withoutGlobalScopes()->with('partner')->where('shop_id', $shopId)->orderBy('date')->orderBy('id')->get() as $wd) {
            $this->postWithdrawal($wd);
            $count++;
        }

        foreach (CurrencyExchange::withoutGlobalScopes()->where('shop_id', $shopId)->orderBy('date')->orderBy('id')->get() as $ex) {
            $this->postCurrencyExchange($ex);
            $count++;
        }

        $trial = $this->trialBalance($shopId);

        return [
            'entries' => $count,
            'balanced' => $trial['balanced'],
            'difference' => $trial['difference'],
        ];
    }

    /**
     * Ensure existing shops with operational data get automatically synced to the GL once
     * before viewing GL-driven reports, preventing empty screens right after upgrade.
     */
    public function ensureSeededFromOperations(string $shopId, AccountingService $accounting): void
    {
        $this->ensureShopReady($shopId);

        $hasEntries = JournalEntry::withoutGlobalScopes()->where('shop_id', $shopId)->exists();
        if ($hasEntries) {
            return;
        }

        $hasOps = Transaction::withoutGlobalScopes()->where('shop_id', $shopId)->exists()
            || CashMovement::withoutGlobalScopes()->where('shop_id', $shopId)->exists()
            || Expense::withoutGlobalScopes()->where('shop_id', $shopId)->exists()
            || SalaryRecord::withoutGlobalScopes()->where('shop_id', $shopId)->exists()
            || PartnerWithdrawal::withoutGlobalScopes()->where('shop_id', $shopId)->exists();

        if ($hasOps) {
            $this->rebuildAllEntries($shopId, $accounting);
        }
    }

    /**
     * Preview unrealized FX revaluation on SYP monetary GL accounts (1112, 1132, 2112).
     */
    public function getGlFxPreview(string $shopId, float $currentRate): array
    {
        $this->ensureShopReady($shopId);
        $rate = $currentRate > 0 ? $currentRate : $this->resolveRate($shopId);

        $monitored = [
            ['role' => 'cash_syp', 'label' => 'الصندوق الثانوي (ليرة سورية SYP)', 'is_liability' => false],
            ['role' => 'ar_syp', 'label' => 'ذمم العملاء (ليرة سورية SYP)', 'is_liability' => false],
            ['role' => 'ap_syp', 'label' => 'ذمم الموردين (ليرة سورية SYP)', 'is_liability' => true],
        ];

        $items = [];
        $totalDiffUsd = 0.0;

        foreach ($monitored as $cfg) {
            $accId = $this->accountId($shopId, $cfg['role']);
            if (!$accId) {
                continue;
            }
            $account = Account::withoutGlobalScopes()->find($accId);
            if (!$account) {
                continue;
            }

            $lines = JournalLine::withoutGlobalScopes()
                ->where('shop_id', $shopId)
                ->where('account_id', $accId)
                ->get();

            $openingUsd = (float) $account->opening_debit - (float) $account->opening_credit;
            $bookUsdSigned = $openingUsd + (float) $lines->sum('debit') - (float) $lines->sum('credit');
            $sypLines = $lines->where('currency', 'SYP');
            $sypSigned = (float) $sypLines->sum('foreign_debit') - (float) $sypLines->sum('foreign_credit');

            if ($cfg['is_liability']) {
                // Liability: normal credit balance
                $bookUsd = round(-$bookUsdSigned, 2);
                $sypBal = round(-$sypSigned, 2);
                $currentUsd = round($sypBal / $rate, 2);
                // If liability USD value shrinks, that is an FX GAIN (+); if it grows, FX LOSS (-)
                $fxDiff = round($bookUsd - $currentUsd, 2);
            } else {
                // Asset: normal debit balance
                $bookUsd = round($bookUsdSigned, 2);
                $sypBal = round($sypSigned, 2);
                $currentUsd = round($sypBal / $rate, 2);
                // If asset USD value grows, FX GAIN (+); if it shrinks, FX LOSS (-)
                $fxDiff = round($currentUsd - $bookUsd, 2);
            }

            if (abs($sypBal) < 1 && abs($bookUsd) < 0.01) {
                continue;
            }

            $items[] = [
                'role' => $cfg['role'],
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'is_liability' => $cfg['is_liability'],
                'balance_syp' => $sypBal,
                'book_usd' => $bookUsd,
                'current_usd' => $currentUsd,
                'fx_diff_usd' => $fxDiff,
            ];

            $totalDiffUsd += $fxDiff;
        }

        return [
            'current_rate' => $rate,
            'items' => $items,
            'net_adjustment_usd' => round($totalDiffUsd, 2),
            'needs_revaluation' => abs($totalDiffUsd) >= 0.01,
        ];
    }

    /**
     * Post a balanced IAS 21 FX revaluation journal entry adjusting SYP monetary GL accounts
     * to the shop's current USD/SYP exchange rate (foreign SYP balance stays untouched).
     */
    public function postFxRevaluation(string $shopId, float $currentRate, ?string $date = null): array
    {
        $preview = $this->getGlFxPreview($shopId, $currentRate);
        if (!$preview['needs_revaluation']) {
            return ['posted' => false, 'net_adjustment_usd' => 0.0];
        }

        $date = $date ?: date('Y-m-d');
        $doc = 'FX-REV-' . str_replace('-', '', $date);
        $rate = $preview['current_rate'];
        $lines = [];

        foreach ($preview['items'] as $item) {
            $diff = round((float) $item['fx_diff_usd'], 2);
            if (abs($diff) < 0.01) {
                continue;
            }

            $label = 'إعادة تقييم ' . $item['name'] . ' على سعر ' . number_format($rate);
            if ($item['is_liability']) {
                // Liability gain (+diff) => Dr AP_SYP (reduces liability USD), Cr FX Gain
                // Liability loss (-diff) => Dr FX Loss, Cr AP_SYP (increases liability USD)
                if ($diff > 0) {
                    $lines[] = $this->lineByAccountId($item['account_id'], $diff, 0, $label, 'USD', 1.0, 0, 0);
                    $lines[] = $this->lineByRole('fx_gain', 0, $diff, 'ربح إعادة تقييم - ' . $item['name'], 'USD', 1.0, 0, $diff);
                } else {
                    $loss = abs($diff);
                    $lines[] = $this->lineByRole('fx_loss', $loss, 0, 'خسارة إعادة تقييم - ' . $item['name'], 'USD', 1.0, $loss, 0);
                    $lines[] = $this->lineByAccountId($item['account_id'], 0, $loss, $label, 'USD', 1.0, 0, 0);
                }
            } else {
                // Asset gain (+diff) => Dr Asset_SYP, Cr FX Gain
                // Asset loss (-diff) => Dr FX Loss, Cr Asset_SYP
                if ($diff > 0) {
                    $lines[] = $this->lineByAccountId($item['account_id'], $diff, 0, $label, 'USD', 1.0, 0, 0);
                    $lines[] = $this->lineByRole('fx_gain', 0, $diff, 'ربح إعادة تقييم - ' . $item['name'], 'USD', 1.0, 0, $diff);
                } else {
                    $loss = abs($diff);
                    $lines[] = $this->lineByRole('fx_loss', $loss, 0, 'خسارة إعادة تقييم - ' . $item['name'], 'USD', 1.0, $loss, 0);
                    $lines[] = $this->lineByAccountId($item['account_id'], 0, $loss, $label, 'USD', 1.0, 0, 0);
                }
            }
        }

        if (empty($lines)) {
            return ['posted' => false, 'net_adjustment_usd' => 0.0];
        }

        $this->writeEntry(
            $shopId,
            $date,
            $doc,
            'fx_revaluation',
            (int) date('YmdHis'),
            'قيد إعادة تقييم أرصدة العملة الثانوية (ل.س) إلى الدولار على سعر ' . number_format($rate),
            $lines
        );

        return [
            'posted' => true,
            'document' => $doc,
            'net_adjustment_usd' => $preview['net_adjustment_usd'],
        ];
    }

    private function cogsFor(Transaction $tx, AccountingService $accounting, ?array $wacMap = null): float
    {
        if ($wacMap === null) {
            $wacMap = [];
            foreach ($accounting->getInventoryValuation($tx->shop_id) as $sr) {
                $wacMap[$sr['item']->id] = (float) $sr['wac_usd'];
            }
        }

        if ($tx->relationLoaded('lines') ? $tx->lines->count() > 0 : $tx->lines()->exists()) {
            $cogs = 0.0;
            foreach ($tx->lines as $line) {
                $cogs += (float) $line->qty * ($wacMap[$line->item_id] ?? 0.0);
            }
            return round($cogs, 2);
        }

        return round((float) $tx->qty * ($wacMap[$tx->item_id] ?? 0.0), 2);
    }

    /**
     * Core double-entry writer. Enforces postable accounts and strict Base-USD balance.
     */
    public function writeEntry(
        string $shopId,
        string $date,
        string $doc,
        string $sourceType,
        ?int $sourceId,
        string $description,
        array $rawLines
    ): ?JournalEntry {
        $resolved = [];
        $sumDebit = 0.0;
        $sumCredit = 0.0;

        foreach ($rawLines as $raw) {
            $debit = round((float) ($raw['debit'] ?? 0), 2);
            $credit = round((float) ($raw['credit'] ?? 0), 2);
            if ($debit <= 0 && $credit <= 0) {
                continue;
            }

            $accountId = !empty($raw['account_id'])
                ? (int) $raw['account_id']
                : (!empty($raw['role']) ? $this->accountId($shopId, (string) $raw['role']) : null);

            if (!$accountId) {
                return null;
            }

            $resolved[] = [
                'account_id' => $accountId,
                'contact_id' => $raw['contact_id'] ?? null,
                'cost_center_id' => $raw['cost_center_id'] ?? null,
                'description' => $raw['description'] ?? $description,
                'debit' => $debit,
                'credit' => $credit,
                'currency' => $raw['currency'] ?? 'USD',
                'exchange_rate' => $raw['exchange_rate'] ?? 1.0,
                'foreign_debit' => round((float) ($raw['foreign_debit'] ?? $debit), 2),
                'foreign_credit' => round((float) ($raw['foreign_credit'] ?? $credit), 2),
                'amount_usd' => max($debit, $credit),
            ];

            $sumDebit += $debit;
            $sumCredit += $credit;
        }

        if (count($resolved) < 2) {
            return null;
        }

        // Absorb tiny multi-currency conversion penny rounding (<= $0.05) on the largest balancing line
        $diff = round($sumDebit - $sumCredit, 2);
        if (abs($diff) > 0 && abs($diff) <= 0.05) {
            $targetIdx = null;
            $largest = -1.0;
            foreach ($resolved as $idx => $line) {
                if ($diff > 0 && $line['credit'] > $largest) {
                    $largest = $line['credit'];
                    $targetIdx = $idx;
                } elseif ($diff < 0 && $line['debit'] > $largest) {
                    $largest = $line['debit'];
                    $targetIdx = $idx;
                }
            }
            if ($targetIdx !== null) {
                if ($diff > 0) {
                    $resolved[$targetIdx]['credit'] = round($resolved[$targetIdx]['credit'] + $diff, 2);
                    $resolved[$targetIdx]['amount_usd'] = $resolved[$targetIdx]['credit'];
                } else {
                    $resolved[$targetIdx]['debit'] = round($resolved[$targetIdx]['debit'] + abs($diff), 2);
                    $resolved[$targetIdx]['amount_usd'] = $resolved[$targetIdx]['debit'];
                }
                $diff = 0.0;
            }
        }

        if (abs($diff) >= 0.01) {
            return null;
        }

        return DB::transaction(function () use ($shopId, $date, $doc, $sourceType, $sourceId, $description, $resolved) {
            $entry = JournalEntry::withoutGlobalScopes()->create([
                'shop_id' => $shopId,
                'date' => $date,
                'document_number' => $doc,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
            ]);

            foreach ($resolved as $line) {
                JournalLine::withoutGlobalScopes()->create([
                    'shop_id' => $shopId,
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'contact_id' => $line['contact_id'],
                    'cost_center_id' => $line['cost_center_id'],
                    'description' => $line['description'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'currency' => $line['currency'],
                    'exchange_rate' => $line['exchange_rate'],
                    'foreign_debit' => $line['foreign_debit'],
                    'foreign_credit' => $line['foreign_credit'],
                    'amount_usd' => $line['amount_usd'],
                ]);
            }

            return $entry;
        });
    }

    /**
     * Collect all descendant account IDs (inclusive) for filtering ledger by a parent account.
     */
    public function descendantIds(string $shopId, int $accountId): array
    {
        $all = Account::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->get(['id', 'parent_id']);

        $byParent = [];
        foreach ($all as $a) {
            $byParent[$a->parent_id ?? 0][] = (int) $a->id;
        }

        $result = [$accountId];
        $queue = [$accountId];
        while (!empty($queue)) {
            $curr = array_shift($queue);
            foreach ($byParent[$curr] ?? [] as $childId) {
                if (!in_array($childId, $result, true)) {
                    $result[] = $childId;
                    $queue[] = $childId;
                }
            }
        }

        return $result;
    }

    /**
     * Compute direct + rolled-up USD & SYP balances for every account in the hierarchical Chart of Accounts.
     */
    public function chartTreeWithBalances(string $shopId, ?string $from = null, ?string $to = null): array
    {
        $this->ensureShopReady($shopId);

        $accounts = Account::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->orderBy('code')
            ->get();

        $lineQuery = JournalLine::withoutGlobalScopes()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.shop_id', $shopId);

        if ($from) {
            $lineQuery->whereDate('journal_entries.date', '>=', $from);
        }
        if ($to) {
            $lineQuery->whereDate('journal_entries.date', '<=', $to);
        }

        $agg = $lineQuery
            ->selectRaw('journal_lines.account_id')
            ->selectRaw('SUM(journal_lines.debit) as sum_debit')
            ->selectRaw('SUM(journal_lines.credit) as sum_credit')
            ->selectRaw("SUM(CASE WHEN journal_lines.currency = 'SYP' THEN journal_lines.foreign_debit ELSE 0 END) as syp_debit")
            ->selectRaw("SUM(CASE WHEN journal_lines.currency = 'SYP' THEN journal_lines.foreign_credit ELSE 0 END) as syp_credit")
            ->selectRaw('COUNT(*) as lines_count')
            ->groupBy('journal_lines.account_id')
            ->get()
            ->keyBy('account_id');

        $nodes = [];
        $childrenByParent = [];

        foreach ($accounts as $acc) {
            $row = $agg->get($acc->id);
            $openingSigned = $from ? 0.0 : ((float) $acc->opening_debit - (float) $acc->opening_credit);
            $debit = round((float) ($row->sum_debit ?? 0), 2);
            $credit = round((float) ($row->sum_credit ?? 0), 2);
            $signedUsd = round($openingSigned + $debit - $credit, 2);
            $signedSyp = round((float) ($row->syp_debit ?? 0) - (float) ($row->syp_credit ?? 0), 2);

            $nodes[$acc->id] = [
                'account' => $acc,
                'direct_debit_usd' => $debit,
                'direct_credit_usd' => $credit,
                'direct_signed_usd' => $signedUsd,
                'direct_signed_syp' => $signedSyp,
                'lines_count' => (int) ($row->lines_count ?? 0),
                'rollup_debit_usd' => $debit,
                'rollup_credit_usd' => $credit,
                'rollup_signed_usd' => $signedUsd,
                'rollup_display_usd' => $acc->isDebitNormal() ? $signedUsd : -$signedUsd,
                'rollup_syp' => $acc->isDebitNormal() ? $signedSyp : -$signedSyp,
                'has_children' => false,
            ];

            $pid = $acc->parent_id ?? 0;
            $childrenByParent[$pid][] = $acc->id;
        }

        // Post-order DFS to roll up child balances into every parent node cleanly
        $ordered = [];
        $walk = function (int $id, int $depth) use (&$walk, &$nodes, $childrenByParent, &$ordered): array {
            $childIds = $childrenByParent[$id] ?? [];
            $nodes[$id]['has_children'] = count($childIds) > 0;
            $nodes[$id]['depth'] = $depth;

            $pos = count($ordered);
            $ordered[] = null; // placeholder to preserve pre-order tree display order

            $totDebit = $nodes[$id]['direct_debit_usd'];
            $totCredit = $nodes[$id]['direct_credit_usd'];
            $totSigned = $nodes[$id]['direct_signed_usd'];
            $totSyp = $nodes[$id]['direct_signed_syp'];

            foreach ($childIds as $cid) {
                [$cd, $cc, $cs, $csyp] = $walk($cid, $depth + 1);
                $totDebit += $cd;
                $totCredit += $cc;
                $totSigned += $cs;
                $totSyp += $csyp;
            }

            $acc = $nodes[$id]['account'];
            $nodes[$id]['rollup_debit_usd'] = round($totDebit, 2);
            $nodes[$id]['rollup_credit_usd'] = round($totCredit, 2);
            $nodes[$id]['rollup_signed_usd'] = round($totSigned, 2);
            $nodes[$id]['rollup_display_usd'] = round($acc->isDebitNormal() ? $totSigned : -$totSigned, 2);
            $nodes[$id]['rollup_syp'] = round($acc->isDebitNormal() ? $totSyp : -$totSyp, 2);

            $ordered[$pos] = $nodes[$id];

            return [$totDebit, $totCredit, $totSigned, $totSyp];
        };

        foreach ($childrenByParent[0] ?? [] as $rootId) {
            $walk($rootId, 0);
        }

        // Summary KPIs by root type
        $summary = [
            'assets' => 0.0,
            'liabilities' => 0.0,
            'equity' => 0.0,
            'revenues' => 0.0,
            'expenses' => 0.0,
        ];
        foreach ($ordered as $n) {
            if ($n['depth'] === 0) {
                $t = $n['account']->type;
                if ($t === 'asset') $summary['assets'] += $n['rollup_display_usd'];
                elseif ($t === 'liability') $summary['liabilities'] += $n['rollup_display_usd'];
                elseif ($t === 'equity') $summary['equity'] += $n['rollup_display_usd'];
                elseif ($t === 'revenue') $summary['revenues'] += $n['rollup_display_usd'];
                elseif ($t === 'expense') $summary['expenses'] += $n['rollup_display_usd'];
            }
        }

        return [
            'rows' => $ordered,
            'by_id' => $nodes,
            'summary' => $summary,
        ];
    }

    /**
     * General Ledger strictly in Base USD with Secondary SYP metadata and date range carry-forward.
     */
    public function ledger(string $shopId, ?int $accountId = null, ?string $from = null, ?string $to = null): array
    {
        $this->ensureShopReady($shopId);

        $accountIds = $accountId ? $this->descendantIds($shopId, $accountId) : null;

        $query = JournalLine::withoutGlobalScopes()
            ->with(['account', 'entry', 'contact'])
            ->where('shop_id', $shopId)
            ->when($accountIds, fn ($q) => $q->whereIn('account_id', $accountIds))
            ->get()
            ->filter(fn ($line) => $line->account && $line->entry)
            ->sortBy(fn ($line) => $line->entry->date->format('Y-m-d') . '-' . str_pad((string) $line->journal_entry_id, 10, '0', STR_PAD_LEFT) . '-' . str_pad((string) $line->id, 10, '0', STR_PAD_LEFT));

        $openingCarry = [];
        if ($accountIds) {
            foreach (Account::withoutGlobalScopes()->whereIn('id', $accountIds)->get() as $a) {
                $openingCarry[$a->id] = (float) $a->opening_debit - (float) $a->opening_credit;
            }
        }

        $rows = [];
        $running = $openingCarry;
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($query as $line) {
            $entryDate = $line->entry->date->format('Y-m-d');
            if ($to && $entryDate > $to) {
                continue;
            }

            $accId = (int) $line->account_id;
            if (!isset($running[$accId])) {
                $running[$accId] = (float) $line->account->opening_debit - (float) $line->account->opening_credit;
            }

            $debit = round((float) $line->debit, 2);
            $credit = round((float) $line->credit, 2);
            $running[$accId] = round($running[$accId] + $debit - $credit, 2);

            if ($from && $entryDate < $from) {
                continue;
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $rows[] = [
                'entry_id' => $line->journal_entry_id,
                'source_type' => $line->entry->source_type,
                'source_label' => $line->entry->sourceLabel(),
                'date' => $entryDate,
                'document' => $line->entry->document_number,
                'account' => $line->account,
                'contact_name' => $line->contact?->name,
                'description' => $line->description ?: $line->entry->description,
                'debit' => $debit,
                'credit' => $credit,
                'currency' => $line->currency ?: 'USD',
                'exchange_rate' => (float) ($line->exchange_rate ?? 1),
                'foreign_debit' => (float) ($line->foreign_debit ?? 0),
                'foreign_credit' => (float) ($line->foreign_credit ?? 0),
                'balance' => $running[$accId],
            ];
        }

        return [
            'rows' => $rows,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'opening_balance' => $accountId ? round(array_sum($openingCarry), 2) : 0.0,
        ];
    }

    /**
     * Trial Balance strictly in Base USD (Totals & Balances). Guaranteed balanced.
     */
    public function trialBalance(
        string $shopId,
        ?string $from = null,
        ?string $to = null,
        ?int $maxLevel = null,
        bool $hideZero = false
    ): array {
        $this->ensureShopReady($shopId);

        $accounts = Account::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->orderBy('code')
            ->get();

        $allLines = JournalLine::withoutGlobalScopes()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.shop_id', $shopId)
            ->select(
                'journal_lines.account_id',
                'journal_lines.debit',
                'journal_lines.credit',
                'journal_entries.date'
            )
            ->get();

        $openingByAcc = [];
        $periodDebitByAcc = [];
        $periodCreditByAcc = [];

        foreach ($accounts as $acc) {
            $openingByAcc[$acc->id] = (float) $acc->opening_debit - (float) $acc->opening_credit;
            $periodDebitByAcc[$acc->id] = 0.0;
            $periodCreditByAcc[$acc->id] = 0.0;
        }

        foreach ($allLines as $l) {
            $d = substr((string) $l->date, 0, 10);
            if ($to && $d > $to) {
                continue;
            }
            $aid = (int) $l->account_id;
            if (!isset($openingByAcc[$aid])) {
                continue;
            }
            if ($from && $d < $from) {
                $openingByAcc[$aid] += (float) $l->debit - (float) $l->credit;
            } else {
                $periodDebitByAcc[$aid] += (float) $l->debit;
                $periodCreditByAcc[$aid] += (float) $l->credit;
            }
        }

        // Roll up leaf figures for level-filtered view, while totals always sum postable leaves
        $rows = [];
        $sumPeriodDebit = 0.0;
        $sumPeriodCredit = 0.0;
        $totalDebitBal = 0.0;
        $totalCreditBal = 0.0;

        foreach ($accounts as $account) {
            if (!$account->is_postable) {
                continue;
            }

            $opening = round($openingByAcc[$account->id] ?? 0.0, 2);
            $debit = round($periodDebitByAcc[$account->id] ?? 0.0, 2);
            $credit = round($periodCreditByAcc[$account->id] ?? 0.0, 2);
            $net = round($opening + $debit - $credit, 2);
            $debitBal = $net > 0 ? $net : 0.0;
            $creditBal = $net < 0 ? abs($net) : 0.0;

            $sumPeriodDebit += $debit;
            $sumPeriodCredit += $credit;
            $totalDebitBal += $debitBal;
            $totalCreditBal += $creditBal;

            if ($hideZero && abs($opening) < 0.01 && abs($debit) < 0.01 && abs($credit) < 0.01) {
                continue;
            }

            $rows[] = [
                'account' => $account,
                'opening' => $opening,
                'debit' => $debit,
                'credit' => $credit,
                'net' => $net,
                'debit_balance' => round($debitBal, 2),
                'credit_balance' => round($creditBal, 2),
            ];
        }

        return [
            'rows' => $rows,
            'period_debit' => round($sumPeriodDebit, 2),
            'period_credit' => round($sumPeriodCredit, 2),
            'total_debit' => round($totalDebitBal, 2),
            'total_credit' => round($totalCreditBal, 2),
            'difference' => round($totalDebitBal - $totalCreditBal, 2),
            'balanced' => abs($totalDebitBal - $totalCreditBal) < 0.02,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Collect postable GL account balances (signed: Debit - Credit) for financial statements.
     */
    private function postableAccountBalances(string $shopId, ?string $from = null, ?string $to = null, bool $includeOpening = true): Collection
    {
        $this->ensureShopReady($shopId);

        $accounts = Account::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get();

        $lineQuery = JournalLine::withoutGlobalScopes()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.shop_id', $shopId);

        if ($from) {
            $lineQuery->whereDate('journal_entries.date', '>=', $from);
        }
        if ($to) {
            $lineQuery->whereDate('journal_entries.date', '<=', $to);
        }

        $agg = $lineQuery
            ->selectRaw('journal_lines.account_id')
            ->selectRaw('SUM(journal_lines.debit) as sum_debit')
            ->selectRaw('SUM(journal_lines.credit) as sum_credit')
            ->selectRaw("SUM(CASE WHEN journal_lines.currency = 'SYP' THEN journal_lines.foreign_debit ELSE 0 END) as syp_debit")
            ->selectRaw("SUM(CASE WHEN journal_lines.currency = 'SYP' THEN journal_lines.foreign_credit ELSE 0 END) as syp_credit")
            ->groupBy('journal_lines.account_id')
            ->get()
            ->keyBy('account_id');

        return $accounts->map(function (Account $acc) use ($agg, $includeOpening) {
            $row = $agg->get($acc->id);
            $opening = $includeOpening ? ((float) $acc->opening_debit - (float) $acc->opening_credit) : 0.0;
            $debit = round((float) ($row->sum_debit ?? 0), 2);
            $credit = round((float) ($row->sum_credit ?? 0), 2);
            $signedUsd = round($opening + $debit - $credit, 2); // +Debit, -Credit
            $signedSyp = round((float) ($row->syp_debit ?? 0) - (float) ($row->syp_credit ?? 0), 2);

            return [
                'account' => $acc,
                'signed_usd' => $signedUsd,
                'signed_syp' => $signedSyp,
                'debit_usd' => $debit,
                'credit_usd' => $credit,
            ];
        });
    }

    /**
     * Trading Account strictly from General Ledger (`statement_type = 'trading'`).
     */
    public function getGlTradingAccount(string $shopId, float $currentRate, ?string $from = null, ?string $to = null): array
    {
        $rate = $currentRate > 0 ? $currentRate : $this->resolveRate($shopId);
        $balances = $this->postableAccountBalances($shopId, $from, $to, $from === null);

        $debitItems = [];
        $creditItems = [];
        $debitSum = 0.0;
        $creditSum = 0.0;

        $grossSales = 0.0;
        $salesReturns = 0.0;
        $salesDiscounts = 0.0;
        $cogs = 0.0;
        $purchasesDirect = 0.0;
        $shippingIn = 0.0;
        $purchaseDiscounts = 0.0;

        foreach ($balances as $row) {
            /** @var Account $acc */
            $acc = $row['account'];
            if ($acc->statement_type !== 'trading') {
                continue;
            }
            $signed = $row['signed_usd']; // +Debit, -Credit
            if (abs($signed) < 0.01) {
                continue;
            }

            if ($signed > 0) {
                $debitItems[] = [
                    'account_id' => $acc->id,
                    'code' => $acc->code,
                    'label' => $acc->name,
                    'amount_usd' => round($signed, 2),
                ];
                $debitSum += $signed;
            } else {
                $creditAmt = round(abs($signed), 2);
                $creditItems[] = [
                    'account_id' => $acc->id,
                    'code' => $acc->code,
                    'label' => $acc->name,
                    'amount_usd' => $creditAmt,
                ];
                $creditSum += $creditAmt;
            }

            match ($acc->system_key) {
                'sales' => $grossSales += -$signed,
                'sales_returns' => $salesReturns += $signed,
                'sales_discount' => $salesDiscounts += $signed,
                'cogs' => $cogs += $signed,
                'purchases' => $purchasesDirect += $signed,
                'shipping_in' => $shippingIn += $signed,
                'purchase_discount' => $purchaseDiscounts += -$signed,
                default => null,
            };
        }

        $tradingResult = round($creditSum - $debitSum, 2);
        $grossProfit = $tradingResult > 0 ? $tradingResult : 0.0;
        $grossLoss = $tradingResult < 0 ? abs($tradingResult) : 0.0;
        $balancedTotal = round(max($debitSum + $grossProfit, $creditSum + $grossLoss), 2);

        $netSales = round($grossSales - $salesReturns - $salesDiscounts, 2);
        $effectiveCogs = round($cogs + $purchasesDirect + $shippingIn - $purchaseDiscounts, 2);

        return [
            'source' => 'general_ledger',
            'base_currency' => self::BASE_CURRENCY,
            'current_rate' => $rate,
            'from' => $from,
            'to' => $to,
            'gross_sales_usd' => round($grossSales, 2),
            'sales_returns_usd' => round($salesReturns, 2),
            'sales_discount_usd' => round($salesDiscounts, 2),
            'net_sales_usd' => $netSales,
            'cogs_usd' => round($cogs, 2),
            'purchases_direct_usd' => round($purchasesDirect, 2),
            'shipping_usd' => round($shippingIn, 2),
            'purchase_discount_usd' => round($purchaseDiscounts, 2),
            'periodic_cogs_usd' => $effectiveCogs,
            'debit_items' => $debitItems,
            'credit_items' => $creditItems,
            'trading_result_usd' => $tradingResult,
            'gross_profit_usd' => round($grossProfit, 2),
            'gross_loss_usd' => round($grossLoss, 2),
            'debit_total_usd' => $balancedTotal,
            'credit_total_usd' => $balancedTotal,
        ];
    }

    /**
     * Profit & Loss Account strictly from General Ledger (`statement_type = 'pnl'` + Trading result).
     */
    public function getGlProfitAndLoss(string $shopId, float $currentRate, ?string $from = null, ?string $to = null): array
    {
        $rate = $currentRate > 0 ? $currentRate : $this->resolveRate($shopId);
        $trading = $this->getGlTradingAccount($shopId, $rate, $from, $to);
        $balances = $this->postableAccountBalances($shopId, $from, $to, $from === null);

        $debitItems = [];
        $creditItems = [];
        $debitSum = 0.0;
        $creditSum = 0.0;

        $totalExpenses = 0.0;
        $totalSalaries = 0.0;
        $otherRevenues = 0.0;
        $fxGain = 0.0;
        $fxLoss = 0.0;

        // Transfer Gross Profit / Gross Loss from Trading Account
        $grossProfitSigned = (float) $trading['trading_result_usd'];
        if ($grossProfitSigned < 0) {
            $debitSum += abs($grossProfitSigned);
        } else {
            $creditSum += $grossProfitSigned;
        }

        foreach ($balances as $row) {
            /** @var Account $acc */
            $acc = $row['account'];
            if ($acc->statement_type !== 'pnl') {
                continue;
            }
            $signed = $row['signed_usd']; // +Debit (expense/loss), -Credit (revenue/gain)
            if (abs($signed) < 0.01) {
                continue;
            }

            if ($signed > 0) {
                $amt = round($signed, 2);
                $debitItems[] = [
                    'account_id' => $acc->id,
                    'code' => $acc->code,
                    'label' => $acc->name,
                    'system_key' => $acc->system_key,
                    'amount_usd' => $amt,
                ];
                $debitSum += $amt;

                if ($acc->system_key === 'salaries') {
                    $totalSalaries += $amt;
                } elseif ($acc->system_key === 'fx_loss') {
                    $fxLoss += $amt;
                } else {
                    $totalExpenses += $amt;
                }
            } else {
                $amt = round(abs($signed), 2);
                $creditItems[] = [
                    'account_id' => $acc->id,
                    'code' => $acc->code,
                    'label' => $acc->name,
                    'system_key' => $acc->system_key,
                    'amount_usd' => $amt,
                ];
                $creditSum += $amt;

                if ($acc->system_key === 'fx_gain') {
                    $fxGain += $amt;
                } else {
                    $otherRevenues += $amt;
                }
            }
        }

        $netProfit = round($creditSum - $debitSum, 2);
        $pnlBalancedTotal = round(max(
            $debitSum + ($netProfit > 0 ? $netProfit : 0.0),
            $creditSum + ($netProfit < 0 ? abs($netProfit) : 0.0)
        ), 2);
        $fxDiff = round($fxGain - $fxLoss, 2);

        return [
            'source' => 'general_ledger',
            'base_currency' => self::BASE_CURRENCY,
            'current_rate' => $rate,
            'from' => $from,
            'to' => $to,
            'total_sales_usd' => $trading['gross_sales_usd'],
            'sales_discount_usd' => $trading['sales_discount_usd'],
            'total_sales_returns_usd' => $trading['sales_returns_usd'],
            'net_sales_usd' => $trading['net_sales_usd'],
            'cogs_usd' => $trading['periodic_cogs_usd'],
            'total_shipping_usd' => $trading['shipping_usd'],
            'purchase_discount_usd' => $trading['purchase_discount_usd'],
            'gross_profit_usd' => $grossProfitSigned,
            'other_revenues_usd' => round($otherRevenues, 2),
            'total_expenses_usd' => round($totalExpenses, 2),
            'total_salaries_usd' => round($totalSalaries, 2),
            'fx_diff_usd' => $fxDiff,
            'fx_gain_usd' => round($fxGain, 2),
            'fx_loss_usd' => round($fxLoss, 2),
            'debit_items' => $debitItems,
            'credit_items' => $creditItems,
            'pnl_debit_total_usd' => $pnlBalancedTotal,
            'pnl_credit_total_usd' => $pnlBalancedTotal,
            'estimated_net_profit_usd' => $netProfit,
        ];
    }

    /**
     * Balance Sheet strictly from General Ledger (`statement_type = 'balance_sheet'` + P&L Net Profit).
     * Guaranteed mathematically balanced whenever the Trial Balance is balanced:
     * Assets === Liabilities + Equity + Net Profit.
     */
    public function getGlBalanceSheet(string $shopId, float $currentRate, ?string $asOf = null): array
    {
        $rate = $currentRate > 0 ? $currentRate : $this->resolveRate($shopId);
        $pnl = $this->getGlProfitAndLoss($shopId, $rate, null, $asOf);
        $balances = $this->postableAccountBalances($shopId, null, $asOf, true);

        $currentAssets = [];
        $fixedAssets = [];
        $currentLiabilities = [];
        $longTermLiabilities = [];
        $equityItems = [];

        $totalCurrentAssets = 0.0;
        $totalFixedAssets = 0.0;
        $totalCurrentLiabilities = 0.0;
        $totalLongTermLiabilities = 0.0;
        $totalEquityAccounts = 0.0;

        // Also expose standard headline buckets for compatibility with summary cards
        $cashUsd = 0.0;
        $cashSypUsd = 0.0;
        $cashSyp = 0.0;
        $arUsd = 0.0;
        $arSypUsd = 0.0;
        $arSyp = 0.0;
        $inventoryUsd = 0.0;
        $apUsd = 0.0;
        $apSypUsd = 0.0;
        $apSyp = 0.0;
        $capitalUsd = 0.0;
        $withdrawalsUsd = 0.0;

        foreach ($balances as $row) {
            /** @var Account $acc */
            $acc = $row['account'];
            if ($acc->statement_type !== 'balance_sheet') {
                continue;
            }
            $signed = $row['signed_usd']; // +Debit, -Credit
            $signedSyp = $row['signed_syp'];
            if (abs($signed) < 0.01 && abs($signedSyp) < 1) {
                continue;
            }

            if ($acc->type === 'asset') {
                // Assets have normal debit balance (+signed); contra-assets like 1221 show negative
                $val = round($signed, 2);
                $item = [
                    'account_id' => $acc->id,
                    'code' => $acc->code,
                    'label' => $acc->name,
                    'currency' => $acc->currency,
                    'amount_usd' => $val,
                    'amount_syp' => $acc->currency === 'SYP' ? round($signedSyp, 2) : null,
                ];

                if (str_starts_with($acc->code, '12')) {
                    $fixedAssets[] = $item;
                    $totalFixedAssets += $val;
                } else {
                    $currentAssets[] = $item;
                    $totalCurrentAssets += $val;
                }

                match ($acc->system_key) {
                    'cash_usd' => $cashUsd += $val,
                    'cash_syp' => (function () use (&$cashSypUsd, &$cashSyp, $val, $signedSyp) {
                        $cashSypUsd += $val;
                        $cashSyp += $signedSyp;
                    })(),
                    'ar_usd' => $arUsd += $val,
                    'ar_syp' => (function () use (&$arSypUsd, &$arSyp, $val, $signedSyp) {
                        $arSypUsd += $val;
                        $arSyp += $signedSyp;
                    })(),
                    'inventory' => $inventoryUsd += $val,
                    default => null,
                };
            } elseif ($acc->type === 'liability') {
                // Liabilities have normal credit balance (-signed)
                $val = round(-$signed, 2);
                $item = [
                    'account_id' => $acc->id,
                    'code' => $acc->code,
                    'label' => $acc->name,
                    'currency' => $acc->currency,
                    'amount_usd' => $val,
                    'amount_syp' => $acc->currency === 'SYP' ? round(-$signedSyp, 2) : null,
                ];

                if (str_starts_with($acc->code, '22')) {
                    $longTermLiabilities[] = $item;
                    $totalLongTermLiabilities += $val;
                } else {
                    $currentLiabilities[] = $item;
                    $totalCurrentLiabilities += $val;
                }

                match ($acc->system_key) {
                    'ap_usd' => $apUsd += $val,
                    'ap_syp' => (function () use (&$apSypUsd, &$apSyp, $val, $signedSyp) {
                        $apSypUsd += $val;
                        $apSyp += -$signedSyp;
                    })(),
                    default => null,
                };
            } elseif ($acc->type === 'equity') {
                // Equity has normal credit balance (-signed); drawings (debit) naturally show negative
                $val = round(-$signed, 2);
                $equityItems[] = [
                    'account_id' => $acc->id,
                    'code' => $acc->code,
                    'label' => $acc->name,
                    'system_key' => $acc->system_key,
                    'amount_usd' => $val,
                ];
                $totalEquityAccounts += $val;

                if ($acc->system_key === 'capital') {
                    $capitalUsd += $val;
                } elseif ($acc->system_key === 'withdrawals') {
                    $withdrawalsUsd += abs($val);
                }
            }
        }

        $totalAssets = round($totalCurrentAssets + $totalFixedAssets, 2);
        $totalLiabilities = round($totalCurrentLiabilities + $totalLongTermLiabilities, 2);
        $profitUsd = round((float) $pnl['estimated_net_profit_usd'], 2);
        $netEquity = round($totalEquityAccounts + $profitUsd, 2);
        $totalLiabilitiesAndEquity = round($totalLiabilities + $netEquity, 2);
        $difference = round($totalAssets - $totalLiabilitiesAndEquity, 2);

        return [
            'source' => 'general_ledger',
            'base_currency' => self::BASE_CURRENCY,
            'current_rate' => $rate,
            'as_of' => $asOf,
            'current_assets' => $currentAssets,
            'fixed_assets' => $fixedAssets,
            'current_liabilities' => $currentLiabilities,
            'long_term_liabilities' => $longTermLiabilities,
            'equity_items' => $equityItems,
            'total_current_assets_usd' => round($totalCurrentAssets, 2),
            'total_fixed_assets_usd' => round($totalFixedAssets, 2),
            'total_assets_usd' => $totalAssets,
            'total_current_liabilities_usd' => round($totalCurrentLiabilities, 2),
            'total_long_term_liabilities_usd' => round($totalLongTermLiabilities, 2),
            'total_liabilities_usd' => $totalLiabilities,
            'cash_usd' => round($cashUsd, 2),
            'cash_syp' => round($cashSyp, 2),
            'cash_syp_usd' => round($cashSypUsd, 2),
            'total_cash_usd' => round($cashUsd + $cashSypUsd, 2),
            'ar_usd' => round($arUsd, 2),
            'ar_syp' => round($arSyp, 2),
            'ar_syp_usd' => round($arSypUsd, 2),
            'total_ar_usd' => round($arUsd + $arSypUsd, 2),
            'inventory_usd' => round($inventoryUsd, 2),
            'ap_usd' => round($apUsd, 2),
            'ap_syp' => round($apSyp, 2),
            'ap_syp_usd' => round($apSypUsd, 2),
            'total_ap_usd' => round($apUsd + $apSypUsd, 2),
            'capital_usd' => round($capitalUsd, 2),
            'withdrawals_usd' => round($withdrawalsUsd, 2),
            'profit_usd' => $profitUsd,
            'fx_diff_usd' => (float) $pnl['fx_diff_usd'],
            'net_equity_usd' => $netEquity,
            'total_liabilities_and_equity_usd' => $totalLiabilitiesAndEquity,
            'difference_usd' => $difference,
            'balanced' => abs($difference) < 0.02,
        ];
    }
}
