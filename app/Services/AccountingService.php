<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\Contact;
use App\Models\CurrencyExchange;
use App\Models\EmployeeMovement;
use App\Models\Expense;
use App\Models\Item;
use App\Models\ItemLocationStock;
use App\Models\Location;
use App\Models\PartnerWithdrawal;
use App\Models\SalaryRecord;
use App\Models\ShippingExpense;
use App\Models\Shop;
use App\Models\StockTransfer;
use App\Models\Transaction;
use App\Models\TransactionLine;
use App\Support\Money;

class AccountingService
{
    public const BASE_CURRENCY = 'USD';

    public function resolveShopRate(string $shopId, ?float $rate = null): float
    {
        if ($rate !== null && $rate > 0) {
            return $rate;
        }
        $shopRate = (float) (Shop::where('id', $shopId)->value('exchange_rate') ?? 0);
        return $shopRate > 0 ? $shopRate : 14500.0;
    }

    public function calculateUsdEquivalent(float $amount, string $currency, float $exchangeRate): float
    {
        return Money::toUsd($amount, $currency, $exchangeRate);
    }

    public function syncTransactionTotals(Transaction $tx): void
    {
        $tx->load('lines');
        $rate = (float) ($tx->exchange_rate > 0 ? $tx->exchange_rate : 14500);

        if ($tx->lines->count() > 0) {
            $gross = round((float) $tx->lines->sum('total'), 2);
            $grossUsd = round((float) $tx->lines->sum('total_usd'), 2);
            $first = $tx->lines->first();
            $tx->item_id = $first->item_id;
            $tx->qty = (float) $tx->lines->sum('qty');
            $tx->unit_price = $tx->qty > 0 ? round($gross / $tx->qty, 2) : 0;
        } else {
            $gross = round((float) $tx->qty * (float) $tx->unit_price, 2);
            $grossUsd = Money::toUsd($gross, $tx->currency, $rate);
        }

        $discount = round(max(0, min((float) ($tx->discount ?? 0), $gross)), 2);
        $discountUsd = Money::toUsd($discount, $tx->currency, $rate);
        $net = round(max(0, $gross - $discount), 2);
        $netUsd = Money::toUsd($net, $tx->currency, $rate);

        $tx->subtotal = $gross;
        $tx->subtotal_usd = $grossUsd;
        $tx->discount = $discount;
        $tx->discount_usd = $discountUsd;
        $tx->total = $net;
        $tx->total_usd = $netUsd;

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

        $tx->paid_amount_usd = Money::toUsd((float) $tx->paid_amount, $tx->currency, $rate);
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

            if ($cm->source_type === 'currency_exchange_in') {
                $summary[$cur]['details']['currency_exchange_in'] += $amount;
                $summary[$cur]['inflows'] += $amount;
            } elseif ($cm->source_type === 'currency_exchange_out') {
                $summary[$cur]['details']['currency_exchange_out'] += $amount;
                $summary[$cur]['outflows'] += $amount;
            } elseif ($cm->source_type === 'shipping_expense') {
                $summary[$cur]['details']['shipping_expenses'] += $amount;
                $summary[$cur]['outflows'] += $amount;
            } elseif ($cm->type === 'opening') {
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

    public function getContactStatement(Contact $contact, ?string $from = null, ?string $to = null, ?float $currentRate = null): array
    {
        $isCustomer = $contact->type === 'customer';
        $rate = $this->resolveShopRate((string) $contact->shop_id, $currentRate);
        $events = [];

        $openingUsd = (float) $contact->opening_usd;
        $openingSyp = (float) $contact->opening_syp;
        $openingSypHistUsd = $rate > 0 ? round($openingSyp / $rate, 2) : 0.0;

        $totalInvoicesUsd = 0.0;
        $totalReturnsUsd = 0.0;
        $totalPaidUsd = 0.0;
        $invoiceCount = 0;

        $txs = Transaction::with(['item', 'lines.item'])
            ->where('contact_id', $contact->id)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        foreach ($txs as $tx) {
            $txDate = $tx->date->format('Y-m-d');
            if ($to && $txDate > $to) {
                continue;
            }

            $total = (float) $tx->total;
            $paid = (float) ($tx->paid_amount ?: ($tx->payment_type === 'cash' ? $total : 0));
            $paid = max(0.0, min($paid, $total));
            $remainder = max(0.0, round($total - $paid, 2));
            $txRate = (float) ($tx->exchange_rate > 0 ? $tx->exchange_rate : $rate);

            $debit = 0.0;
            $credit = 0.0;
            $desc = '';
            $matched = false;

            if ($isCustomer) {
                if ($tx->type === 'sale') {
                    $debit = $total;
                    $credit = $paid;
                    $desc = "فاتورة بيع #{$tx->invoice_number}";
                    $matched = true;
                } elseif ($tx->type === 'sale_return') {
                    $debit = $paid;
                    $credit = $total;
                    $desc = "مرتجع مبيعات #{$tx->invoice_number}";
                    $matched = true;
                }
            } else {
                if ($tx->type === 'purchase') {
                    $debit = $paid;
                    $credit = $total;
                    $desc = "فاتورة شراء #{$tx->invoice_number}";
                    $matched = true;
                } elseif ($tx->type === 'purchase_return') {
                    $debit = $total;
                    $credit = $paid;
                    $desc = "مرتجع مشتريات #{$tx->invoice_number}";
                    $matched = true;
                }
            }

            if (!$matched) {
                continue;
            }

            $signed = $isCustomer ? ($debit - $credit) : ($credit - $debit);

            if ($from && $txDate < $from) {
                if ($tx->currency === 'SYP') {
                    $openingSyp += $signed;
                    $openingSypHistUsd += Money::toUsd($signed, 'SYP', $txRate);
                } else {
                    $openingUsd += $signed;
                }
                continue;
            }

            if (in_array($tx->type, ['sale', 'purchase'], true)) {
                $totalInvoicesUsd += (float) $tx->total_usd;
                $totalPaidUsd += (float) ($tx->paid_amount_usd ?: Money::toUsd($paid, $tx->currency, $txRate));
                $invoiceCount++;
            } else {
                $totalReturnsUsd += (float) $tx->total_usd;
            }

            $items = [];
            if ($tx->lines->count() > 0) {
                foreach ($tx->lines as $line) {
                    $items[] = [
                        'item_name' => $line->item->item_name ?? 'صنف',
                        'unit' => $line->item->unit ?? '',
                        'qty' => (float) $line->qty,
                        'unit_price' => (float) $line->unit_price,
                        'total' => (float) $line->total,
                        'total_usd' => (float) $line->total_usd,
                    ];
                }
            } elseif ($tx->item || (float) $tx->qty > 0) {
                $items[] = [
                    'item_name' => $tx->item->item_name ?? ($tx->notes ?: 'بند فاتورة'),
                    'unit' => $tx->item->unit ?? '',
                    'qty' => (float) $tx->qty,
                    'unit_price' => (float) $tx->unit_price,
                    'total' => (float) ($tx->subtotal > 0 ? $tx->subtotal : $total),
                    'total_usd' => (float) ($tx->subtotal_usd > 0 ? $tx->subtotal_usd : $tx->total_usd),
                ];
            }

            $events[] = $this->event($tx->date, $desc, $debit, $credit, $tx->currency, true, [
                'sort_order' => 1,
                'record_id' => (int) $tx->id,
                'kind' => 'invoice',
                'transaction_id' => (int) $tx->id,
                'tx_type' => $tx->type,
                'invoice_number' => $tx->invoice_number,
                'payment_type' => $tx->payment_type,
                'operation_subtype' => $tx->operation_subtype,
                'subtotal' => (float) ($tx->subtotal > 0 ? $tx->subtotal : ($total + (float) $tx->discount)),
                'discount' => (float) $tx->discount,
                'discount_label' => $tx->discountLabel(),
                'total' => $total,
                'paid' => $paid,
                'remaining' => $remainder,
                'exchange_rate' => $txRate,
                'total_usd' => (float) $tx->total_usd,
                'notes' => $tx->notes,
                'items' => $items,
            ]);
        }

        $cms = CashMovement::where('contact_id', $contact->id)
            ->where('affects_balance', true)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        foreach ($cms as $cm) {
            $cmDate = $cm->date->format('Y-m-d');
            if ($to && $cmDate > $to) {
                continue;
            }

            $debit = 0.0;
            $credit = 0.0;
            $desc = '';
            $matched = false;

            if ($isCustomer && $cm->type === 'receipt') {
                $credit = (float) $cm->amount;
                $desc = trim('قبض ' . ($cm->voucher_number ? "#{$cm->voucher_number} " : '') . ($cm->notes ?? ''));
                $matched = true;
            } elseif (!$isCustomer && $cm->type === 'payment') {
                $debit = (float) $cm->amount;
                $desc = trim('صرف ' . ($cm->voucher_number ? "#{$cm->voucher_number} " : '') . ($cm->notes ?? ''));
                $matched = true;
            }

            if (!$matched) {
                continue;
            }

            $cmRate = (float) ($cm->exchange_rate > 0 ? $cm->exchange_rate : $rate);
            $signed = $isCustomer ? ($debit - $credit) : ($credit - $debit);

            if ($from && $cmDate < $from) {
                if ($cm->currency === 'SYP') {
                    $openingSyp += $signed;
                    $openingSypHistUsd += Money::toUsd($signed, 'SYP', $cmRate);
                } else {
                    $openingUsd += $signed;
                }
                continue;
            }

            $totalPaidUsd += (float) ($cm->amount_usd > 0 ? $cm->amount_usd : Money::toUsd((float) $cm->amount, $cm->currency, $cmRate));

            $events[] = $this->event($cm->date, $desc, $debit, $credit, $cm->currency, (bool) $cm->affects_balance, [
                'sort_order' => 2,
                'record_id' => (int) $cm->id,
                'kind' => 'cash',
                'voucher_number' => $cm->voucher_number,
                'exchange_rate' => $cmRate,
                'items' => [],
            ]);
        }

        usort($events, function ($a, $b) {
            $d = strcmp($a['date'], $b['date']);
            if ($d !== 0) {
                return $d;
            }
            $o = ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
            if ($o !== 0) {
                return $o;
            }
            return ($a['record_id'] ?? 0) <=> ($b['record_id'] ?? 0);
        });

        $balUsd = $openingUsd;
        $balSyp = $openingSyp;
        $sypHistoricalUsd = $openingSypHistUsd;
        $rows = [];

        foreach ($events as $ev) {
            $signed = $isCustomer ? ($ev['debit'] - $ev['credit']) : ($ev['credit'] - $ev['debit']);
            $evRate = (float) (($ev['exchange_rate'] ?? 0) > 0 ? $ev['exchange_rate'] : $rate);

            if ($ev['currency'] === 'SYP') {
                $balSyp += $signed;
                $sypHistoricalUsd += Money::toUsd($signed, 'SYP', $evRate);
            } else {
                $balUsd += $signed;
            }

            $ev['balance_usd'] = round($balUsd, 2);
            $ev['balance_syp'] = round($balSyp, 2);
            $ev['balance_total_usd'] = round($balUsd + ($rate > 0 ? ($balSyp / $rate) : 0.0), 2);
            $rows[] = $ev;
        }

        $finalSypAtCurrentUsd = $rate > 0 ? round($balSyp / $rate, 2) : 0.0;
        $sypFxDiffUsd = $isCustomer
            ? round($finalSypAtCurrentUsd - $sypHistoricalUsd, 2)
            : round($sypHistoricalUsd - $finalSypAtCurrentUsd, 2);

        return [
            'contact' => $contact,
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'current_rate' => $rate,
            'base_currency' => self::BASE_CURRENCY,
            'opening_usd' => round($openingUsd, 2),
            'opening_syp' => round($openingSyp, 2),
            'final_balance_usd' => round($balUsd, 2),
            'final_balance_syp' => round($balSyp, 2),
            'final_balance_syp_usd' => $finalSypAtCurrentUsd,
            'final_balance_total_usd' => round($balUsd + $finalSypAtCurrentUsd, 2),
            'syp_historical_usd' => round($sypHistoricalUsd, 2),
            'syp_fx_diff_usd' => $sypFxDiffUsd,
            'total_invoices_usd' => round($totalInvoicesUsd, 2),
            'total_returns_usd' => round($totalReturnsUsd, 2),
            'total_paid_usd' => round($totalPaidUsd, 2),
            'invoice_count' => $invoiceCount,
        ];
    }

    public function getInventoryValuation(string $shopId): array
    {
        $items = Item::where('shop_id', $shopId)->get();
        $adjustments = $this->itemCostAdjustments($shopId);
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
            $grossPurchasedValUsd = $val('purchase') + $headerVal('purchase');
            $purchaseDiscountUsd = $adjustments['purchase_discount'][$item->id] ?? 0.0;
            $shippingCostUsd = $adjustments['shipping_cost'][$item->id] ?? 0.0;
            $salesDiscountUsd = $adjustments['sales_discount'][$item->id] ?? 0.0;

            // Net landed purchase value = gross lines - earned purchase discount + linked shipping cost
            // Note: header-only legacy transactions already store net total_usd, so purchase_discount only applies to lined transactions in adjustments
            $purchasedValUsd = max(0, $grossPurchasedValUsd - $purchaseDiscountUsd + $shippingCostUsd);

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

            $netSalesUsd = (($val('sale') + $headerVal('sale')) - $salesDiscountUsd)
                - ($val('sale_return') + $headerVal('sale_return'));

            $reports[] = [
                'item' => $item,
                'opening_qty' => $openingQty,
                'purchased_qty' => $purchasedQty,
                'sold_qty' => $soldQty,
                'sales_return_qty' => $salesReturnsQty,
                'purchase_return_qty' => $purchaseReturnsQty,
                'current_stock' => $currentStock,
                'shipping_cost_usd' => round($shippingCostUsd, 2),
                'purchase_discount_usd' => round($purchaseDiscountUsd, 2),
                'wac_usd' => round($wacUsd, 2),
                'stock_value_usd' => round(max(0, $currentStock * $wacUsd), 2),
                'is_low_stock' => $currentStock <= (float) $item->alert_limit,
                'net_sales_usd' => round($netSalesUsd, 2),
                'cogs_usd' => round($soldQty * $wacUsd, 2),
            ];
        }

        return $reports;
    }

    private function itemCostAdjustments(string $shopId): array
    {
        $purchaseDiscount = [];
        $shippingCost = [];
        $salesDiscount = [];

        $purchases = Transaction::with('lines')
            ->where('shop_id', $shopId)
            ->where('type', 'purchase')
            ->get()
            ->keyBy('id');

        foreach ($purchases as $tx) {
            if ($tx->lines->count() > 0 && (float) $tx->discount_usd > 0) {
                $this->allocateAmountToTransaction($tx, (float) $tx->discount_usd, $purchaseDiscount);
            }
        }

        $sales = Transaction::with('lines')
            ->where('shop_id', $shopId)
            ->where('type', 'sale')
            ->where('discount_usd', '>', 0)
            ->get();

        foreach ($sales as $tx) {
            if ($tx->lines->count() > 0) {
                $this->allocateAmountToTransaction($tx, (float) $tx->discount_usd, $salesDiscount);
            }
        }

        $shippings = ShippingExpense::where('shop_id', $shopId)->get();
        $unlinkedShippingUsd = 0.0;
        foreach ($shippings as $ship) {
            $amtUsd = (float) $ship->amount_usd;
            if ($amtUsd <= 0) {
                continue;
            }
            if ($ship->transaction_id && $purchases->has($ship->transaction_id)) {
                $this->allocateAmountToTransaction($purchases->get($ship->transaction_id), $amtUsd, $shippingCost);
            } else {
                $unlinkedShippingUsd += $amtUsd;
            }
        }

        $unallocatedShippingUsd = 0.0;
        if ($unlinkedShippingUsd > 0) {
            $totalPurchasesUsd = (float) $purchases->sum('total_usd');
            if ($totalPurchasesUsd > 0) {
                foreach ($purchases as $tx) {
                    $share = $unlinkedShippingUsd * ((float) $tx->total_usd / $totalPurchasesUsd);
                    $this->allocateAmountToTransaction($tx, $share, $shippingCost);
                }
            } else {
                $unallocatedShippingUsd = $unlinkedShippingUsd;
            }
        }

        return [
            'purchase_discount' => $purchaseDiscount,
            'shipping_cost' => $shippingCost,
            'sales_discount' => $salesDiscount,
            'unallocated_shipping_usd' => $unallocatedShippingUsd,
        ];
    }

    private function allocateAmountToTransaction(Transaction $tx, float $amountUsd, array &$bucket): void
    {
        if ($amountUsd <= 0) {
            return;
        }
        if ($tx->lines->count() > 0) {
            $baseSum = (float) $tx->lines->sum('total_usd');
            $qtySum = (float) $tx->lines->sum('qty');
            foreach ($tx->lines as $line) {
                if ($baseSum > 0) {
                    $ratio = (float) $line->total_usd / $baseSum;
                } elseif ($qtySum > 0) {
                    $ratio = (float) $line->qty / $qtySum;
                } else {
                    $ratio = 1 / $tx->lines->count();
                }
                $bucket[$line->item_id] = ($bucket[$line->item_id] ?? 0.0) + ($amountUsd * $ratio);
            }
            return;
        }

        if ($tx->item_id) {
            $bucket[$tx->item_id] = ($bucket[$tx->item_id] ?? 0.0) + $amountUsd;
        }
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

    public function getFxDifferenceReport(string $shopId, ?float $currentRate = null, ?string $from = null, ?string $to = null): array
    {
        $safeRate = $this->resolveShopRate($shopId, $currentRate);
        $inRange = function ($date) use ($from, $to): bool {
            $d = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date;
            if ($from && $d < $from) {
                return false;
            }
            if ($to && $d > $to) {
                return false;
            }
            return true;
        };

        $rows = [];

        // 1. Realized FX from Currency Exchanges
        $exchanges = CurrencyExchange::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $realizedExchangeFxUsd = 0.0;
        foreach ($exchanges as $ex) {
            $fx = $ex->fxDiffUsd($safeRate);
            $realizedExchangeFxUsd += $fx;

            if ($inRange($ex->date)) {
                $sypAmt = $ex->direction === 'syp_to_usd' ? (float) $ex->from_amount : (float) $ex->to_amount;
                $histUsd = $ex->direction === 'syp_to_usd'
                    ? (float) $ex->to_amount
                    : (float) $ex->from_amount;
                $currUsd = round($sypAmt / $safeRate, 2);
                $rows[] = [
                    'date' => $ex->date->format('Y-m-d'),
                    'module' => 'exchange',
                    'module_label' => 'تحويل عملة (محقق)',
                    'reference' => $ex->reference_number ?: 'EX-' . $ex->id,
                    'party' => $ex->direction === 'syp_to_usd' ? 'ل.س ← دولار' : 'دولار ← ل.س',
                    'amount_syp' => round($sypAmt, 2),
                    'op_rate' => (float) $ex->exchange_rate,
                    'current_rate' => $safeRate,
                    'historical_usd' => round($histUsd, 2),
                    'current_usd' => $currUsd,
                    'fx_diff_usd' => $fx,
                    'realized' => true,
                    'notes' => $ex->notes,
                ];
            }
        }

        // 2. SYP Cashbox Total FX (Realized + Unrealized without double-counting)
        $sypMovements = CashMovement::withoutGlobalScopes()
            ->with('contact')
            ->where('shop_id', $shopId)
            ->where('currency', 'SYP')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $sypIn = 0.0;
        $sypOut = 0.0;
        $sypInUsd = 0.0;
        $sypOutUsd = 0.0;

        foreach ($sypMovements as $cm) {
            $amt = (float) $cm->amount;
            $cmRate = (float) ($cm->exchange_rate > 0 ? $cm->exchange_rate : $safeRate);
            $usd = (float) ($cm->amount_usd > 0 ? $cm->amount_usd : Money::toUsd($amt, 'SYP', $cmRate));
            $isIn = in_array($cm->type, ['opening', 'receipt', 'purchase_return_cash'], true);
            if ($isIn) {
                $sypIn += $amt;
                $sypInUsd += $usd;
            } else {
                $sypOut += $amt;
                $sypOutUsd += $usd;
            }
        }

        $linkedTxIds = $sypMovements->where('source_type', 'transaction')->pluck('source_id')->filter()->map(fn ($id) => (int) $id)->all();
        $unlinkedCashSypTxs = Transaction::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('currency', 'SYP')
            ->where(function ($q) {
                $q->where('payment_type', 'cash')->orWhere('paid_amount', '>', 0);
            })
            ->when(count($linkedTxIds), fn ($q) => $q->whereNotIn('id', $linkedTxIds))
            ->get();

        foreach ($unlinkedCashSypTxs as $tx) {
            $paid = (float) ($tx->paid_amount ?: ($tx->payment_type === 'cash' ? $tx->total : 0));
            if ($paid <= 0) {
                continue;
            }
            $txRate = (float) ($tx->exchange_rate > 0 ? $tx->exchange_rate : $safeRate);
            $paidUsd = (float) ($tx->paid_amount_usd > 0 ? $tx->paid_amount_usd : Money::toUsd($paid, 'SYP', $txRate));
            if (in_array($tx->type, ['sale', 'purchase_return'], true)) {
                $sypIn += $paid;
                $sypInUsd += $paidUsd;
            } elseif (in_array($tx->type, ['purchase', 'sale_return'], true)) {
                $sypOut += $paid;
                $sypOutUsd += $paidUsd;
            }
        }

        $sypCashBalance = $sypIn - $sypOut;
        $sypCashCurrentUsd = round($sypCashBalance / $safeRate, 2);
        $sypCashHistoricalUsd = round($sypInUsd - $sypOutUsd, 2);
        $sypCashTotalFxUsd = round($sypCashCurrentUsd - $sypCashHistoricalUsd, 2);
        // Split cleanly so realized_exchange_fx + cashbox_unrealized_fx === syp_cash_total_fx (zero double-counting)
        $cashboxUnrealizedFxUsd = round($sypCashTotalFxUsd - $realizedExchangeFxUsd, 2);

        // 3. Customer SYP Receivables FX & 4. Supplier SYP Payables FX
        $customers = Contact::withoutGlobalScopes()->where('shop_id', $shopId)->where('type', 'customer')->get();
        $suppliers = Contact::withoutGlobalScopes()->where('shop_id', $shopId)->where('type', 'supplier')->get();

        $receivablesFxUsd = 0.0;
        $receivablesSyp = 0.0;
        foreach ($customers as $c) {
            $st = $this->getContactStatement($c, null, null, $safeRate);
            $receivablesFxUsd += $st['syp_fx_diff_usd'];
            $receivablesSyp += $st['final_balance_syp'];
        }

        $payablesFxUsd = 0.0;
        $payablesSyp = 0.0;
        foreach ($suppliers as $c) {
            $st = $this->getContactStatement($c, null, null, $safeRate);
            $payablesFxUsd += $st['syp_fx_diff_usd'];
            $payablesSyp += $st['final_balance_syp'];
        }

        $netFxDiffUsd = round($realizedExchangeFxUsd + $cashboxUnrealizedFxUsd + $receivablesFxUsd + $payablesFxUsd, 2);

        // Operation-level SYP audit rows across ALL operations (Invoices, Cashbox, Expenses, Salaries, Shipping, Withdrawals, Employee Movements)
        $sypTxs = Transaction::withoutGlobalScopes()
            ->with('contact')
            ->where('shop_id', $shopId)
            ->where('currency', 'SYP')
            ->orderByDesc('date')
            ->get();

        foreach ($sypTxs as $tx) {
            if (!$inRange($tx->date)) {
                continue;
            }
            $amtSyp = (float) $tx->total;
            $opRate = (float) ($tx->exchange_rate > 0 ? $tx->exchange_rate : $safeRate);
            $histUsd = (float) ($tx->total_usd > 0 ? $tx->total_usd : Money::toUsd($amtSyp, 'SYP', $opRate));
            $currUsd = round($amtSyp / $safeRate, 2);
            $isAssetInflow = in_array($tx->type, ['sale', 'purchase_return'], true);
            $diff = $isAssetInflow ? round($currUsd - $histUsd, 2) : round($histUsd - $currUsd, 2);

            $label = match ($tx->type) {
                'sale' => 'فاتورة مبيعات',
                'purchase' => 'فاتورة مشتريات',
                'sale_return' => 'مرتجع مبيعات',
                'purchase_return' => 'مرتجع مشتريات',
                default => $tx->type,
            };

            $rows[] = [
                'date' => $tx->date->format('Y-m-d'),
                'module' => 'transaction',
                'module_label' => $label,
                'reference' => $tx->invoice_number,
                'party' => $tx->contact->name ?? 'نقدي',
                'amount_syp' => round($amtSyp, 2),
                'op_rate' => $opRate,
                'current_rate' => $safeRate,
                'historical_usd' => round($histUsd, 2),
                'current_usd' => $currUsd,
                'fx_diff_usd' => $diff,
                'realized' => false,
                'notes' => $tx->notes,
            ];
        }

        $sypExpenses = Expense::withoutGlobalScopes()->where('shop_id', $shopId)->where('currency', 'SYP')->get();
        foreach ($sypExpenses as $exp) {
            if (!$inRange($exp->date)) {
                continue;
            }
            $amtSyp = (float) $exp->amount;
            $opRate = (float) ($exp->exchange_rate > 0 ? $exp->exchange_rate : $safeRate);
            $histUsd = (float) ($exp->amount_usd > 0 ? $exp->amount_usd : Money::toUsd($amtSyp, 'SYP', $opRate));
            $currUsd = round($amtSyp / $safeRate, 2);
            $rows[] = [
                'date' => $exp->date->format('Y-m-d'),
                'module' => 'expense',
                'module_label' => 'مصروف عام',
                'reference' => $exp->voucher_number ?: 'EXP-' . $exp->id,
                'party' => $exp->expense_type ?: 'مصروف',
                'amount_syp' => round($amtSyp, 2),
                'op_rate' => $opRate,
                'current_rate' => $safeRate,
                'historical_usd' => round($histUsd, 2),
                'current_usd' => $currUsd,
                'fx_diff_usd' => round($histUsd - $currUsd, 2),
                'realized' => false,
                'notes' => $exp->description ?: $exp->notes,
            ];
        }

        $sypShippings = ShippingExpense::withoutGlobalScopes()->where('shop_id', $shopId)->where('currency', 'SYP')->get();
        foreach ($sypShippings as $sh) {
            if (!$inRange($sh->date)) {
                continue;
            }
            $amtSyp = (float) $sh->amount;
            $opRate = (float) ($sh->exchange_rate > 0 ? $sh->exchange_rate : $safeRate);
            $histUsd = (float) ($sh->amount_usd > 0 ? $sh->amount_usd : Money::toUsd($amtSyp, 'SYP', $opRate));
            $currUsd = round($amtSyp / $safeRate, 2);
            $rows[] = [
                'date' => $sh->date->format('Y-m-d'),
                'module' => 'shipping',
                'module_label' => 'أجور شحن',
                'reference' => $sh->voucher_number ?: 'SHP-' . $sh->id,
                'party' => $sh->carrier_name ?: 'شحن مشتريات',
                'amount_syp' => round($amtSyp, 2),
                'op_rate' => $opRate,
                'current_rate' => $safeRate,
                'historical_usd' => round($histUsd, 2),
                'current_usd' => $currUsd,
                'fx_diff_usd' => round($histUsd - $currUsd, 2),
                'realized' => false,
                'notes' => $sh->description ?: $sh->notes,
            ];
        }

        $sypSalaries = SalaryRecord::withoutGlobalScopes()->with('employee')->where('shop_id', $shopId)->where('currency', 'SYP')->get();
        foreach ($sypSalaries as $sal) {
            if (!$inRange($sal->date)) {
                continue;
            }
            $amtSyp = (float) $sal->net;
            $opRate = (float) ($sal->exchange_rate > 0 ? $sal->exchange_rate : $safeRate);
            $histUsd = (float) ($sal->amount_usd > 0 ? $sal->amount_usd : Money::toUsd($amtSyp, 'SYP', $opRate));
            $currUsd = round($amtSyp / $safeRate, 2);
            $rows[] = [
                'date' => $sal->date->format('Y-m-d'),
                'module' => 'salary',
                'module_label' => 'راتب موظف',
                'reference' => $sal->voucher_number ?: 'SAL-' . $sal->id,
                'party' => $sal->employee->name ?? 'موظف',
                'amount_syp' => round($amtSyp, 2),
                'op_rate' => $opRate,
                'current_rate' => $safeRate,
                'historical_usd' => round($histUsd, 2),
                'current_usd' => $currUsd,
                'fx_diff_usd' => round($histUsd - $currUsd, 2),
                'realized' => false,
                'notes' => $sal->notes,
            ];
        }

        $sypWithdrawals = PartnerWithdrawal::withoutGlobalScopes()->with('partner')->where('shop_id', $shopId)->where('currency', 'SYP')->get();
        foreach ($sypWithdrawals as $wd) {
            if (!$inRange($wd->date)) {
                continue;
            }
            $amtSyp = (float) $wd->amount;
            $opRate = (float) ($wd->exchange_rate > 0 ? $wd->exchange_rate : $safeRate);
            $histUsd = (float) ($wd->amount_usd > 0 ? $wd->amount_usd : Money::toUsd($amtSyp, 'SYP', $opRate));
            $currUsd = round($amtSyp / $safeRate, 2);
            $rows[] = [
                'date' => $wd->date->format('Y-m-d'),
                'module' => 'withdrawal',
                'module_label' => 'مسحوب شريك',
                'reference' => $wd->voucher_number ?: 'WD-' . $wd->id,
                'party' => $wd->partner->name ?? 'شريك',
                'amount_syp' => round($amtSyp, 2),
                'op_rate' => $opRate,
                'current_rate' => $safeRate,
                'historical_usd' => round($histUsd, 2),
                'current_usd' => $currUsd,
                'fx_diff_usd' => round($histUsd - $currUsd, 2),
                'realized' => false,
                'notes' => $wd->description ?: $wd->notes,
            ];
        }

        $sypEmpMoves = EmployeeMovement::withoutGlobalScopes()->with('employee')->where('shop_id', $shopId)->where('currency', 'SYP')->get();
        foreach ($sypEmpMoves as $em) {
            if (!$inRange($em->date)) {
                continue;
            }
            $amtSyp = (float) $em->amount;
            $opRate = (float) ($em->exchange_rate > 0 ? $em->exchange_rate : $safeRate);
            $histUsd = (float) ($em->amount_usd > 0 ? $em->amount_usd : Money::toUsd($amtSyp, 'SYP', $opRate));
            $currUsd = round($amtSyp / $safeRate, 2);
            $rows[] = [
                'date' => $em->date->format('Y-m-d'),
                'module' => 'employee',
                'module_label' => 'حركة موظف',
                'reference' => $em->voucher_number ?: 'EMP-' . $em->id,
                'party' => $em->employee->name ?? 'موظف',
                'amount_syp' => round($amtSyp, 2),
                'op_rate' => $opRate,
                'current_rate' => $safeRate,
                'historical_usd' => round($histUsd, 2),
                'current_usd' => $currUsd,
                'fx_diff_usd' => round($histUsd - $currUsd, 2),
                'realized' => false,
                'notes' => $em->notes,
            ];
        }

        // Direct / voucher SYP cashbox entries (unlinked to above domain tables)
        $linkedSources = ['transaction', 'expense', 'shipping_expense', 'salary', 'withdrawal', 'employee_movement', 'currency_exchange_in', 'currency_exchange_out'];
        foreach ($sypMovements as $cm) {
            if (!$inRange($cm->date)) {
                continue;
            }
            if ($cm->source_type && in_array($cm->source_type, $linkedSources, true)) {
                continue;
            }
            $amtSyp = (float) $cm->amount;
            $opRate = (float) ($cm->exchange_rate > 0 ? $cm->exchange_rate : $safeRate);
            $histUsd = (float) ($cm->amount_usd > 0 ? $cm->amount_usd : Money::toUsd($amtSyp, 'SYP', $opRate));
            $currUsd = round($amtSyp / $safeRate, 2);
            $isIn = in_array($cm->type, ['opening', 'receipt', 'purchase_return_cash'], true);
            $rows[] = [
                'date' => $cm->date->format('Y-m-d'),
                'module' => 'cashbox',
                'module_label' => $isIn ? 'سند قبض / وارد صندوق' : 'سند صرف / صادر صندوق',
                'reference' => $cm->voucher_number ?: 'CM-' . $cm->id,
                'party' => $cm->contact->name ?? ($cm->category ?: 'الصندوق'),
                'amount_syp' => round($amtSyp, 2),
                'op_rate' => $opRate,
                'current_rate' => $safeRate,
                'historical_usd' => round($histUsd, 2),
                'current_usd' => $currUsd,
                'fx_diff_usd' => $isIn ? round($currUsd - $histUsd, 2) : round($histUsd - $currUsd, 2),
                'realized' => false,
                'notes' => $cm->notes,
            ];
        }

        usort($rows, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return [
            'base_currency' => self::BASE_CURRENCY,
            'current_rate' => $safeRate,
            'from' => $from,
            'to' => $to,
            'realized_exchange_fx_usd' => round($realizedExchangeFxUsd, 2),
            'cashbox_unrealized_fx_usd' => $cashboxUnrealizedFxUsd,
            'cashbox_total_fx_usd' => $sypCashTotalFxUsd,
            'syp_cash_balance' => round($sypCashBalance, 2),
            'syp_cash_current_usd' => $sypCashCurrentUsd,
            'syp_cash_historical_usd' => $sypCashHistoricalUsd,
            'receivables_syp' => round($receivablesSyp, 2),
            'receivables_fx_usd' => round($receivablesFxUsd, 2),
            'payables_syp' => round($payablesSyp, 2),
            'payables_fx_usd' => round($payablesFxUsd, 2),
            'net_fx_diff_usd' => $netFxDiffUsd,
            'fx_gain_usd' => $netFxDiffUsd > 0 ? $netFxDiffUsd : 0.0,
            'fx_loss_usd' => $netFxDiffUsd < 0 ? abs($netFxDiffUsd) : 0.0,
            'operations_count' => count($rows),
            'rows' => $rows,
        ];
    }

    public function getProfitAndLossReport(string $shopId, ?float $currentRate = null): array
    {
        $rate = $this->resolveShopRate($shopId, $currentRate);
        $stockReports = $this->getInventoryValuation($shopId);
        $adjustments = $this->itemCostAdjustments($shopId);
        $wacMap = [];
        $openingInventoryUsd = 0.0;
        $closingInventoryUsd = 0.0;
        foreach ($stockReports as $sr) {
            $wacMap[$sr['item']->id] = $sr['wac_usd'];
            $openingInventoryUsd += ((float) ($sr['item']->opening_qty ?? 0)) * ((float) ($sr['item']->opening_cost_usd ?? 0));
            $closingInventoryUsd += (float) $sr['stock_value_usd'];
        }

        $sales = Transaction::with('lines')->where('shop_id', $shopId)->where('type', 'sale')->get();
        $salesReturns = Transaction::with('lines')->where('shop_id', $shopId)->where('type', 'sale_return')->get();
        $purchases = Transaction::where('shop_id', $shopId)->where('type', 'purchase')->get();
        $purchaseReturns = Transaction::where('shop_id', $shopId)->where('type', 'purchase_return')->get();

        $salesDiscountUsd = (float) $sales->sum('discount_usd');
        $grossSalesUsd = (float) $sales->sum(function ($s) {
            return (float) $s->subtotal_usd > 0 ? (float) $s->subtotal_usd : ((float) $s->total_usd + (float) $s->discount_usd);
        });
        $totalSalesReturnsUsd = (float) $salesReturns->sum('total_usd');
        $netSalesUsd = $grossSalesUsd - $salesDiscountUsd - $totalSalesReturnsUsd;

        $purchaseDiscountUsd = (float) $purchases->sum('discount_usd');
        $grossPurchasesUsd = (float) $purchases->sum(function ($p) {
            return (float) $p->subtotal_usd > 0 ? (float) $p->subtotal_usd : ((float) $p->total_usd + (float) $p->discount_usd);
        });
        $purchaseReturnsUsd = (float) $purchaseReturns->sum('total_usd');
        $totalShippingUsd = (float) ShippingExpense::where('shop_id', $shopId)->sum('amount_usd');
        $netPurchasesUsd = $grossPurchasesUsd - $purchaseDiscountUsd - $purchaseReturnsUsd + $totalShippingUsd;

        $cogsUsd = $adjustments['unallocated_shipping_usd'] ?? 0.0;
        foreach ($sales as $s) {
            $cogsUsd += $this->cogsOf($s, $wacMap);
        }
        foreach ($salesReturns as $sr) {
            $cogsUsd -= $this->cogsOf($sr, $wacMap);
        }

        $grossProfitUsd = $netSalesUsd - $cogsUsd;

        $expenseRows = Expense::withoutGlobalScopes()->where('shop_id', $shopId)->get(['id', 'cash_movement_id', 'amount_usd']);
        $linkedExpenseIds = $expenseRows->pluck('id')->map(fn ($id) => (int) $id)->all();
        $linkedExpenseCashIds = $expenseRows->pluck('cash_movement_id')->filter()->map(fn ($id) => (int) $id)->all();
        $manualCashExpensesUsd = (float) CashMovement::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('type', 'expense')
            ->get(['id', 'source_type', 'source_id', 'amount_usd'])
            ->reject(fn ($m) => ($m->id && in_array((int) $m->id, $linkedExpenseCashIds, true))
                || ($m->source_type === 'expense' && $m->source_id && in_array((int) $m->source_id, $linkedExpenseIds, true)))
            ->sum('amount_usd');
        $totalExpensesUsd = (float) $expenseRows->sum('amount_usd') + $manualCashExpensesUsd;

        $salaryRows = SalaryRecord::withoutGlobalScopes()->where('shop_id', $shopId)->get(['id', 'cash_movement_id', 'amount_usd']);
        $linkedSalaryIds = $salaryRows->pluck('id')->map(fn ($id) => (int) $id)->all();
        $linkedSalaryCashIds = $salaryRows->pluck('cash_movement_id')->filter()->map(fn ($id) => (int) $id)->all();
        $manualCashSalariesUsd = (float) CashMovement::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('type', 'salary')
            ->get(['id', 'source_type', 'source_id', 'amount_usd'])
            ->reject(fn ($m) => ($m->id && in_array((int) $m->id, $linkedSalaryCashIds, true))
                || ($m->source_type === 'salary' && $m->source_id && in_array((int) $m->source_id, $linkedSalaryIds, true))
                || $m->source_type === 'employee_movement')
            ->sum('amount_usd');
        $totalSalariesUsd = (float) $salaryRows->sum('amount_usd') + $manualCashSalariesUsd;

        $allWithdrawals = PartnerWithdrawal::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->get(['id', 'cash_movement_id', 'classification_status', 'nature', 'amount_usd']);
        $linkedWithdrawalIds = $allWithdrawals->pluck('id')->map(fn ($id) => (int) $id)->all();
        $linkedWithdrawalCashIds = $allWithdrawals->pluck('cash_movement_id')->filter()->map(fn ($id) => (int) $id)->all();
        $finalStructuredWithdrawalsUsd = (float) $allWithdrawals
            ->where('classification_status', 'مصنف')
            ->where('nature', 'مسحوب شخصي نهائي')
            ->sum('amount_usd');
        $manualCashWithdrawalsUsd = (float) CashMovement::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('type', 'withdrawal')
            ->get(['id', 'source_type', 'source_id', 'amount_usd'])
            ->reject(fn ($m) => ($m->id && in_array((int) $m->id, $linkedWithdrawalCashIds, true))
                || ($m->source_type === 'withdrawal' && $m->source_id && in_array((int) $m->source_id, $linkedWithdrawalIds, true)))
            ->sum('amount_usd');
        $totalWithdrawalsUsd = $finalStructuredWithdrawalsUsd + $manualCashWithdrawalsUsd;

        $fx = $this->getFxDifferenceReport($shopId, $rate);
        $fxDiffUsd = (float) $fx['net_fx_diff_usd'];
        $fxGainUsd = $fxDiffUsd > 0 ? $fxDiffUsd : 0.0;
        $fxLossUsd = $fxDiffUsd < 0 ? abs($fxDiffUsd) : 0.0;

        $operatingCostsUsd = $totalExpensesUsd + $totalSalariesUsd;
        $operatingProfitBeforeFxUsd = $grossProfitUsd - $operatingCostsUsd;
        $estimatedNetProfitUsd = $operatingProfitBeforeFxUsd + $fxDiffUsd;
        $profitMargin = $netSalesUsd > 0 ? ($estimatedNetProfitUsd / $netSalesUsd) * 100 : 0.0;

        // T-Account balanced totals for P&L
        $pnlDebitBase = ($grossProfitUsd < 0 ? abs($grossProfitUsd) : 0.0)
            + $totalExpensesUsd
            + $totalSalariesUsd
            + $fxLossUsd;
        $pnlCreditBase = ($grossProfitUsd > 0 ? $grossProfitUsd : 0.0)
            + $fxGainUsd;
        $pnlSideTotal = max($pnlDebitBase, $pnlCreditBase);

        return [
            'base_currency' => self::BASE_CURRENCY,
            'current_rate' => $rate,
            'opening_inventory_usd' => round($openingInventoryUsd, 2),
            'closing_inventory_usd' => round($closingInventoryUsd, 2),
            'gross_purchases_usd' => round($grossPurchasesUsd, 2),
            'purchase_returns_usd' => round($purchaseReturnsUsd, 2),
            'net_purchases_usd' => round($netPurchasesUsd, 2),
            'total_sales_usd' => round($grossSalesUsd, 2),
            'sales_discount_usd' => round($salesDiscountUsd, 2),
            'purchase_discount_usd' => round($purchaseDiscountUsd, 2),
            'total_shipping_usd' => round($totalShippingUsd, 2),
            'total_sales_returns_usd' => round($totalSalesReturnsUsd, 2),
            'net_sales_usd' => round($netSalesUsd, 2),
            'cogs_usd' => round($cogsUsd, 2),
            'gross_profit_usd' => round($grossProfitUsd, 2),
            'total_expenses_usd' => round($totalExpensesUsd, 2),
            'total_salaries_usd' => round($totalSalariesUsd, 2),
            'total_withdrawals_usd' => round($totalWithdrawalsUsd, 2),
            'fx_diff_usd' => round($fxDiffUsd, 2),
            'fx_gain_usd' => round($fxGainUsd, 2),
            'fx_loss_usd' => round($fxLossUsd, 2),
            'fx_breakdown' => [
                'realized_exchange_fx_usd' => $fx['realized_exchange_fx_usd'],
                'cashbox_unrealized_fx_usd' => $fx['cashbox_unrealized_fx_usd'],
                'receivables_fx_usd' => $fx['receivables_fx_usd'],
                'payables_fx_usd' => $fx['payables_fx_usd'],
            ],
            'operating_profit_before_fx_usd' => round($operatingProfitBeforeFxUsd, 2),
            'estimated_net_profit_usd' => round($estimatedNetProfitUsd, 2),
            'net_profit_usd' => $estimatedNetProfitUsd > 0 ? round($estimatedNetProfitUsd, 2) : 0.0,
            'net_loss_usd' => $estimatedNetProfitUsd < 0 ? round(abs($estimatedNetProfitUsd), 2) : 0.0,
            'pnl_debit_total_usd' => round($pnlSideTotal, 2),
            'pnl_credit_total_usd' => round($pnlSideTotal, 2),
            'profit_margin_percent' => round($profitMargin, 1),
        ];
    }

    public function getTradingAccount(string $shopId, ?float $currentRate = null): array
    {
        $pnl = $this->getProfitAndLossReport($shopId, $currentRate);

        $debitItems = [
            ['label' => 'بضاعة أول المدة (المخزون الافتتاحي)', 'amount_usd' => $pnl['opening_inventory_usd']],
            ['label' => 'إجمالي المشتريات', 'amount_usd' => $pnl['gross_purchases_usd']],
            ['label' => 'مصاريف أجور شحن المشتريات', 'amount_usd' => $pnl['total_shipping_usd']],
            ['label' => 'مردودات المبيعات', 'amount_usd' => $pnl['total_sales_returns_usd']],
            ['label' => 'حسم ممنوح على المبيعات', 'amount_usd' => $pnl['sales_discount_usd']],
        ];

        $creditItems = [
            ['label' => 'إجمالي المبيعات', 'amount_usd' => $pnl['total_sales_usd']],
            ['label' => 'مردودات المشتريات', 'amount_usd' => $pnl['purchase_returns_usd']],
            ['label' => 'حسم مكتسب على المشتريات', 'amount_usd' => $pnl['purchase_discount_usd']],
            ['label' => 'بضاعة آخر المدة (المخزون الختامي)', 'amount_usd' => $pnl['closing_inventory_usd']],
        ];

        $debitSubtotal = round(array_sum(array_column($debitItems, 'amount_usd')), 2);
        $creditSubtotal = round(array_sum(array_column($creditItems, 'amount_usd')), 2);
        $tradingResult = round($creditSubtotal - $debitSubtotal, 2);

        $grossProfitUsd = $tradingResult >= 0 ? $tradingResult : 0.0;
        $grossLossUsd = $tradingResult < 0 ? abs($tradingResult) : 0.0;
        $balancedTotal = round(max($debitSubtotal, $creditSubtotal), 2);

        $periodicCogs = round(
            $pnl['opening_inventory_usd']
            + $pnl['net_purchases_usd']
            - $pnl['closing_inventory_usd'],
            2
        );

        return [
            'base_currency' => self::BASE_CURRENCY,
            'current_rate' => $pnl['current_rate'],
            'debit_items' => $debitItems,
            'credit_items' => $creditItems,
            'debit_subtotal_usd' => $debitSubtotal,
            'credit_subtotal_usd' => $creditSubtotal,
            'gross_profit_usd' => $grossProfitUsd,
            'gross_loss_usd' => $grossLossUsd,
            'trading_result_usd' => $tradingResult,
            'debit_total_usd' => $balancedTotal,
            'credit_total_usd' => $balancedTotal,
            'net_sales_usd' => $pnl['net_sales_usd'],
            'net_purchases_usd' => $pnl['net_purchases_usd'],
            'periodic_cogs_usd' => $periodicCogs,
            'wac_cogs_usd' => $pnl['cogs_usd'],
            'wac_gross_profit_usd' => $pnl['gross_profit_usd'],
        ];
    }

    public function getBalanceSheet(string $shopId, float $rate): array
    {
        $safeRate = $this->resolveShopRate($shopId, $rate);
        $cash = $this->getCashboxSummary($shopId, $safeRate);
        $customers = Contact::where('type', 'customer')->get();
        $suppliers = Contact::where('type', 'supplier')->get();

        $arUsd = 0.0;
        $arSyp = 0.0;
        $apUsd = 0.0;
        $apSyp = 0.0;

        foreach ($customers as $c) {
            $s = $this->getContactStatement($c, null, null, $safeRate);
            $arUsd += $s['final_balance_usd'];
            $arSyp += $s['final_balance_syp'];
        }
        foreach ($suppliers as $c) {
            $s = $this->getContactStatement($c, null, null, $safeRate);
            $apUsd += $s['final_balance_usd'];
            $apSyp += $s['final_balance_syp'];
        }

        $cashUsd = round((float) $cash['usd']['balance'], 2);
        $cashSyp = round((float) $cash['syp']['balance'], 2);
        $cashSypUsd = round($cashSyp / $safeRate, 2);
        $totalCashUsd = round($cashUsd + $cashSypUsd, 2);

        $arUsd = round($arUsd, 2);
        $arSyp = round($arSyp, 2);
        $arSypUsd = round($arSyp / $safeRate, 2);
        $totalArUsd = round($arUsd + $arSypUsd, 2);

        $apUsd = round($apUsd, 2);
        $apSyp = round($apSyp, 2);
        $apSypUsd = round($apSyp / $safeRate, 2);
        $totalApUsd = round($apUsd + $apSypUsd, 2);

        $stock = round((float) collect($this->getInventoryValuation($shopId))->sum('stock_value_usd'), 2);
        $pnl = $this->getProfitAndLossReport($shopId, $safeRate);

        $totalAssetsUsd = round($totalCashUsd + $totalArUsd + $stock, 2);
        $totalLiabilitiesUsd = round($totalApUsd, 2);
        $netEquityUsd = round($totalAssetsUsd - $totalLiabilitiesUsd, 2);
        $profitUsd = round((float) $pnl['estimated_net_profit_usd'], 2);
        $withdrawalsUsd = round((float) $pnl['total_withdrawals_usd'], 2);
        // Capital & retained opening equity derived to reconcile Assets = Liabilities + Equity in Base USD
        $capitalUsd = round($netEquityUsd - $profitUsd + $withdrawalsUsd, 2);
        $totalLiabilitiesAndEquityUsd = round($totalLiabilitiesUsd + $capitalUsd + $profitUsd - $withdrawalsUsd, 2);
        $differenceUsd = round($totalAssetsUsd - $totalLiabilitiesAndEquityUsd, 2);

        return [
            'base_currency' => self::BASE_CURRENCY,
            'current_rate' => $safeRate,
            'cash_usd' => $cashUsd,
            'cash_syp' => $cashSyp,
            'cash_syp_usd' => $cashSypUsd,
            'total_cash_usd' => $totalCashUsd,
            'ar_usd' => $arUsd,
            'ar_syp' => $arSyp,
            'ar_syp_usd' => $arSypUsd,
            'total_ar_usd' => $totalArUsd,
            'inventory_usd' => $stock,
            'total_assets_usd' => $totalAssetsUsd,
            'ap_usd' => $apUsd,
            'ap_syp' => $apSyp,
            'ap_syp_usd' => $apSypUsd,
            'total_ap_usd' => $totalApUsd,
            'total_liabilities_usd' => $totalLiabilitiesUsd,
            'capital_usd' => $capitalUsd,
            'profit_usd' => $profitUsd,
            'fx_diff_usd' => $pnl['fx_diff_usd'],
            'withdrawals_usd' => $withdrawalsUsd,
            'net_equity_usd' => $netEquityUsd,
            'total_liabilities_and_equity_usd' => $totalLiabilitiesAndEquityUsd,
            'difference_usd' => $differenceUsd,
            'balanced' => abs($differenceUsd) < 0.01,
        ];
    }

    public function getSalesStatement(string $shopId, float $currentRate, array $filters = []): array
    {
        return $this->buildInvoiceStatement($shopId, 'sales', $currentRate, $filters);
    }

    public function getPurchasesStatement(string $shopId, float $currentRate, array $filters = []): array
    {
        return $this->buildInvoiceStatement($shopId, 'purchases', $currentRate, $filters);
    }

    private function buildInvoiceStatement(string $shopId, string $mode, float $currentRate, array $filters = []): array
    {
        $safeRate = $this->resolveShopRate($shopId, $currentRate);
        $isSales = $mode === 'sales';
        $mainType = $isSales ? 'sale' : 'purchase';
        $returnType = $isSales ? 'sale_return' : 'purchase_return';

        $query = Transaction::with(['contact', 'item', 'lines.item'])
            ->where('shop_id', $shopId)
            ->whereIn('type', [$mainType, $returnType]);

        if (!empty($filters['from'])) {
            $query->whereDate('date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('date', '<=', $filters['to']);
        }
        if (!empty($filters['contact_id'])) {
            $query->where('contact_id', (int) $filters['contact_id']);
        }
        if (!empty($filters['payment_type'])) {
            $query->where('payment_type', $filters['payment_type']);
        }
        if (!empty($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }
        if (!empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function ($q) use ($term) {
                $q->where('invoice_number', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%")
                    ->orWhereHas('contact', fn ($cq) => $cq->where('name', 'like', "%{$term}%"));
            });
        }

        $txs = $query->orderByDesc('date')->orderByDesc('id')->get();

        $shippingByTx = [];
        if (!$isSales) {
            $shippingRows = ShippingExpense::where('shop_id', $shopId)
                ->whereNotNull('transaction_id')
                ->get(['transaction_id', 'amount_usd']);
            foreach ($shippingRows as $sh) {
                $tid = (int) $sh->transaction_id;
                $shippingByTx[$tid] = ($shippingByTx[$tid] ?? 0.0) + (float) $sh->amount_usd;
            }
        }

        $rows = [];
        $grossUsd = 0.0;
        $discountUsd = 0.0;
        $returnsUsd = 0.0;
        $paidUsd = 0.0;
        $remainingUsd = 0.0;
        $shippingUsd = 0.0;
        $fxDiffUsd = 0.0;
        $mainCount = 0;
        $returnCount = 0;

        foreach ($txs as $tx) {
            $isReturn = $tx->type === $returnType;
            $txRate = (float) ($tx->exchange_rate > 0 ? $tx->exchange_rate : $safeRate);
            $subtotal = (float) ($tx->subtotal > 0 ? $tx->subtotal : ((float) $tx->total + (float) $tx->discount));
            $subtotalUsd = (float) ($tx->subtotal_usd > 0 ? $tx->subtotal_usd : ((float) $tx->total_usd + (float) $tx->discount_usd));
            $total = (float) $tx->total;
            $totalUsd = (float) $tx->total_usd;
            $paid = (float) ($tx->paid_amount ?: ($tx->payment_type === 'cash' ? $total : 0));
            $paid = max(0.0, min($paid, $total));
            $txPaidUsd = (float) ($tx->paid_amount_usd ?: Money::toUsd($paid, $tx->currency, $txRate));
            $remUsd = max(0.0, round($totalUsd - $txPaidUsd, 2));
            $txShippingUsd = !$isSales ? round($shippingByTx[(int) $tx->id] ?? 0.0, 2) : 0.0;

            $txFxDiff = 0.0;
            if ($tx->currency === 'SYP' && $total > 0) {
                $currValUsd = round($total / $safeRate, 2);
                $isInflow = in_array($tx->type, ['sale', 'purchase_return'], true);
                $txFxDiff = $isInflow ? round($currValUsd - $totalUsd, 2) : round($totalUsd - $currValUsd, 2);
            }

            if ($isReturn) {
                $returnsUsd += $totalUsd;
                $returnCount++;
            } else {
                $grossUsd += $subtotalUsd;
                $discountUsd += (float) $tx->discount_usd;
                $paidUsd += $txPaidUsd;
                $remainingUsd += $remUsd;
                $shippingUsd += $txShippingUsd;
                $mainCount++;
            }
            $fxDiffUsd += $txFxDiff;

            $items = [];
            if ($tx->lines->count() > 0) {
                foreach ($tx->lines as $line) {
                    $items[] = [
                        'item_name' => $line->item->item_name ?? 'صنف',
                        'qty' => (float) $line->qty,
                        'unit_price' => (float) $line->unit_price,
                        'total' => (float) $line->total,
                    ];
                }
            } elseif ($tx->item) {
                $items[] = [
                    'item_name' => $tx->item->item_name,
                    'qty' => (float) $tx->qty,
                    'unit_price' => (float) $tx->unit_price,
                    'total' => $subtotal,
                ];
            }

            $rows[] = [
                'id' => (int) $tx->id,
                'date' => $tx->date->format('Y-m-d'),
                'invoice_number' => $tx->invoice_number,
                'type' => $tx->type,
                'is_return' => $isReturn,
                'contact_id' => $tx->contact_id,
                'contact_name' => $tx->contact->name ?? ($isSales ? 'عميل نقدي' : 'مورد نقدي'),
                'payment_type' => $tx->payment_type,
                'currency' => $tx->currency,
                'exchange_rate' => $txRate,
                'subtotal' => round($subtotal, 2),
                'discount' => round((float) $tx->discount, 2),
                'discount_usd' => round((float) $tx->discount_usd, 2),
                'total' => round($total, 2),
                'total_usd' => round($totalUsd, 2),
                'paid' => round($paid, 2),
                'paid_usd' => round($txPaidUsd, 2),
                'remaining_usd' => $remUsd,
                'shipping_usd' => $txShippingUsd,
                'fx_diff_usd' => $txFxDiff,
                'items' => $items,
                'notes' => $tx->notes,
            ];
        }

        $netUsd = $isSales
            ? round($grossUsd - $discountUsd - $returnsUsd, 2)
            : round($grossUsd - $discountUsd - $returnsUsd + $shippingUsd, 2);

        return [
            'mode' => $mode,
            'base_currency' => self::BASE_CURRENCY,
            'current_rate' => $safeRate,
            'filters' => $filters,
            'rows' => $rows,
            'totals' => [
                'invoice_count' => $mainCount,
                'return_count' => $returnCount,
                'gross_usd' => round($grossUsd, 2),
                'discount_usd' => round($discountUsd, 2),
                'returns_usd' => round($returnsUsd, 2),
                'shipping_usd' => round($shippingUsd, 2),
                'net_usd' => $netUsd,
                'paid_usd' => round($paidUsd, 2),
                'remaining_usd' => round($remainingUsd, 2),
                'fx_diff_usd' => round($fxDiffUsd, 2),
            ],
        ];
    }

    public function getContactsSummaryStatement(string $shopId, string $type, float $currentRate, array $filters = []): array
    {
        $safeRate = $this->resolveShopRate($shopId, $currentRate);
        $query = Contact::where('shop_id', $shopId)->where('type', $type)->orderBy('name');

        if (!empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            });
        }

        $contacts = $query->get();
        $balanceFilter = $filters['balance_status'] ?? 'all';
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        $rows = [];
        $totals = [
            'count' => 0,
            'active_debtors_count' => 0,
            'total_invoices_usd' => 0.0,
            'total_returns_usd' => 0.0,
            'total_paid_usd' => 0.0,
            'balance_usd' => 0.0,
            'balance_syp' => 0.0,
            'balance_total_usd' => 0.0,
            'syp_fx_diff_usd' => 0.0,
        ];

        foreach ($contacts as $contact) {
            $st = $this->getContactStatement($contact, $from, $to, $safeRate);
            $hasBalance = abs($st['final_balance_total_usd']) >= 0.01;

            if ($balanceFilter === 'nonzero' && !$hasBalance) {
                continue;
            }
            if ($balanceFilter === 'zero' && $hasBalance) {
                continue;
            }

            $totals['count']++;
            if ($st['final_balance_total_usd'] > 0.01) {
                $totals['active_debtors_count']++;
            }
            $totals['total_invoices_usd'] += $st['total_invoices_usd'];
            $totals['total_returns_usd'] += $st['total_returns_usd'];
            $totals['total_paid_usd'] += $st['total_paid_usd'];
            $totals['balance_usd'] += $st['final_balance_usd'];
            $totals['balance_syp'] += $st['final_balance_syp'];
            $totals['balance_total_usd'] += $st['final_balance_total_usd'];
            $totals['syp_fx_diff_usd'] += $st['syp_fx_diff_usd'];

            $rows[] = [
                'contact' => $contact,
                'invoice_count' => $st['invoice_count'],
                'opening_usd' => $st['opening_usd'],
                'opening_syp' => $st['opening_syp'],
                'total_invoices_usd' => $st['total_invoices_usd'],
                'total_returns_usd' => $st['total_returns_usd'],
                'total_paid_usd' => $st['total_paid_usd'],
                'balance_usd' => $st['final_balance_usd'],
                'balance_syp' => $st['final_balance_syp'],
                'balance_syp_usd' => $st['final_balance_syp_usd'],
                'balance_total_usd' => $st['final_balance_total_usd'],
                'syp_fx_diff_usd' => $st['syp_fx_diff_usd'],
            ];
        }

        foreach (['total_invoices_usd', 'total_returns_usd', 'total_paid_usd', 'balance_usd', 'balance_syp', 'balance_total_usd', 'syp_fx_diff_usd'] as $k) {
            $totals[$k] = round($totals[$k], 2);
        }

        return [
            'type' => $type,
            'base_currency' => self::BASE_CURRENCY,
            'current_rate' => $safeRate,
            'filters' => $filters,
            'rows' => $rows,
            'totals' => $totals,
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

    private function event($date, string $desc, float $debit, float $credit, string $currency, bool $affects, array $extra = []): array
    {
        return array_merge([
            'date' => $date->format('Y-m-d'),
            'description' => $desc,
            'debit' => $debit,
            'credit' => $credit,
            'currency' => $currency,
            'affects' => $affects,
        ], $extra);
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
                'currency_exchange_in' => 0.0,
                'cash_purchases' => 0.0,
                'supplier_payments' => 0.0,
                'expenses' => 0.0,
                'shipping_expenses' => 0.0,
                'salaries' => 0.0,
                'withdrawals' => 0.0,
                'currency_exchange_out' => 0.0,
            ],
        ];
    }
}
