<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CostCenter;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\JournalService;
use App\Services\ShopSetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountingBooksController extends Controller
{
    public function __construct(
        protected ShopSetupService $setup,
        protected JournalService $journal,
    ) {}

    public function accounts()
    {
        $shop = current_shop();
        $this->setup->bootstrap($shop);
        return view('books.accounts', [
            'accounts' => Account::orderBy('code')->get(),
            'shop' => $shop,
        ]);
    }

    public function storeAccount(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'classification' => 'nullable|string',
            'opening_debit' => 'nullable|numeric',
            'opening_credit' => 'nullable|numeric',
        ]);
        Account::create($data);
        return back()->with('success', 'تم حفظ الحساب.');
    }

    public function destroyAccount(Account $account)
    {
        $account->delete();
        return back()->with('success', 'تم حذف الحساب.');
    }

    public function mappings()
    {
        $shop = current_shop();
        $status = $this->setup->mappingStatus($shop->id);
        return view('books.mappings', [
            'status' => $status,
            'accounts' => Account::orderBy('code')->get(),
            'shop' => $shop,
        ]);
    }

    public function saveMappings(Request $request)
    {
        $shop = current_shop();
        $roles = $request->input('role', []);
        foreach ($roles as $role => $accountId) {
            AccountMapping::updateOrCreate(
                ['shop_id' => $shop->id, 'role' => $role],
                ['account_id' => $accountId ?: null]
            );
        }
        if ($request->boolean('enable_posting')) {
            $status = $this->setup->mappingStatus($shop->id);
            if (!$status['ready']) {
                return back()->with('info', 'لا يمكن تفعيل الترحيل قبل استكمال الربط الإلزامي.');
            }
            $shop->update(['posting_enabled' => true]);
        }
        if ($request->boolean('disable_posting')) {
            $shop->update(['posting_enabled' => false]);
        }
        return back()->with('success', 'تم حفظ ربط الحسابات.');
    }

    public function ledger(Request $request)
    {
        $shop = current_shop();
        $accountId = $request->integer('account_id') ?: null;
        return view('books.ledger', [
            'rows' => $this->journal->ledger($shop->id, $accountId),
            'accounts' => Account::orderBy('code')->get(),
            'accountId' => $accountId,
            'shop' => $shop,
        ]);
    }

    public function storeJournal(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'document_number' => 'required|string',
            'description' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'nullable|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string',
        ]);
        $debit = collect($data['lines'])->sum(fn ($l) => (float) ($l['debit'] ?? 0));
        $credit = collect($data['lines'])->sum(fn ($l) => (float) ($l['credit'] ?? 0));
        if (abs($debit - $credit) > 0.009) {
            return back()->withErrors(['lines' => 'القيد غير متوازن. المدين '.$debit.' والدائن '.$credit]);
        }
        $entry = JournalEntry::create([
            'date' => $data['date'],
            'document_number' => $data['document_number'],
            'description' => $data['description'] ?? null,
            'source_type' => 'manual',
        ]);
        foreach ($data['lines'] as $line) {
            if (empty($line['account_id']) || ((float) ($line['debit'] ?? 0) == 0 && (float) ($line['credit'] ?? 0) == 0)) {
                continue;
            }
            JournalLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $line['account_id'],
                'description' => $line['description'] ?? $data['description'] ?? null,
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
                'currency' => 'USD',
                'amount_usd' => ((float) ($line['debit'] ?? 0)) + ((float) ($line['credit'] ?? 0)),
            ]);
        }
        return back()->with('success', 'تم حفظ القيد اليدوي.');
    }

    public function trial()
    {
        $shop = current_shop();
        return view('books.trial', [
            'trial' => $this->journal->trialBalance($shop->id),
            'shop' => $shop,
        ]);
    }

    public function postingMap()
    {
        return view('books.posting-map', ['shop' => current_shop()]);
    }

    public function costCenters()
    {
        return view('books.cost-centers', [
            'centers' => CostCenter::orderBy('code')->get(),
            'shop' => current_shop(),
        ]);
    }

    public function storeCostCenter(Request $request)
    {
        CostCenter::create($request->validate([
            'code' => 'required|string',
            'name' => 'required|string',
            'type' => 'nullable|string',
            'cost_amount' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]));
        return back()->with('success', 'تم حفظ مركز التكلفة.');
    }
}
