<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AccountingService;
use App\Models\Transaction;
use App\Models\Item;
use App\Models\Contact;
use App\Models\Shop;

class DashboardController extends Controller
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index()
    {
        $user = Auth::user();

        // السوبر أدمن يتم تحويله للوحة المنصة إذا لم يكن يتفحص متجراً
        if ($user->hasRole('super_admin') && !session()->has('impersonated_shop_id')) {
            return redirect()->route('admin.shops');
        }

        $shop = current_shop();
        if (!$shop) {
            abort(403, 'المستخدم غير مرتبط بأي متجر تجاري.');
        }

        $exchangeRate = (float)$shop->exchange_rate;
        $cashbox = $this->accountingService->getCashboxSummary($shop->id, $exchangeRate);
        $stockReports = $this->accountingService->getInventoryValuation($shop->id);
        $pnl = $this->accountingService->getProfitAndLossReport($shop->id);

        $lowStockItems = array_filter($stockReports, fn($sr) => $sr['is_low_stock']);
        $recentTransactions = Transaction::with(['contact', 'item'])
            ->latest('date')
            ->take(5)
            ->get();

        return view('dashboard', [
            'shop' => $shop,
            'cashbox' => $cashbox,
            'stockReports' => $stockReports,
            'pnl' => $pnl,
            'lowStockItems' => $lowStockItems,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
