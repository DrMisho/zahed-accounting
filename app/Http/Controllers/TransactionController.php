<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Item;
use App\Models\Location;
use App\Models\Transaction;
use App\Services\DocumentPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function __construct(protected DocumentPostingService $posting) {}

    public function index(Request $request)
    {
        $query = Transaction::with(['contact', 'item', 'lines'])->latest('date');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('contact', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        return view('transactions.index', [
            'transactions' => $query->paginate(20),
            'items' => Item::all(),
            'contacts' => Contact::all(),
            'shop' => current_shop(),
        ]);
    }

    public function create()
    {
        return view('transactions.create', [
            'items' => Item::all(),
            'contacts' => Contact::all(),
            'locations' => Location::all(),
            'shop' => current_shop(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'required|string',
            'date' => 'required|date',
            'type' => 'required|in:sale,purchase,sale_return,purchase_return',
            'operation_subtype' => 'required|in:cash,credit,installment',
            'contact_id' => 'required|exists:contacts,id',
            'currency' => 'required|in:USD,SYP',
            'exchange_rate' => 'required|numeric|min:1',
            'paid_amount' => 'nullable|numeric|min:0',
            'location_id' => 'nullable|exists:locations,id',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.qty' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
        ]);

        $first = $validated['lines'][0];
        $this->posting->saveInvoice([
            'invoice_number' => $validated['invoice_number'],
            'date' => $validated['date'],
            'type' => $validated['type'],
            'operation_subtype' => $validated['operation_subtype'],
            'payment_type' => $validated['operation_subtype'] === 'cash' ? 'cash' : 'credit',
            'contact_id' => $validated['contact_id'],
            'item_id' => $first['item_id'],
            'qty' => $first['qty'],
            'unit_price' => $first['unit_price'],
            'currency' => $validated['currency'],
            'exchange_rate' => $validated['exchange_rate'],
            'paid_amount' => $validated['paid_amount'] ?? 0,
            'location_id' => $validated['location_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ], $validated['lines']);

        return redirect()->route('transactions.index')->with('success', 'تم حفظ الفاتورة وترحيل الأثر حسب نوع البيع.');
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['contact', 'item', 'shop', 'lines.item']);
        return view('transactions.show', ['transaction' => $transaction]);
    }

    public function destroy(Transaction $transaction)
    {
        $this->posting->destroyInvoice($transaction);
        return redirect()->route('transactions.index')->with('success', 'تم حذف الفاتورة وعكس أثرها.');
    }
}
