<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CurrencyExchange;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\ShippingExpense;
use App\Models\Shop;
use App\Models\Transaction;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class JournalService
{
    public function accountId(string $shopId, string $role): ?int
    {
        return AccountMapping::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('role', $role)
            ->value('account_id');
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

    public function postTransaction(Transaction $tx, AccountingService $accounting): void
    {
        $shop = Shop::find($tx->shop_id);
        if (!$shop || !$shop->posting_enabled) {
            return;
        }

        $this->forgetSource($tx->shop_id, 'transaction', $tx->id);

        $cur = $tx->currency;
        $amount = (float) $tx->total;
        $amountUsd = (float) $tx->total_usd;
        $discount = (float) ($tx->discount ?? 0);
        $discountUsd = (float) ($tx->discount_usd ?? 0);
        $gross = (float) ($tx->subtotal > 0 ? $tx->subtotal : ($amount + $discount));
        $grossUsd = (float) ($tx->subtotal_usd > 0 ? $tx->subtotal_usd : ($amountUsd + $discountUsd));
        $paid = (float) $tx->paid_amount;
        $paidUsd = (float) $tx->paid_amount_usd;
        $remainder = max(0, $amount - $paid);
        $remainderUsd = Money::toUsd($remainder, $cur, (float) $tx->exchange_rate);

        $lines = [];
        $desc = "فاتورة {$tx->invoice_number}";

        if (in_array($tx->type, ['sale', 'sale_return'], true)) {
            $salesRole = $tx->type === 'sale_return' ? 'sales_returns' : 'sales';
            $hasSalesDiscountAccount = $discount > 0 && (bool) $this->accountId($tx->shop_id, 'sales_discount');

            if ($tx->type === 'sale') {
                if ($paid > 0) {
                    $lines[] = [$this->cashRole($cur), $paid, 0, $paidUsd, $desc.' نقداً'];
                }
                if ($remainder > 0) {
                    $lines[] = [$this->arRole($cur), $remainder, 0, $remainderUsd, $desc.' آجل'];
                }
                if ($hasSalesDiscountAccount) {
                    $lines[] = ['sales_discount', $discount, 0, $discountUsd, 'حسم ممنوح - '.$desc];
                    $lines[] = [$salesRole, 0, $gross, $grossUsd, $desc];
                } else {
                    $lines[] = [$salesRole, 0, $amount, $amountUsd, $desc];
                }
            } else {
                $lines[] = [$salesRole, $amount, 0, $amountUsd, $desc];
                if ($paid > 0) {
                    $lines[] = [$this->cashRole($cur), 0, $paid, $paidUsd, $desc];
                }
                if ($remainder > 0) {
                    $lines[] = [$this->arRole($cur), 0, $remainder, $remainderUsd, $desc];
                }
            }

            $cogs = $this->cogsFor($tx, $accounting);
            if ($cogs > 0) {
                if ($tx->type === 'sale') {
                    $lines[] = ['cogs', $cogs, 0, $cogs, 'تكلفة المبيعات'];
                    $lines[] = ['inventory', 0, $cogs, $cogs, 'صرف مخزون'];
                } else {
                    $lines[] = ['inventory', $cogs, 0, $cogs, 'إرجاع مخزون'];
                    $lines[] = ['cogs', 0, $cogs, $cogs, 'عكس تكلفة'];
                }
            }
        }

        if (in_array($tx->type, ['purchase', 'purchase_return'], true)) {
            $hasPurchaseDiscountAccount = $discount > 0 && (bool) $this->accountId($tx->shop_id, 'purchase_discount');

            if ($tx->type === 'purchase') {
                if ($hasPurchaseDiscountAccount) {
                    $lines[] = ['inventory', $gross, 0, $grossUsd, $desc];
                    $lines[] = ['purchase_discount', 0, $discount, $discountUsd, 'حسم مكتسب - '.$desc];
                } else {
                    $lines[] = ['inventory', $amount, 0, $amountUsd, $desc];
                }
                if ($paid > 0) {
                    $lines[] = [$this->cashRole($cur), 0, $paid, $paidUsd, $desc];
                }
                if ($remainder > 0) {
                    $lines[] = [$this->apRole($cur), 0, $remainder, $remainderUsd, $desc];
                }
            } else {
                if ($paid > 0) {
                    $lines[] = [$this->cashRole($cur), $paid, 0, $paidUsd, $desc];
                }
                if ($remainder > 0) {
                    $lines[] = [$this->apRole($cur), $remainder, 0, $remainderUsd, $desc];
                }
                $lines[] = ['inventory', 0, $amount, $amountUsd, $desc];
            }
        }

        $this->write($tx->shop_id, $tx->date->format('Y-m-d'), $tx->invoice_number, 'transaction', $tx->id, $desc, $lines, $cur);
    }

    public function postCurrencyExchange(CurrencyExchange $exchange): void
    {
        $shop = Shop::find($exchange->shop_id);
        if (!$shop || !$shop->posting_enabled) {
            return;
        }

        $usd = (float) $exchange->amount_usd;
        if ($usd <= 0) {
            return;
        }

        $debitRole = $this->cashRole($exchange->to_currency);
        $creditRole = $this->cashRole($exchange->from_currency);
        $doc = $exchange->reference_number ?: 'EX-'.$exchange->id;
        $desc = $exchange->directionLabel();

        $baseRate = (float) ($shop->exchange_rate > 0 ? $shop->exchange_rate : 14500);
        $fxDiff = $exchange->fxDiffUsd($baseRate);
        $hasFxAccount = abs($fxDiff) >= 0.01 && (bool) $this->accountId($exchange->shop_id, 'fx_diff');

        $lines = [];
        if ($hasFxAccount) {
            if ($exchange->direction === 'syp_to_usd') {
                $sypBookUsd = round((float) $exchange->from_amount / $baseRate, 2);
                $lines[] = [$debitRole, $usd, 0, $usd, $desc];
                $lines[] = [$creditRole, 0, $sypBookUsd, $sypBookUsd, $desc];
                if ($fxDiff > 0) {
                    $lines[] = ['fx_diff', 0, $fxDiff, $fxDiff, 'ربح فرق صرف عملة - '.$doc];
                } else {
                    $loss = abs($fxDiff);
                    $lines[] = ['fx_diff', $loss, 0, $loss, 'خسارة فرق صرف عملة - '.$doc];
                }
            } else {
                $sypReceivedBookUsd = round((float) $exchange->to_amount / $baseRate, 2);
                $lines[] = [$debitRole, $sypReceivedBookUsd, 0, $sypReceivedBookUsd, $desc];
                $lines[] = [$creditRole, 0, $usd, $usd, $desc];
                if ($fxDiff > 0) {
                    $lines[] = ['fx_diff', 0, $fxDiff, $fxDiff, 'ربح فرق صرف عملة - '.$doc];
                } else {
                    $loss = abs($fxDiff);
                    $lines[] = ['fx_diff', $loss, 0, $loss, 'خسارة فرق صرف عملة - '.$doc];
                }
            }
        } else {
            $lines[] = [$debitRole, $usd, 0, $usd, $desc];
            $lines[] = [$creditRole, 0, $usd, $usd, $desc];
        }

        $this->forgetSource($exchange->shop_id, 'currency_exchange', $exchange->id);
        $this->write($exchange->shop_id, $exchange->date->format('Y-m-d'), $doc, 'currency_exchange', $exchange->id, $desc, $lines, 'USD');
    }

    public function postShippingExpense(ShippingExpense $shipping): void
    {
        $doc = $shipping->voucher_number ?: 'SHP-'.$shipping->id;
        $desc = 'أجور شحن مشتريات' . ($shipping->transaction ? ' - فاتورة '.$shipping->transaction->invoice_number : '');
        $this->postSimple(
            $shipping->shop_id,
            $shipping->date->format('Y-m-d'),
            $doc,
            'shipping_expense',
            $shipping->id,
            $desc,
            'inventory',
            $this->cashRole($shipping->currency),
            (float) $shipping->amount,
            $shipping->currency,
            (float) $shipping->exchange_rate
        );
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
        $shop = Shop::find($shopId);
        if (!$shop || !$shop->posting_enabled) {
            return;
        }

        $usd = Money::toUsd($amount, $currency, $rate);
        $this->forgetSource($shopId, $sourceType, $sourceId);
        $this->write($shopId, $date, $doc, $sourceType, $sourceId, $description, [
            [$debitRole, $amount, 0, $usd, $description],
            [$creditRole, 0, $amount, $usd, $description],
        ], $currency);
    }

    public function forgetSource(string $shopId, string $sourceType, int $sourceId): void
    {
        JournalEntry::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->each(fn (JournalEntry $e) => $e->delete());
    }

    private function cogsFor(Transaction $tx, AccountingService $accounting): float
    {
        $reports = $accounting->getInventoryValuation($tx->shop_id);
        $wac = [];
        foreach ($reports as $sr) {
            $wac[$sr['item']->id] = $sr['wac_usd'];
        }
        $qty = (float) $tx->qty;
        if ($tx->lines()->exists()) {
            $cogs = 0;
            foreach ($tx->lines as $line) {
                $cogs += (float) $line->qty * ($wac[$line->item_id] ?? 0);
            }
            return round($cogs, 2);
        }
        return round($qty * ($wac[$tx->item_id] ?? 0), 2);
    }

    private function write(
        string $shopId,
        string $date,
        string $doc,
        string $sourceType,
        int $sourceId,
        string $description,
        array $roleLines,
        string $currency
    ): void {
        $resolved = [];
        foreach ($roleLines as [$role, $debit, $credit, $usd, $lineDesc]) {
            $accountId = $this->accountId($shopId, $role);
            if (!$accountId) {
                return;
            }
            $resolved[] = compact('accountId', 'debit', 'credit', 'usd', 'lineDesc');
        }

        DB::transaction(function () use ($shopId, $date, $doc, $sourceType, $sourceId, $description, $resolved, $currency) {
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
                    'account_id' => $line['accountId'],
                    'description' => $line['lineDesc'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'currency' => $currency,
                    'amount_usd' => $line['usd'],
                ]);
            }
        });
    }

    public function ledger(string $shopId, ?int $accountId = null): array
    {
        $query = JournalLine::with(['account', 'entry'])
            ->where('shop_id', $shopId)
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->get()
            ->sortBy(fn ($line) => ($line->entry?->date?->format('Y-m-d') ?? '').'-'.$line->id);

        $rows = [];
        $running = [];
        foreach ($query as $line) {
            if (!$line->account || !$line->entry) {
                continue;
            }
            $accId = $line->account_id;
            $running[$accId] = ($running[$accId] ?? 0) + (float) $line->debit - (float) $line->credit;
            $opening = (float) $line->account->opening_debit - (float) $line->account->opening_credit;
            $rows[] = [
                'date' => $line->entry->date->format('Y-m-d'),
                'document' => $line->entry->document_number,
                'account' => $line->account,
                'description' => $line->description,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'balance' => round($opening + $running[$accId], 2),
            ];
        }

        return $rows;
    }

    public function trialBalance(string $shopId): array
    {
        $accounts = Account::where('shop_id', $shopId)->orderBy('code')->get();
        $rows = [];
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($accounts as $account) {
            $debit = (float) $account->lines()->sum('debit');
            $credit = (float) $account->lines()->sum('credit');
            $opening = (float) $account->opening_debit - (float) $account->opening_credit;
            $net = $opening + $debit - $credit;
            $debitBal = $net >= 0 ? $net : 0;
            $creditBal = $net < 0 ? abs($net) : 0;
            $rows[] = [
                'account' => $account,
                'opening' => $opening,
                'debit' => $debit,
                'credit' => $credit,
                'net' => $net,
                'debit_balance' => $debitBal,
                'credit_balance' => $creditBal,
            ];
            $totalDebit += $debitBal;
            $totalCredit += $creditBal;
        }

        return [
            'rows' => $rows,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'difference' => round($totalDebit - $totalCredit, 2),
            'balanced' => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }
}
