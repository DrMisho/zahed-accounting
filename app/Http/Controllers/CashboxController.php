<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\Contact;
use App\Services\AccountingService;
use App\Services\DocumentPostingService;
use Illuminate\Http\Request;

class CashboxController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService,
        protected DocumentPostingService $posting,
    ) {}

    public function index(Request $request)
    {
        $shop = current_shop();
        $exchangeRate = (float) $shop->exchange_rate;

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
        if ($request->input('contact_id') === '') {
            $request->merge(['contact_id' => null]);
        }

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

        $validated['shop_id'] = current_shop_id();

        $this->posting->saveCashMovement($validated);

        return redirect()->route('cashbox.index')->with('success', 'تم تسجيل سند الصندوق وترحيل قيده المحاسبي تلقائياً إلى دفتر الأستاذ.');
    }

    public function destroy(CashMovement $cashMovement)
    {
        $this->posting->destroyCashMovement($cashMovement);
        return redirect()->route('cashbox.index')->with('success', 'تم حذف سند الصندوق وعكس أثره في دفتر الأستاذ.');
    }
}
