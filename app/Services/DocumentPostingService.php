<?php

namespace App\Services;

use App\Models\EmployeeMovement;
use App\Models\Expense;
use App\Models\PartnerWithdrawal;
use App\Models\SalaryRecord;
use App\Models\Transaction;
use App\Models\TransactionLine;
use App\Models\Voucher;
use Illuminate\Support\Facades\Auth;

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
            'category' => 'فاتورة '.$tx->invoice_number,
            'amount' => $paid,
            'currency' => $tx->currency,
            'exchange_rate' => $tx->exchange_rate,
            'notes' => 'أثر نقدي للفاتورة '.$tx->invoice_number,
            'voucher_number' => $tx->invoice_number,
            'affects_balance' => false,
            'document_ref' => $tx->invoice_number,
        ]);
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
        $this->journal->postSimple($row->shop_id, $row->date->format('Y-m-d'), $row->voucher_number ?: 'EXP-'.$row->id, 'expense', $row->id, $row->expense_type, 'expenses', $this->journal->cashRole($row->currency), (float) $row->amount, $row->currency, (float) $row->exchange_rate);
        return $row;
    }

    public function saveSalary(array $data): SalaryRecord
    {
        $row = SalaryRecord::create($data);
        $cm = $this->cash->sync($row->shop_id, 'salary', $row->id, [
            'date' => $row->date,
            'type' => 'salary',
            'category' => 'راتب '.$row->month,
            'amount' => $row->net,
            'currency' => $row->currency,
            'exchange_rate' => $row->exchange_rate,
            'notes' => $row->notes,
            'voucher_number' => $row->voucher_number,
            'affects_balance' => false,
        ]);
        $row->update(['cash_movement_id' => $cm->id]);
        $this->journal->postSimple($row->shop_id, $row->date->format('Y-m-d'), $row->voucher_number ?: 'SAL-'.$row->id, 'salary', $row->id, 'راتب', 'salaries', $this->journal->cashRole($row->currency), (float) $row->net, $row->currency, (float) $row->exchange_rate);
        return $row;
    }

    public function saveEmployeeMovement(array $data)
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
        $this->journal->postSimple($row->shop_id, $row->date->format('Y-m-d'), $row->voucher_number ?: 'WD-'.$row->id, 'withdrawal', $row->id, 'مسحوبات', 'withdrawals', $this->journal->cashRole($row->currency), (float) $row->amount, $row->currency, (float) $row->exchange_rate);
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
            $debit = $row->type === 'receipt' ? $this->journal->cashRole($row->currency) : $this->journal->apRole($row->currency);
            $credit = $row->type === 'receipt' ? $this->journal->arRole($row->currency) : $this->journal->cashRole($row->currency);
            $this->journal->postSimple($row->shop_id, $row->date->format('Y-m-d'), $row->number, 'voucher', $row->id, $row->description ?: $row->number, $debit, $credit, (float) $row->amount, $row->currency, (float) $row->exchange_rate);
        } else {
            $row->update(['cash_movement_id' => null]);
        }
        return $row;
    }

    public function destroyLinked(string $shopId, string $source, int $id): void
    {
        $this->cash->forget($shopId, $source, $id);
        $this->journal->forgetSource($shopId, $source, $id);
    }
}
