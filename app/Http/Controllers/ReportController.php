<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AccountingService;

class ReportController extends Controller
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function pnl()
    {
        $shop = current_shop();
        $pnl = $this->accountingService->getProfitAndLossReport($shop->id);

        return view('reports.pnl', [
            'pnl' => $pnl,
            'shop' => $shop,
        ]);
    }

    public function stock()
    {
        $shop = current_shop();
        $stockReports = $this->accountingService->getInventoryValuation($shop->id);

        return view('reports.stock', [
            'stockReports' => $stockReports,
            'shop' => $shop,
        ]);
    }

    public function cashflow()
    {
        $shop = current_shop();
        $cashbox = $this->accountingService->getCashboxSummary($shop->id, (float)$shop->exchange_rate);

        return view('reports.cashflow', [
            'cashbox' => $cashbox,
            'shop' => $shop,
        ]);
    }

    public function balanceSheet()
    {
        $shop = current_shop();
        return view('reports.balance', [
            'bs' => $this->accountingService->getBalanceSheet($shop->id, (float) $shop->exchange_rate),
            'shop' => $shop,
        ]);
    }

    public function profitability()
    {
        $shop = current_shop();
        return view('reports.profitability', [
            'rows' => $this->accountingService->getInventoryValuation($shop->id),
            'shop' => $shop,
        ]);
    }
}
