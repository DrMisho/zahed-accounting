<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function sales(Request $request)
    {
        $shop = current_shop();
        $filters = $request->only(['from', 'to', 'contact_id', 'payment_type', 'currency', 'search']);
        $statement = $this->accountingService->getSalesStatement($shop->id, (float) $shop->exchange_rate, $filters);
        $customers = Contact::where('type', 'customer')->orderBy('name')->get();

        return view('reports.sales', [
            'statement' => $statement,
            'customers' => $customers,
            'shop' => $shop,
        ]);
    }

    public function purchases(Request $request)
    {
        $shop = current_shop();
        $filters = $request->only(['from', 'to', 'contact_id', 'payment_type', 'currency', 'search']);
        $statement = $this->accountingService->getPurchasesStatement($shop->id, (float) $shop->exchange_rate, $filters);
        $suppliers = Contact::where('type', 'supplier')->orderBy('name')->get();

        return view('reports.purchases', [
            'statement' => $statement,
            'suppliers' => $suppliers,
            'shop' => $shop,
        ]);
    }

    public function customers(Request $request)
    {
        $shop = current_shop();
        $filters = $request->only(['from', 'to', 'search', 'balance_status']);
        $statement = $this->accountingService->getContactsSummaryStatement($shop->id, 'customer', (float) $shop->exchange_rate, $filters);

        return view('reports.customers', [
            'statement' => $statement,
            'shop' => $shop,
        ]);
    }

    public function suppliers(Request $request)
    {
        $shop = current_shop();
        $filters = $request->only(['from', 'to', 'search', 'balance_status']);
        $statement = $this->accountingService->getContactsSummaryStatement($shop->id, 'supplier', (float) $shop->exchange_rate, $filters);

        return view('reports.suppliers', [
            'statement' => $statement,
            'shop' => $shop,
        ]);
    }

    public function fxDifferences(Request $request)
    {
        $shop = current_shop();
        $from = $request->get('from');
        $to = $request->get('to');
        $fx = $this->accountingService->getFxDifferenceReport($shop->id, (float) $shop->exchange_rate, $from, $to);

        return view('reports.fx', [
            'fx' => $fx,
            'shop' => $shop,
        ]);
    }

    public function trading()
    {
        $shop = current_shop();
        $trading = $this->accountingService->getTradingAccount($shop->id, (float) $shop->exchange_rate);

        return view('reports.trading', [
            'trading' => $trading,
            'shop' => $shop,
        ]);
    }

    public function pnl()
    {
        $shop = current_shop();
        $rate = (float) $shop->exchange_rate;
        $pnl = $this->accountingService->getProfitAndLossReport($shop->id, $rate);

        return view('reports.pnl', [
            'pnl' => $pnl,
            'shop' => $shop,
        ]);
    }

    public function finalAccounts()
    {
        $shop = current_shop();
        $rate = (float) $shop->exchange_rate;
        $trading = $this->accountingService->getTradingAccount($shop->id, $rate);
        $pnl = $this->accountingService->getProfitAndLossReport($shop->id, $rate);
        $bs = $this->accountingService->getBalanceSheet($shop->id, $rate);

        return view('reports.final-accounts', [
            'trading' => $trading,
            'pnl' => $pnl,
            'bs' => $bs,
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
        $cashbox = $this->accountingService->getCashboxSummary($shop->id, (float) $shop->exchange_rate);

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
