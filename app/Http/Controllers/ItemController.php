<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Item;
use App\Services\AccountingService;

class ItemController extends Controller
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index()
    {
        $shop = current_shop();
        $stockReports = $this->accountingService->getInventoryValuation($shop->id);

        return view('items.index', [
            'stockReports' => $stockReports,
            'shop' => $shop,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'alert_limit' => 'required|integer|min:0',
            'opening_qty' => 'required|numeric|min:0',
            'opening_cost_usd' => 'required|numeric|min:0',
            'code' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
        ]);

        Item::create($validated);

        return redirect()->route('items.index')->with('success', 'تم تعريف الصنف الجديد بالمخزن بنجاح.');
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'alert_limit' => 'required|integer|min:0',
            'opening_qty' => 'required|numeric|min:0',
            'opening_cost_usd' => 'required|numeric|min:0',
            'code' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
        ]);

        $item->update($validated);

        return redirect()->route('items.index')->with('success', 'تم تحديث بيانات الصنف بنجاح.');
    }

    public function destroy(Item $item)
    {
        $item->delete();
        return redirect()->route('items.index')->with('success', 'تم حذف الصنف بنجاح.');
    }
}
