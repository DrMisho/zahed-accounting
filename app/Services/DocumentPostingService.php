<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CurrencyExchange;
use App\Models\EmployeeMovement;
use App\Models\Expense;
use App\Models\PartnerWithdrawal;
use App\Models\SalaryRecord;
use App\Models\ShippingExpense;
use App\Models\Transaction;
use App\Models\TransactionLine;
use App\Models\Voucher;

class DocumentPostingService
{
    public function __construct(
        protected AccountingService $accounting,
        protected CashLinkService $cash,
        protected JournalService $journal,
    ) {}

    public function saveInvoice(array $data, array $lines): Transaction
    {
        $shopId = $data['shop_id'] ?? current_shop_id() ?? session('impersonated_shop_id');
        $tx = Transaction::create($data);
        $locationId = $data['location_id'] ?? null;

        foreach ($lines as $line) {
            if (empty($line['item_id']) || (float) ($line['qty'] ?? 0) <= 0) {
                continue;
            }
            TransactionLine::create([
                'shop_id' => $shopId,
                'transaction_id' => $tx->id,
                'item_id' => $line['item_id'],
                'location_id' => $line['location_id'] ?? $locationId,
                'qty' => $line['qty'],
                'unit_price' => $line['unit_price'],
            ]);
        }

        $tx->refresh();
        $this->accounting->syncTransactionTotals($tx);
        $tx->refresh();
        $this->syncInvoiceCash($tx);
        $this->journal->postTransaction($tx->fresh('lines'), $this->accounting);

        return $tx->fresh(['lines', 'contact', 'item']);
    }

    public function destroyInvoice(Transaction $tx): void
    {
        $this->cash->forget($tx->shop_id, 'transaction', $tx->id);
        $this->journal->forgetSource($tx->shop_id, 'transaction', $tx->id);
        $tx->delete();
    }

    public function syncInvoiceCash(Transaction $tx): void
    {
        $paid = (float) $tx->paid_amount;
        if ($paid <= 0) {
            $this->cash->forget($tx->shop_id, 'transaction', $tx->id);
            return;
        }

        $type = in_array($tx->type, ['sale', 'purchase_return'], true) ? 'receipt' : 'payment';
        $this->cash->sync($tx->shop_id, 'transaction', $tx->id, [
            'date' => $tx->date,
            'type' => $type,
            'contact_id' => $tx->contact_id,
            'category' => 'فاتورة ' . $tx->invoice_number,
            'amount' => $paid,
            'currency' => $tx->currency,
            'exchange_rate' => $tx->exchange_rate,
            'notes' => 'أثر نقدي للفاتورة ' . $tx->invoice_number,
            'voucher_number' => $tx->invoice_number,
            'affects_balance' => false,
            'document_ref' => $tx->invoice_number,
        ]);
    }

    public function saveCashMovement(array $data): CashMovement
    {
        $data['source_type'] = null;
        $data['source_id'] = null;
        $cm = CashMovement::create($data);
        $this->journal->postCashMovement($cm->fresh());
        return $cm;
    }

    public function destroyCashMovement(CashMovement $cm): void
    {
        if (!$cm->source_type) {
            $this->journal->forgetSource($cm->shop_id, 'cash_movement', $cm->id);
        }
        $cm->delete();
    }

    public function saveExpense(array $data): Expense
    {
        $row = Expense::create($data);
        $cm = $this->cash->sync($row->shop_id, 'expense', $row->id, [
            'date' => $row->date,
            'type' => 'expense',
            'category' => $row->expense_type,
            'amount' => $row->amount,
            'currency' => $row->currency,
            'exchange_rate' => $row->exchange_rate,
            'notes' => $row->description,
            'voucher_number' => $row->voucher_number,
            'affects_balance' => false,
        ]);
        $row->update(['cash_movement_id' => $cm->id]);
        $this->journal->postExpense($row->fresh());
        return $row;
    }

    public function saveSalary(array $data): SalaryRecord
    {
        $row = SalaryRecord::create($data);
        $cm = $this->cash->sync($row->shop_id, 'salary', $row->id, [
            'date' => $row->date,
            'type' => 'salary',
            'category' => 'راتب ' . $row->month,
            'amount' => $row->net,
            'currency' => $row->currency,
            'exchange_rate' => $row->exchange_rate,
            'notes' => $row->notes,
            'voucher_number' => $row->voucher_number,
            'affects_balance' => false,
        ]);
        $row->update(['cash_movement_id' => $cm->id]);
        $this->journal->postSalary($row->fresh('employee'));
        return $row;
    }

    public function saveEmployeeMovement(array $data): EmployeeMovement
    {
        $row = EmployeeMovement::create($data);
        $cm = $this->cash->sync($row->shop_id, 'employee_movement', $row->id, [
            'date' => $row->date,
            'type' => $row->type === 'advance' ? 'payment' : 'salary',
            'category' => $row->typeLabel(),
            'amount' => $row->amount,
            'currency' => $row->currency,
            'exchange_rate' => $row->exchange_rate,
            'notes' => $row->notes,
            'voucher_number' => $row->voucher_number,
            'affects_balance' => false,
        ]);
        $row->update(['cash_movement_id' => $cm->id]);
        $this->journal->postEmployeeMovement($row->fresh('employee'));
        return $row;
    }

    public function saveWithdrawal(array $data): PartnerWithdrawal
    {
        $row = PartnerWithdrawal::create($data);
        $cm = $this->cash->sync($row->shop_id, 'withdrawal', $row->id, [
            'date' => $row->date,
            'type' => 'withdrawal',
            'category' => $row->nature,
            'amount' => $row->amount,
            'currency' => $row->currency,
            'exchange_rate' => $row->exchange_rate,
            'notes' => $row->description,
            'voucher_number' => $row->voucher_number,
            'affects_balance' => false,
        ]);
        $row->update(['cash_movement_id' => $cm->id]);
        $this->journal->postWithdrawal($row->fresh('partner'));
        return $row;
    }

    public function saveVoucher(array $data, ?Voucher $row = null): Voucher
    {
        if ($row) {
            $this->destroyLinked($row->shop_id, 'voucher', $row->id);
            $row->update($data);
            $row->refresh();
        } else {
            $row = Voucher::create($data);
        }

        if ($row->affects_balance) {
            $cm = $this->cash->sync($row->shop_id, 'voucher', $row->id, [
                'date' => $row->date,
                'type' => $row->type === 'receipt' ? 'receipt' : 'payment',
                'contact_id' => $row->contact_id,
                'category' => $row->type === 'receipt' ? 'سند قبض' : 'سند صرف',
                'amount' => $row->amount,
                'currency' => $row->currency,
                'exchange_rate' => $row->exchange_rate,
                'notes' => $row->description,
                'voucher_number' => $row->number,
                'affects_balance' => true,
                'document_ref' => $row->original_ref,
            ]);
            $row->update(['cash_movement_id' => $cm->id]);
            $this->journal->postVoucher($row->fresh());
        } else {
            $row->update(['cash_movement_id' => null]);
            $this->journal->forgetSource($row->shop_id, 'voucher', $row->id);
        }

        return $row;
    }

    public function saveCurrencyExchange(array $data): CurrencyExchange
    {
        $row = CurrencyExchange::create($data);
        $ref = $row->reference_number ?: 'EX-' . $row->id;

        $outCm = $this->cash->sync($row->shop_id, 'currency_exchange_out', $row->id, [
            'date' => $row->date,
            'type' => 'payment',
            'category' => 'تحويل عملة (صادر ' . $row->from_currency . ' ← ' . $row->to_currency . ')',
            'amount' => $row->from_amount,
            'currency' => $row->from_currency,
            'exchange_rate' => $row->exchange_rate,
            'notes' => trim(($row->notes ? $row->notes . ' — ' : '') . $row->directionLabel()),
            'voucher_number' => $ref,
            'affects_balance' => false,
            'document_ref' => $ref,
        ]);

        $inCm = $this->cash->sync($row->shop_id, 'currency_exchange_in', $row->id, [
            'date' => $row->date,
            'type' => 'receipt',
            'category' => 'تحويل عملة (وارد ' . $row->to_currency . ' ← ' . $row->from_currency . ')',
            'amount' => $row->to_amount,
            'currency' => $row->to_currency,
            'exchange_rate' => $row->exchange_rate,
            'notes' => trim(($row->notes ? $row->notes . ' — ' : '') . $row->directionLabel()),
            'voucher_number' => $ref,
            'affects_balance' => false,
            'document_ref' => $ref,
        ]);

        $row->update([
            'out_cash_movement_id' => $outCm->id,
            'in_cash_movement_id' => $inCm->id,
        ]);

        $this->journal->postCurrencyExchange($row->fresh());

        return $row->fresh();
    }

    public function destroyCurrencyExchange(CurrencyExchange $exchange): void
    {
        $this->cash->forget($exchange->shop_id, 'currency_exchange_out', $exchange->id);
        $this->cash->forget($exchange->shop_id, 'currency_exchange_in', $exchange->id);
        $this->journal->forgetSource($exchange->shop_id, 'currency_exchange', $exchange->id);
        $exchange->delete();
    }

    public function saveShippingExpense(array $data): ShippingExpense
    {
        $row = ShippingExpense::create($data);
        $row->load('transaction');
        $invoiceRef = $row->transaction?->invoice_number;

        $cm = $this->cash->sync($row->shop_id, 'shipping_expense', $row->id, [
            'date' => $row->date,
            'type' => 'payment',
            'category' => 'أجور شحن مشتريات' . ($invoiceRef ? ' (' . $invoiceRef . ')' : ''),
            'amount' => $row->amount,
            'currency' => $row->currency,
            'exchange_rate' => $row->exchange_rate,
            'notes' => $row->description ?: ($row->carrier_name ? 'شحن عبر ' . $row->carrier_name : 'مصاريف أجور شحن'),
            'voucher_number' => $row->voucher_number ?: 'SHP-' . $row->id,
            'affects_balance' => false,
            'document_ref' => $invoiceRef,
        ]);

        $row->update(['cash_movement_id' => $cm->id]);
        $this->journal->postShippingExpense($row->fresh('transaction'));

        return $row->fresh('transaction');
    }

    public function destroyShippingExpense(ShippingExpense $row): void
    {
        $this->cash->forget($row->shop_id, 'shipping_expense', $row->id);
        $this->journal->forgetSource($row->shop_id, 'shipping_expense', $row->id);
        $row->delete();
    }

    public function destroyLinked(string $shopId, string $source, int $id): void
    {
        $this->cash->forget($shopId, $source, $id);
        $this->journal->forgetSource($shopId, $source, $id);
    }
}
