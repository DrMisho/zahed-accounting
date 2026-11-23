<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Contact;
use App\Services\AccountingService;

class ContactController extends Controller
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index(Request $request)
    {
        $shop = current_shop();
        $rate = (float) ($shop->exchange_rate ?? 14500);
        $type = $request->get('type', 'customer');
        $contacts = Contact::where('type', $type)->get();

        $contactList = [];
        foreach ($contacts as $contact) {
            $stmt = $this->accountingService->getContactStatement($contact, null, null, $rate);
            $contactList[] = [
                'contact' => $contact,
                'balance_usd' => $stmt['final_balance_usd'],
                'balance_syp' => $stmt['final_balance_syp'],
                'balance_total_usd' => $stmt['final_balance_total_usd'],
                'syp_fx_diff_usd' => $stmt['syp_fx_diff_usd'],
            ];
        }

        return view('contacts.index', [
            'type' => $type,
            'contactList' => $contactList,
            'shop' => $shop,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:customer,supplier',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'code' => 'nullable|string|max:50',
            'opening_usd' => 'nullable|numeric',
            'opening_syp' => 'nullable|numeric',
        ]);

        Contact::create($validated);

        return redirect()->route('contacts.index', ['type' => $validated['type']])
            ->with('success', 'تم إضافة جهة الاتصال بنجاح.');
    }

    public function statement(Request $request, Contact $contact)
    {
        $shop = current_shop();
        $from = $request->get('from');
        $to = $request->get('to');
        $statement = $this->accountingService->getContactStatement(
            $contact,
            $from ?: null,
            $to ?: null,
            (float) ($shop->exchange_rate ?? 14500)
        );

        return view('contacts.statement', [
            'statement' => $statement,
            'shop' => $shop,
        ]);
    }

    public function destroy(Contact $contact)
    {
        $type = $contact->type;
        $contact->delete();

        return redirect()->route('contacts.index', ['type' => $type])
            ->with('success', 'تم حذف جهة الاتصال بنجاح.');
    }
}
