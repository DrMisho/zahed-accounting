<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CostCenter;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use App\Services\JournalService;
use App\Services\ShopSetupService;
use App\Support\Money;
use Illuminate\Http\Request;

class AccountingBooksController extends Controller
{
    public function __construct(
        protected ShopSetupService $setup,
        protected JournalService $journal,
        protected AccountingService $accounting,
    ) {}

    public function accounts(Request $request)
    {
        $shop = current_shop();
        $this->setup->bootstrap($shop);
        $this->journal->ensureSeededFromOperations($shop->id, $this->accounting);

        $from = $request->get('from');
        $to = $request->get('to');

        $tree = $this->journal->chartTreeWithBalances($shop->id, $from, $to);
        $parents = Account::where('shop_id', $shop->id)
            ->where('is_postable', false)
            ->orderBy('code')
            ->get();

        $nextCodes = [];
        foreach ($parents as $p) {
            $nextCodes[$p->id] = $this->setup->nextChildCode($shop->id, $p);
        }

        $glFxPreview = $this->journal->getGlFxPreview($shop->id, (float) $shop->exchange_rate);

        return view('books.accounts', [
            'tree' => $tree,
            'parents' => $parents,
            'nextCodes' => $nextCodes,
            'glFxPreview' => $glFxPreview,
            'from' => $from,
            'to' => $to,
            'shop' => $shop,
        ]);
    }

    public function storeAccount(Request $request)
    {
        $shop = current_shop();
        $data = $request->validate([
            'parent_id' => 'required|exists:accounts,id',
            'code' => 'required|string|max:25',
            'name' => 'required|string|max:180',
            'is_postable' => 'nullable|boolean',
            'classification' => 'nullable|string|max:80',
            'currency' => 'nullable|in:USD,SYP',
            'opening_debit' => 'nullable|numeric|min:0',
            'opening_credit' => 'nullable|numeric|min:0',
        ]);

        $parent = Account::where('shop_id', $shop->id)->findOrFail($data['parent_id']);
        if ($parent->is_postable) {
            return back()->withErrors(['parent_id' => 'لا يمكن إضافة حساب فرعي تحت حساب تفصيلي قابل للترحيل؛ اختر حساباً رئيسياً تجميعياً.']);
        }

        $exists = Account::where('shop_id', $shop->id)->where('code', trim($data['code']))->exists();
        if ($exists) {
            return back()->withErrors(['code' => 'رقم الحساب مستخدم مسبقاً في شجرة الحسابات.']);
        }

        $isPostable = $request->has('is_postable') ? $request->boolean('is_postable') : true;

        Account::create([
            'shop_id' => $shop->id,
            'parent_id' => $parent->id,
            'level' => min(4, $parent->level + 1),
            'code' => trim($data['code']),
            'name' => trim($data['name']),
            'type' => $parent->type,
            'classification' => $data['classification'] ?: $parent->classification,
            'is_postable' => $isPostable,
            'normal_balance' => $parent->normal_balance,
            'statement_type' => $parent->statement_type,
            'currency' => $data['currency'] ?: 'USD',
            'is_system' => false,
            'opening_debit' => $isPostable ? (float) ($data['opening_debit'] ?? 0) : 0,
            'opening_credit' => $isPostable ? (float) ($data['opening_credit'] ?? 0) : 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'تمت إضافة الحساب بنجاح إلى شجرة الحسابات.');
    }

    public function updateAccount(Request $request, Account $account)
    {
        $data = $request->validate([
            'name' => 'required|string|max:180',
            'classification' => 'nullable|string|max:80',
            'currency' => 'nullable|in:USD,SYP',
            'opening_debit' => 'nullable|numeric|min:0',
            'opening_credit' => 'nullable|numeric|min:0',
        ]);

        $update = [
            'name' => trim($data['name']),
            'classification' => $data['classification'] ?? $account->classification,
        ];

        if ($account->is_postable) {
            if (!empty($data['currency'])) {
                $update['currency'] = $data['currency'];
            }
            $update['opening_debit'] = (float) ($data['opening_debit'] ?? 0);
            $update['opening_credit'] = (float) ($data['opening_credit'] ?? 0);
        }

        $account->update($update);

        return back()->with('success', 'تم تحديث بيانات الحساب بنجاح.');
    }

    public function destroyAccount(Account $account)
    {
        if ($account->is_system) {
            return back()->withErrors(['account' => 'لا يمكن حذف حساب نظامي أساسي مرتبط بالترحيل التلقائي.']);
        }

        if ($account->children()->exists()) {
            return back()->withErrors(['account' => 'لا يمكن حذف حساب يحتوي على حسابات فرعية تابعة له.']);
        }

        if ($account->lines()->exists()) {
            return back()->withErrors(['account' => 'لا يمكن حذف حساب عليه قيود وحركات مرحلة في دفتر الأستاذ.']);
        }

        $account->delete();
        return back()->with('success', 'تم حذف الحساب من شجرة الحسابات.');
    }

    public function rebuildEntries()
    {
        $shop = current_shop();
        $this->setup->bootstrap($shop);
        $result = $this->journal->rebuildAllEntries($shop->id, $this->accounting);

        $status = $result['balanced']
            ? 'وميزان المراجعة متوازن 100% بالعملة الأساسية (USD).'
            : 'يرجى مراجعة الأرصدة الافتتاحية اليدوية (الفرق: $' . number_format($result['difference'], 2) . ').';

        return back()->with('success', "تم إعادة بناء وترحيل {$result['entries']} قيد محاسبي تلقائياً بنجاح، {$status}");
    }

    public function revalueFx(Request $request)
    {
        $shop = current_shop();
        $date = $request->input('date') ?: date('Y-m-d');
        $result = $this->journal->postFxRevaluation($shop->id, (float) $shop->exchange_rate, $date);

        if (!$result['posted']) {
            return back()->with('info', 'لا يوجد فرق إعادة تقييم مستحق على أرصدة الليرة السورية وفق سعر الصرف الحالي.');
        }

        $diff = (float) $result['net_adjustment_usd'];
        $sign = $diff >= 0 ? 'ربح' : 'خسارة';
        $amt = number_format(abs($diff), 2);

        return back()->with('success', "تم ترحيل قيد إعادة تقييم فروق صرف العملة ({$result['document']}) بصافي {$sign} قدره \${$amt} USD.");
    }

    public function mappings()
    {
        $shop = current_shop();
        $this->setup->bootstrap($shop);
        $status = $this->setup->mappingStatus($shop->id);
        return view('books.mappings', [
            'status' => $status,
            'accounts' => Account::where('is_postable', true)->orderBy('code')->get(),
            'shop' => $shop,
        ]);
    }

    public function saveMappings(Request $request)
    {
        $shop = current_shop();
        $roles = $request->input('role', []);
        foreach ($roles as $role => $accountId) {
            if ($accountId && !Account::where('shop_id', $shop->id)->where('id', $accountId)->where('is_postable', true)->exists()) {
                continue;
            }
            AccountMapping::updateOrCreate(
                ['shop_id' => $shop->id, 'role' => $role],
                ['account_id' => $accountId ?: null]
            );
        }
        $shop->update(['posting_enabled' => true]);

        return back()->with('success', 'تم حفظ ربط أدوار الحسابات والترحيل التلقائي مفعّل بشكل دائم.');
    }

    public function ledger(Request $request)
    {
        $shop = current_shop();
        $this->setup->bootstrap($shop);
        $this->journal->ensureSeededFromOperations($shop->id, $this->accounting);

        $accountId = $request->integer('account_id') ?: null;
        $from = $request->get('from');
        $to = $request->get('to');

        $ledgerData = $this->journal->ledger($shop->id, $accountId, $from, $to);
        $allAccounts = Account::where('shop_id', $shop->id)->orderBy('code')->get();
        $postableAccounts = $allAccounts->where('is_postable', true)->values();
        $selectedAccount = $accountId ? $allAccounts->firstWhere('id', $accountId) : null;

        return view('books.ledger', [
            'rows' => $ledgerData['rows'],
            'totalDebit' => $ledgerData['total_debit'],
            'totalCredit' => $ledgerData['total_credit'],
            'openingBalance' => $ledgerData['opening_balance'],
            'accounts' => $allAccounts,
            'postableAccounts' => $postableAccounts,
            'selectedAccount' => $selectedAccount,
            'accountId' => $accountId,
            'from' => $from,
            'to' => $to,
            'shop' => $shop,
        ]);
    }

    public function storeJournal(Request $request)
    {
        $shop = current_shop();
        $defaultRate = $this->journal->resolveRate($shop->id, (float) $shop->exchange_rate);

        $data = $request->validate([
            'date' => 'required|date',
            'document_number' => 'required|string|max:60',
            'description' => 'nullable|string|max:500',
            'exchange_rate' => 'nullable|numeric|min:1',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'nullable|exists:accounts,id',
            'lines.*.currency' => 'nullable|in:USD,SYP',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
        ]);

        $entryRate = (float) ($data['exchange_rate'] ?? $defaultRate);
        if ($entryRate <= 0) {
            $entryRate = $defaultRate;
        }

        $preparedLines = [];
        $sumDebitUsd = 0.0;
        $sumCreditUsd = 0.0;

        foreach ($data['lines'] as $line) {
            $accId = (int) ($line['account_id'] ?? 0);
            $rawDebit = (float) ($line['debit'] ?? 0);
            $rawCredit = (float) ($line['credit'] ?? 0);

            if ($accId <= 0 || ($rawDebit <= 0 && $rawCredit <= 0)) {
                continue;
            }

            $account = Account::where('shop_id', $shop->id)->find($accId);
            if (!$account || !$account->is_postable) {
                return back()->withInput()->withErrors(['lines' => 'لا يمكن الترحيل على حساب رئيسي تجميعي؛ اختر حساباً فرعياً قابلاً للترحيل فقط.']);
            }

            $cur = strtoupper((string) ($line['currency'] ?? 'USD')) === 'SYP' ? 'SYP' : 'USD';
            $debitUsd = round(Money::toUsd($rawDebit, $cur, $entryRate), 2);
            $creditUsd = round(Money::toUsd($rawCredit, $cur, $entryRate), 2);

            $preparedLines[] = [
                'account_id' => $account->id,
                'debit' => $debitUsd,
                'credit' => $creditUsd,
                'currency' => $cur,
                'exchange_rate' => $cur === 'SYP' ? $entryRate : 1.0,
                'foreign_debit' => round($rawDebit, 2),
                'foreign_credit' => round($rawCredit, 2),
                'description' => $line['description'] ?: ($data['description'] ?? $data['document_number']),
            ];

            $sumDebitUsd += $debitUsd;
            $sumCreditUsd += $creditUsd;
        }

        if (count($preparedLines) < 2) {
            return back()->withInput()->withErrors(['lines' => 'يجب إدخال سطرين على الأقل (مدين ودائن) لحفظ القيد اليدوي.']);
        }

        if (abs($sumDebitUsd - $sumCreditUsd) > 0.02) {
            return back()->withInput()->withErrors([
                'lines' => 'القيد غير متوازن بالعملة الأساسية (USD). مجموع المدين: $' . number_format($sumDebitUsd, 2) . ' ومجموع الدائن: $' . number_format($sumCreditUsd, 2),
            ]);
        }

        $entry = $this->journal->writeEntry(
            $shop->id,
            $data['date'],
            $data['document_number'],
            'manual',
            null,
            $data['description'] ?: 'قيد يومية يدوي',
            $preparedLines
        );

        if (!$entry) {
            return back()->withInput()->withErrors(['lines' => 'تعذر حفظ القيد؛ تأكد من توازن المبالغ وصحة الحسابات المختارة.']);
        }

        return back()->with('success', 'تم ترحيل القيد اليدوي المتوازن إلى دفتر الأستاذ العام.');
    }

    public function destroyJournal(JournalEntry $journalEntry)
    {
        if (!in_array($journalEntry->source_type, ['manual', 'fx_revaluation'], true)) {
            return back()->withErrors(['entry' => 'لا يمكن حذف قيد مُرحّل تلقائياً من هنا؛ احذف أو عدّل المستند الأصلي لتحديث القيد تلقائياً.']);
        }

        $journalEntry->delete();
        return back()->with('success', 'تم حذف القيد المحاسبي بنجاح.');
    }

    public function trial(Request $request)
    {
        $shop = current_shop();
        $this->setup->bootstrap($shop);
        $this->journal->ensureSeededFromOperations($shop->id, $this->accounting);

        $from = $request->get('from');
        $to = $request->get('to');
        $hideZero = $request->boolean('hide_zero', true);

        return view('books.trial', [
            'trial' => $this->journal->trialBalance($shop->id, $from, $to, null, $hideZero),
            'hideZero' => $hideZero,
            'from' => $from,
            'to' => $to,
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
