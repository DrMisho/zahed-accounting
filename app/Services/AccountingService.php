<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Item;
use App\Models\ItemLocationStock;
use App\Models\Location;
use App\Models\PartnerWithdrawal;
use App\Models\SalaryRecord;
use App\Models\StockTransfer;
use App\Models\Transaction;
use App\Models\TransactionLine;
use App\Support\Money;

class AccountingService
{
    public function calculateUsdEquivalent(float $amount, string $currency, float $exchangeRate): float
    {
        return Money::toUsd($amount, $currency, $exchangeRate);
    }

    public function syncTransactionTotals(Transaction $tx): void
    {
        $tx->load('lines');
        if ($tx->lines->count() > 0) {
            $tx->total = round((float) $tx->lines->sum('total'), 2);
            $tx->total_usd = round((float) $tx->lines->sum('total_usd'), 2);
            $first = $tx->lines->first();
            $tx->item_id = $first->item_id;
            $tx->qty = (float) $tx->lines->sum('qty');
            $tx->unit_price = $tx->qty > 0 ? round($tx->total / $tx->qty, 2) : 0;
        } else {
            $tx->total = (float) $tx->qty * (float) $tx->unit_price;
            $tx->total_usd = Money::toUsd($tx->total, $tx->currency, (float) $tx->exchange_rate);
        }

        $subtype = $tx->operation_subtype ?: ($tx->payment_type === 'cash' ? 'cash' : 'credit');
        $tx->operation_subtype = $subtype;

        if ($subtype === 'cash') {
            $tx->payment_type = 'cash';
            $tx->paid_amount = $tx->total;
        } elseif ($subtype === 'credit') {
            $tx->payment_type = 'credit';
            if ($tx->paid_amount === null) {
                $tx->paid_amount = 0;
            }
        } else {
            $tx->payment_type = 'credit';
            $tx->paid_amount = min((float) $tx->paid_amount, (float) $tx->total);
        }

        $tx->paid_amount_usd = Money::toUsd((float) $tx->paid_amount, $tx->currency, (float) $tx->exchange_rate);
        $tx->saveQuietly();
    }

    public function getCashboxSummary(string $shopId, float $currentExchangeRate): array
    {
        $summary = [
            'usd' => $this->emptyCurrencyBucket(),
            'syp' => $this->emptyCurrencyBucket(),
            'total_usd_equivalent' => 0.0,
            'running' => ['usd' => [], 'syp' => []],
        ];

        $movements = CashMovement::where('shop_id', $shopId)->orderBy('date')->orderBy('id')->get();

        foreach ($movements as $cm) {
            $cur = strtolower($cm->currency);
            if (!isset($summary[$cur])) {
                continue;
            }
            $amount = (float) $cm->amount;
            $in = in_array($cm->type, ['opening', 'receipt', 'purchase_return_cash'], true);
            $out = in_array($cm->type, ['payment', 'expense', 'salary', 'withdrawal'], true);

            if ($cm->type === 'opening') {
                $summary[$cur]['details']['opening_balance'] += $amount;
                $summary[$cur]['inflows'] += $amount;
            } elseif ($cm->type === 'receipt') {
                $summary[$cur]['details']['customer_receipts'] += $amount;
                $summary[$cur]['inflows'] += $amount;
            } elseif ($cm->type === 'payment') {
                $summary[$cur]['details']['supplier_payments'] += $amount;
                $summary[$cur]['outflows'] += $amount;
            } elseif ($cm->type === 'expense') {
                $summary[$cur]['details']['expenses'] += $amount;
                $summary[$cur]['outflows'] += $amount;
            } elseif ($cm->type === 'salary') {
                $summary[$cur]['details']['salaries'] += $amount;
                $summary[$cur]['outflows'] += $amount;
            } elseif ($cm->type === 'withdrawal') {
                $summary[$cur]['details']['withdrawals'] += $amount;
                $summary[$cur]['outflows'] += $amount;
            }

            $summary[$cur]['balance'] = $summary[$cur]['inflows'] - $summary[$cur]['outflows'];
            $summary['running'][$cur][] = [
                'id' => $cm->id,
                'balance' => $summary[$cur]['balance'],
            ];
        }

        $linkedTxIds = $movements->where('source_type', 'transaction')->pluck('source_id')->filter()->all();

        $cashTxs = Transaction::where('shop_id', $shopId)
            ->where(function ($q) {
                $q->where('payment_type', 'cash')->orWhere('paid_amount', '>', 0);
            })
            ->when(count($linkedTxIds), fn ($q) => $q->whereNotIn('id', $linkedTxIds))
            ->get();

        foreach ($cashTxs as $tx) {
            $cur = strtolower($tx->currency);
            if (!isset($summary[$cur])) {
                continue;
            }
            $paid = (float) ($tx->paid_amount ?: ($tx->payment_type === 'cash' ? $tx->total : 0));
            if ($paid <= 0) {
                continue;
            }
            if (in_array($tx->type, ['sale', 'purchase_return'], true)) {
                $summary[$cur]['details']['cash_sales'] += $paid;
                $summary[$cur]['inflows'] += $paid;
            } elseif (in_array($tx->type, ['purchase', 'sale_return'], true)) {
                $summary[$cur]['details']['cash_purchases'] += $paid;
                $summary[$cur]['outflows'] += $paid;
            }
        }

        $summary['usd']['balance'] = $summary['usd']['inflows'] - $summary['usd']['outflows'];
        $summary['syp']['balance'] = $summary['syp']['inflows'] - $summary['syp']['outflows'];
        $sypEquivalent = $currentExchangeRate > 0 ? ($summary['syp']['balance'] / $currentExchangeRate) : 0;
        $summary['total_usd_equivalent'] = round($summary['usd']['balance'] + $sypEquivalent, 2);

        return $summary;
    }

    public function getContactStatement(Contact $contact, ?string $from = null, ?string $to = null): array
    {
        $isCustomer = $contact->type === 'customer';
        $events = [];

        $txs = Transaction::where('contact_id', $contact->id)->orderBy('date')->get();
        foreach ($txs as $tx) {
            if ($from && $tx->date->format('Y-m-d') < $from) {
                continue;
            }
            if ($to && $tx->date->format('Y-m-d') > $to) {
                continue;
            }
            $total = (float) $tx->total;
            $paid = (float) ($tx->paid_amount ?: ($tx->payment_type === 'cash' ? $total : 0));
            $remainder = max(0, $total - $paid);

            if ($isCustomer) {
                if ($tx->type === 'sale' && $remainder > 0) {
                    $events[] = $this->event($tx->date, "فاتورة بيع #{$tx->invoice_number}", $remainder, 0, $tx->currency, true);
                } elseif ($tx->type === 'sale_return' && $remainder > 0) {
                    $events[] = $this->event($tx->date, "مرتجع مبيعات #{$tx->invoice_number}", 0, $remainder, $tx->currency, true);
                }
            } else {
                if ($tx->type === 'purchase' && $remainder > 0) {
                    $events[] = $this->event($tx->date, "فاتورة شراء #{$tx->invoice_number}", 0, $remainder, $tx->currency, true);
                } elseif ($tx->type === 'purchase_return' && $remainder > 0) {
                    $events[] = $this->event($tx->date, "مرتجع مشتريات #{$tx->invoice_number}", $remainder, 0, $tx->currency, true);
                }
            }
        }

        $cms = CashMovement::where('contact_id', $contact->id)->where('affects_balance', true)->orderBy('date')->get();
        foreach ($cms as $cm) {
            if ($from && $cm->date->format('Y-m-d') < $from) {
                continue;
            }
            if ($to && $cm->date->format('Y-m-d') > $to) {
                continue;
            }
            if ($isCustomer && $cm->type === 'receipt') {
                $events[] = $this->event($cm->date, "قبض {$cm->notes}", 0, (float) $cm->amount, $cm->currency, (bool) $cm->affects_balance);
            } elseif (!$isCustomer && $cm->type === 'payment') {
                $events[] = $this->event($cm->date, "صرف {$cm->notes}", (float) $cm->amount, 0, $cm->currency, (bool) $cm->affects_balance);
            }
        }

        usort($events, fn ($a, $b) => strcmp($a['date'], $b['date']));

        $balUsd = (float) $contact->opening_usd;
        $balSyp = (float) $contact->opening_syp;
        $rows = [];
        foreach ($events as $ev) {
            $signed = $isCustomer ? ($ev['debit'] - $ev['credit']) : ($ev['credit'] - $ev['debit']);
            if ($ev['currency'] === 'SYP') {
                $balSyp += $signed;
            } else {
                $balUsd += $signed;
            }
            $ev['balance_usd'] = round($balUsd, 2);
            $ev['balance_syp'] = round($balSyp, 2);
            $rows[] = $ev;
        }

        return [
            'contact' => $contact,
            'rows' => $rows,
            'opening_usd' => (float) $contact->opening_usd,
            'opening_syp' => (float) $contact->opening_syp,
            'final_balance_usd' => round($balUsd, 2),
            'final_balance_syp' => round($balSyp, 2),
        ];
    }

    public function getInventoryValuation(string $shopId): array
    {
        $items = Item::where('shop_id', $shopId)->get();
        $reports = [];

        foreach ($items as $item) {
            $qty = fn (string $type) => (float) TransactionLine::where('item_id', $item->id)
                ->whereHas('transaction', fn ($q) => $q->where('type', $type))
                ->sum('qty');

            $val = fn (string $type) => (float) TransactionLine::where('item_id', $item->id)
                ->whereHas('transaction', fn ($q) => $q->where('type', $type))
                ->sum('total_usd');

            $headerQty = fn (string $type) => (float) Transaction::where('item_id', $item->id)->where('type', $type)
                ->whereDoesntHave('lines')->sum('qty');
            $headerVal = fn (string $type) => (float) Transaction::where('item_id', $item->id)->where('type', $type)
                ->whereDoesntHave('lines')->sum('total_usd');

            $purchasedQty = $qty('purchase') + $headerQty('purchase');
            $purchasedValUsd = $val('purchase') + $headerVal('purchase');
            $soldQty = $qty('sale') + $headerQty('sale');
            $salesReturnsQty = $qty('sale_return') + $headerQty('sale_return');
            $purchaseReturnsQty = $qty('purchase_return') + $headerQty('purchase_return');

            $openingQty = (float) ($item->opening_qty ?? 0);
            $openingCostUsd = (float) ($item->opening_cost_usd ?? 0);
            $openingValUsd = $openingQty * $openingCostUsd;
            $availableQty = $openingQty + $purchasedQty + $salesReturnsQty;
            $availableValUsd = $openingValUsd + $purchasedValUsd;
            $wacUsd = $availableQty > 0 ? ($availableValUsd / $availableQty) : $openingCostUsd;
            $currentStock = $availableQty - ($soldQty + $purchaseReturnsQty);

            $reports[] = [
                'item' => $item,
                'opening_qty' => $openingQty,
                'purchased_qty' => $purchasedQty,
                'sold_qty' => $soldQty,
                'sales_return_qty' => $salesReturnsQty,
                'purchase_return_qty' => $purchaseReturnsQty,
                'current_stock' => $currentStock,
                'wac_usd' => round($wacUsd, 2),
                'stock_value_usd' => round(max(0, $currentStock * $wacUsd), 2),
                'is_low_stock' => $currentStock <= (float) $item->alert_limit,
                'net_sales_usd' => round(($val('sale') + $headerVal('sale')) - ($val('sale_return') + $headerVal('sale_return')), 2),
                'cogs_usd' => round($soldQty * $wacUsd, 2),
            ];
        }

        return $reports;
    }

    public function getLocationInventory(string $shopId): array
    {
        $locations = Location::where('shop_id', $shopId)->get();
        $items = Item::where('shop_id', $shopId)->get();
        $rows = [];

        foreach ($locations as $location) {
            foreach ($items as $item) {
                $opening = ItemLocationStock::where('location_id', $location->id)->where('item_id', $item->id)->first();
                $openingQty = (float) ($opening->opening_qty ?? 0);
                $inPurchase = (float) TransactionLine::where('item_id', $item->id)->where('location_id', $location->id)
                    ->whereHas('transaction', fn ($q) => $q->where('type', 'purchase'))->sum('qty');
                $sold = (float) TransactionLine::where('item_id', $item->id)->where('location_id', $location->id)
                    ->whereHas('transaction', fn ($q) => $q->where('type', 'sale'))->sum('qty');
                $saleRet = (float) TransactionLine::where('item_id', $item->id)->where('location_id', $location->id)
                    ->whereHas('transaction', fn ($q) => $q->where('type', 'sale_return'))->sum('qty');
                $purRet = (float) TransactionLine::where('item_id', $item->id)->where('location_id', $location->id)
                    ->whereHas('transaction', fn ($q) => $q->where('type', 'purchase_return'))->sum('qty');
                $transferIn = (float) StockTransfer::where('to_location_id', $location->id)->where('item_id', $item->id)->sum('qty');
                $transferOut = (float) StockTransfer::where('from_location_id', $location->id)->where('item_id', $item->id)->sum('qty');

                $current = $openingQty + $inPurchase + $saleRet + $transferIn - $sold - $purRet - $transferOut;
                $rows[] = [
                    'location' => $location,
                    'item' => $item,
                    'opening_qty' => $openingQty,
                    'purchased' => $inPurchase,
                    'sold' => $sold,
                    'sale_return' => $saleRet,
                    'purchase_return' => $purRet,
                    'transfer_in' => $transferIn,
                    'transfer_out' => $transferOut,
                    'current' => $current,
                    'opening_cost_usd' => (float) ($opening->opening_cost_usd ?? 0),
                    'negative' => $current < 0,
                ];
            }
        }

        return $rows;
    }

    public function getProfitAndLossReport(string $shopId): array
    {
        $stockReports = $this->getInventoryValuation($shopId);
        $wacMap = [];
        foreach ($stockReports as $sr) {
            $wacMap[$sr['item']->id] = $sr['wac_usd'];
        }

        $sales = Transaction::with('lines')->where('shop_id', $shopId)->where('type', 'sale')->get();
        $salesReturns = Transaction::with('lines')->where('shop_id', $shopId)->where('type', 'sale_return')->get();
        $totalSalesUsd = (float) $sales->sum('total_usd');
        $totalSalesReturnsUsd = (float) $salesReturns->sum('total_usd');
        $netSalesUsd = $totalSalesUsd - $totalSalesReturnsUsd;

        $cogsUsd = 0.0;
        foreach ($sales as $s) {
            $cogsUsd += $this->cogsOf($s, $wacMap);
        }
        foreach ($salesReturns as $sr) {
            $cogsUsd -= $this->cogsOf($sr, $wacMap);
        }

        $grossProfitUsd = $netSalesUsd - $cogsUsd;
        $totalExpensesUsd = (float) Expense::where('shop_id', $shopId)->sum('amount_usd');
        if ($totalExpensesUsd == 0.0) {
            $totalExpensesUsd = (float) CashMovement::where('shop_id', $shopId)->where('type', 'expense')->sum('amount_usd');
        }
        $totalSalariesUsd = (float) SalaryRecord::where('shop_id', $shopId)->sum('amount_usd');
        if ($totalSalariesUsd == 0.0) {
            $totalSalariesUsd = (float) CashMovement::where('shop_id', $shopId)->where('type', 'salary')->sum('amount_usd');
        }
        $totalWithdrawalsUsd = (float) PartnerWithdrawal::where('shop_id', $shopId)
            ->where('classification_status', 'مصنف')
            ->where('nature', 'مسحوب شخصي نهائي')
            ->sum('amount_usd');
        if ($totalWithdrawalsUsd == 0.0) {
            $totalWithdrawalsUsd = (float) CashMovement::where('shop_id', $shopId)->where('type', 'withdrawal')->sum('amount_usd');
        }

        $operatingCostsUsd = $totalExpensesUsd + $totalSalariesUsd;
        $estimatedNetProfitUsd = $grossProfitUsd - $operatingCostsUsd;
        $profitMargin = $netSalesUsd > 0 ? ($estimatedNetProfitUsd / $netSalesUsd) * 100 : 0.0;

        return [
            'total_sales_usd' => round($totalSalesUsd, 2),
            'total_sales_returns_usd' => round($totalSalesReturnsUsd, 2),
            'net_sales_usd' => round($netSalesUsd, 2),
            'cogs_usd' => round($cogsUsd, 2),
            'gross_profit_usd' => round($grossProfitUsd, 2),
            'total_expenses_usd' => round($totalExpensesUsd, 2),
            'total_salaries_usd' => round($totalSalariesUsd, 2),
            'total_withdrawals_usd' => round($totalWithdrawalsUsd, 2),
            'estimated_net_profit_usd' => round($estimatedNetProfitUsd, 2),
            'profit_margin_percent' => round($profitMargin, 1),
        ];
    }

    public function getBalanceSheet(string $shopId, float $rate): array
    {
        $cash = $this->getCashboxSummary($shopId, $rate);
        $customers = Contact::where('type', 'customer')->get();
        $suppliers = Contact::where('type', 'supplier')->get();
        $arUsd = 0;
        $arSyp = 0;
        $apUsd = 0;
        $apSyp = 0;
        foreach ($customers as $c) {
            $s = $this->getContactStatement($c);
            $arUsd += $s['final_balance_usd'];
            $arSyp += $s['final_balance_syp'];
        }
        foreach ($suppliers as $c) {
            $s = $this->getContactStatement($c);
            $apUsd += $s['final_balance_usd'];
            $apSyp += $s['final_balance_syp'];
        }
        $stock = collect($this->getInventoryValuation($shopId))->sum('stock_value_usd');
        $pnl = $this->getProfitAndLossReport($shopId);

        return [
            'cash_usd' => $cash['usd']['balance'],
            'cash_syp' => $cash['syp']['balance'],
            'ar_usd' => round($arUsd, 2),
            'ar_syp' => round($arSyp, 2),
            'inventory_usd' => round($stock, 2),
            'ap_usd' => round($apUsd, 2),
            'ap_syp' => round($apSyp, 2),
            'profit_usd' => $pnl['estimated_net_profit_usd'],
            'withdrawals_usd' => $pnl['total_withdrawals_usd'],
        ];
    }

    public function itemMovement(string $shopId, ?int $itemId = null): array
    {
        $query = Transaction::with(['item', 'contact', 'lines.item'])->where('shop_id', $shopId)->orderBy('date');
        $rows = [];
        foreach ($query->get() as $tx) {
            $lines = $tx->lines->count() ? $tx->lines : collect([(object) [
                'item' => $tx->item,
                'qty' => $tx->qty,
                'unit_price' => $tx->unit_price,
                'total' => $tx->total,
            ]]);
            foreach ($lines as $line) {
                if ($itemId && (int) ($line->item_id ?? $tx->item_id) !== $itemId) {
                    continue;
                }
                $rows[] = [
                    'date' => $tx->date->format('Y-m-d'),
                    'type' => $tx->type,
                    'document' => $tx->invoice_number,
                    'item' => $line->item->item_name ?? $tx->item?->item_name,
                    'qty' => $line->qty,
                    'unit_price' => $line->unit_price,
                    'total' => $line->total,
                    'notes' => $tx->notes,
                ];
            }
        }
        return $rows;
    }

    private function cogsOf(Transaction $tx, array $wacMap): float
    {
        if ($tx->lines->count()) {
            $cogs = 0;
            foreach ($tx->lines as $line) {
                $cogs += (float) $line->qty * ($wacMap[$line->item_id] ?? 0);
            }
            return $cogs;
        }
        return (float) $tx->qty * ($wacMap[$tx->item_id] ?? 0);
    }

    private function event($date, string $desc, float $debit, float $credit, string $currency, bool $affects): array
    {
        return [
            'date' => $date->format('Y-m-d'),
            'description' => $desc,
            'debit' => $debit,
            'credit' => $credit,
            'currency' => $currency,
            'affects' => $affects,
        ];
    }

    private function emptyCurrencyBucket(): array
    {
        return [
            'inflows' => 0.0,
            'outflows' => 0.0,
            'balance' => 0.0,
            'details' => [
                'cash_sales' => 0.0,
                'customer_receipts' => 0.0,
                'opening_balance' => 0.0,
                'cash_purchases' => 0.0,
                'supplier_payments' => 0.0,
                'expenses' => 0.0,
                'salaries' => 0.0,
                'withdrawals' => 0.0,
            ],
        ];
    }
}
