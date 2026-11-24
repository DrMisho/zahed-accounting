<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\CostCenter;
use App\Models\Expense;
use App\Models\Voucher;
use App\Services\DocumentPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoucherController extends Controller
{
    public function __construct(protected DocumentPostingService $posting) {}

    public function index(Request $request)
    {
        $type = $request->get('type', 'receipt');
        return view('vouchers.index', [
            'type' => $type,
            'rows' => Voucher::with('contact')->where('type', $type)->latest('date')->get(),
            'contacts' => Contact::orderBy('name')->get(),
            'shop' => current_shop(),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->input('contact_id') === '') {
            $request->merge(['contact_id' => null]);
        }
        $data = $request->validate([
            'type' => 'required|in:receipt,payment',
            'number' => 'required|string',
            'date' => 'required|date',
            'contact_id' => 'nullable|exists:contacts,id',
            'beneficiary' => 'nullable|string',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,SYP',
            'exchange_rate' => 'required|numeric|min:1',
            'payment_method' => 'nullable|string',
            'original_ref' => 'nullable|string',
            'affects_balance' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);
        $data['affects_balance'] = $request->boolean('affects_balance');
        $this->posting->saveVoucher($data);
        return back()->with('success', $data['affects_balance']
            ? 'تم حفظ السند مع أثر على الصندوق والذمة.'
            : 'تم حفظ السند كتوثيق دون أثر على الذمم.');
    }

    public function update(Request $request, Voucher $voucher)
    {
        if ($request->input('contact_id') === '') {
            $request->merge(['contact_id' => null]);
        }
        $data = $request->validate([
            'type' => 'required|in:receipt,payment',
            'number' => 'required|string',
            'date' => 'required|date',
            'contact_id' => 'nullable|exists:contacts,id',
            'beneficiary' => 'nullable|string',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,SYP',
            'exchange_rate' => 'required|numeric|min:1',
            'payment_method' => 'nullable|string',
            'original_ref' => 'nullable|string',
            'affects_balance' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);
        $data['affects_balance'] = $request->boolean('affects_balance');
        $this->posting->saveVoucher($data, $voucher);
        return redirect()->route('vouchers.index', ['type' => $voucher->type])->with('success', 'تم تعديل السند بنجاح.');
    }

    public function show(Voucher $voucher)
    {
        $voucher->load('contact');
        return view('vouchers.show', ['voucher' => $voucher, 'shop' => current_shop()]);
    }

    public function expenses()
    {
        $shop = current_shop();
        $expenseAccounts = \App\Models\Account::where('shop_id', $shop->id)
            ->where('type', 'expense')
            ->where('statement_type', 'pnl')
            ->where('is_postable', true)
            ->orderBy('code')
            ->get();

        return view('vouchers.expenses', [
            'rows' => Expense::with(['costCenter', 'account'])->latest('date')->get(),
            'centers' => CostCenter::orderBy('code')->get(),
            'expenseAccounts' => $expenseAccounts,
            'shop' => $shop,
        ]);
    }

    public function storeExpense(Request $request)
    {
        if ($request->input('account_id') === '') {
            $request->merge(['account_id' => null]);
        }
        if ($request->input('cost_center_id') === '') {
            $request->merge(['cost_center_id' => null]);
        }

        $this->posting->saveExpense($request->validate([
            'date' => 'required|date',
            'voucher_number' => 'nullable|string',
            'expense_type' => 'required|string',
            'account_id' => 'nullable|exists:accounts,id',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,SYP',
            'exchange_rate' => 'required|numeric|min:1',
            'payment_method' => 'nullable|string',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'notes' => 'nullable|string',
        ]));
        return back()->with('success', 'تم تسجيل المصروف وترحيل قيده المحاسبي إلى دفتر الأستاذ.');
    }
}
