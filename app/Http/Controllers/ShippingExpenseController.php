<?php

namespace App\Http\Controllers;

use App\Models\ShippingExpense;
use App\Models\Transaction;
use App\Services\DocumentPostingService;
use Illuminate\Http\Request;

class ShippingExpenseController extends Controller
{
    public function __construct(protected DocumentPostingService $posting) {}

    public function index()
    {
        $shop = current_shop();
        $rows = ShippingExpense::with(['transaction.contact'])->latest('date')->latest('id')->get();
        $purchases = Transaction::with(['contact', 'shippingExpenses'])
            ->where('type', 'purchase')
            ->latest('date')
            ->get();

        $totalShippingUsd = round((float) $rows->sum('amount_usd'), 2);
        $totalPurchasesUsd = round((float) $purchases->sum('total_usd'), 2);
        $linkedCount = $rows->whereNotNull('transaction_id')->count();
        $shippingRatio = $totalPurchasesUsd > 0 ? round(($totalShippingUsd / $totalPurchasesUsd) * 100, 2) : 0;

        return view('shipping.index', [
            'shop' => $shop,
            'rows' => $rows,
            'purchases' => $purchases,
            'stats' => [
                'total_shipping_usd' => $totalShippingUsd,
                'total_purchases_usd' => $totalPurchasesUsd,
                'total_landed_usd' => round($totalPurchasesUsd + $totalShippingUsd, 2),
                'linked_count' => $linkedCount,
                'count' => $rows->count(),
                'shipping_ratio' => $shippingRatio,
            ],
        ]);
    }

    public function store(Request $request)
    {
        if ($request->input('transaction_id') === '') {
            $request->merge(['transaction_id' => null]);
        }

        $data = $request->validate([
            'date' => 'required|date',
            'transaction_id' => 'nullable|exists:transactions,id',
            'voucher_number' => 'nullable|string|max:100',
            'carrier_name' => 'nullable|string|max:150',
            'shipping_type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,SYP',
            'exchange_rate' => 'required|numeric|min:1',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        if (!empty($data['transaction_id'])) {
            $isPurchase = Transaction::where('id', $data['transaction_id'])
                ->where('type', 'purchase')
                ->exists();
            if (!$isPurchase) {
                return back()->withErrors(['transaction_id' => 'يجب اختيار فاتورة شراء صالحة تابعة للمتجر.'])->withInput();
            }
        }

        $data['shipping_type'] = $data['shipping_type'] ?: 'شحن مشتريات';
        $data['payment_method'] = $data['payment_method'] ?: 'نقدي';

        $this->posting->saveShippingExpense($data);

        return redirect()
            ->route('shipping.index')
            ->with('success', 'تم تسجيل مصروف أجور الشحن وربطه بتكلفة المشتريات وتحديث الصندوق.');
    }

    public function destroy(ShippingExpense $shippingExpense)
    {
        $this->posting->destroyShippingExpense($shippingExpense);

        return redirect()
            ->route('shipping.index')
            ->with('success', 'تم حذف مصروف الشحن وإعادة احتساب تكلفة المشتريات والصندوق.');
    }
}
