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
        $type = $request->get('type', 'customer');
        $contacts = Contact::where('type', $type)->get();

        $contactList = [];
        foreach ($contacts as $contact) {
            $stmt = $this->accountingService->getContactStatement($contact);
            $contactList[] = [
                'contact' => $contact,
                'balance_usd' => $stmt['final_balance_usd'],
                'balance_syp' => $stmt['final_balance_syp'],
            ];
        }

        return view('contacts.index', [
            'type' => $type,
            'contactList' => $contactList,
            'shop' => current_shop(),
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

    public function statement(Contact $contact)
    {
        $statement = $this->accountingService->getContactStatement($contact);

        return view('contacts.statement', [
            'statement' => $statement,
            'shop' => current_shop(),
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
