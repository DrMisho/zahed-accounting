<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\AccountingService;
use App\Services\JournalService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService,
        protected JournalService $journalService,
    ) {}

    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('super_admin') && !session()->has('impersonated_shop_id')) {
            return redirect()->route('admin.shops');
        }

        $shop = current_shop();
        if (!$shop) {
            abort(403, 'المستخدم غير مرتبط بأي متجر تجاري.');
        }

        $exchangeRate = (float) $shop->exchange_rate;
        $this->journalService->ensureSeededFromOperations($shop->id, $this->accountingService);

        $cashbox = $this->accountingService->getCashboxSummary($shop->id, $exchangeRate);
        $stockReports = $this->accountingService->getInventoryValuation($shop->id);
        $pnl = $this->journalService->getGlProfitAndLoss($shop->id, $exchangeRate);

        $lowStockItems = array_filter($stockReports, fn ($sr) => $sr['is_low_stock']);
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
