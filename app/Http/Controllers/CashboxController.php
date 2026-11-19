<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CashMovement;
use App\Models\Contact;
use App\Services\AccountingService;

class CashboxController extends Controller
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index(Request $request)
    {
        $shop = current_shop();
        $exchangeRate = (float)$shop->exchange_rate;

        $query = CashMovement::with('contact')->latest('date');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        $movements = $query->paginate(20);
        $summary = $this->accountingService->getCashboxSummary($shop->id, $exchangeRate);

        return view('cashbox.index', [
            'movements' => $movements,
            'summary' => $summary,
            'shop' => $shop,
            'contacts' => Contact::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:receipt,payment,expense,salary,withdrawal,opening',
            'contact_id' => 'nullable|exists:contacts,id',
            'category' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,SYP',
            'exchange_rate' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
        ]);

        CashMovement::create($validated);

        return redirect()->route('cashbox.index')->with('success', 'تم تسجيل سند الصندوق وتحديث الرصيد التراكمي.');
    }

    public function destroy(CashMovement $cashMovement)
    {
        $cashMovement->delete();
        return redirect()->route('cashbox.index')->with('success', 'تم حذف سند الصندوق بنجاح.');
    }
}
