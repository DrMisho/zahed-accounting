<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemLocationStock;
use App\Models\Location;
use App\Models\StockTransfer;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryExtraController extends Controller
{
    public function __construct(protected AccountingService $accounting) {}

    public function locations()
    {
        $shop = current_shop();
        return view('inventory.locations', [
            'locations' => Location::orderBy('name')->get(),
            'items' => Item::orderBy('item_name')->get(),
            'rows' => $this->accounting->getLocationInventory($shop->id),
            'shop' => $shop,
        ]);
    }

    public function storeLocation(Request $request)
    {
        Location::create($request->validate([
            'name' => 'required|string',
            'is_default' => 'nullable|boolean',
        ]));
        return back()->with('success', 'تم حفظ الموقع.');
    }

    public function storeOpening(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'item_id' => 'required|exists:items,id',
            'opening_qty' => 'required|numeric',
            'opening_cost_usd' => 'nullable|numeric|min:0',
        ]);
        ItemLocationStock::updateOrCreate(
            ['location_id' => $data['location_id'], 'item_id' => $data['item_id']],
            $data
        );
        return back()->with('success', 'تم حفظ افتتاح الموقع.');
    }

    public function transfers()
    {
        return view('inventory.transfers', [
            'transfers' => StockTransfer::with(['item', 'fromLocation', 'toLocation'])->latest('date')->get(),
            'items' => Item::orderBy('item_name')->get(),
            'locations' => Location::orderBy('name')->get(),
            'shop' => current_shop(),
        ]);
    }

    public function storeTransfer(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'number' => 'required|string',
            'item_id' => 'required|exists:items,id',
            'qty' => 'required|numeric|min:0.01',
            'from_location_id' => 'required|exists:locations,id',
            'to_location_id' => 'required|exists:locations,id|different:from_location_id',
            'notes' => 'nullable|string',
        ]);
        StockTransfer::create($data);
        return back()->with('success', 'تم تسجيل المناقلة دون أثر على البيع أو الشراء.');
    }

    public function movement(Request $request)
    {
        $shop = current_shop();
        $itemId = $request->integer('item_id') ?: null;
        return view('inventory.movement', [
            'rows' => $this->accounting->itemMovement($shop->id, $itemId),
            'items' => Item::orderBy('item_name')->get(),
            'itemId' => $itemId,
            'shop' => $shop,
        ]);
    }

    public function alerts()
    {
        $shop = current_shop();
        $stock = collect($this->accounting->getInventoryValuation($shop->id));
        return view('inventory.alerts', [
            'rows' => $stock,
            'shop' => $shop,
        ]);
    }

    public function sheet()
    {
        $shop = current_shop();
        return view('inventory.sheet', [
            'rows' => $this->accounting->getInventoryValuation($shop->id),
            'shop' => $shop,
        ]);
    }
}
