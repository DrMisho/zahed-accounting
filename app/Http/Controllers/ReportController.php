<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\AccountingService;
use App\Services\JournalService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService,
        protected JournalService $journalService,
    ) {}

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
        $this->journalService->ensureSeededFromOperations($shop->id, $this->accountingService);

        $from = $request->get('from');
        $to = $request->get('to');
        $rate = (float) $shop->exchange_rate;
        $fx = $this->accountingService->getFxDifferenceReport($shop->id, $rate, $from, $to);
        $glFxPreview = $this->journalService->getGlFxPreview($shop->id, $rate);

        return view('reports.fx', [
            'fx' => $fx,
            'glFxPreview' => $glFxPreview,
            'shop' => $shop,
        ]);
    }

    /**
     * Trading Account strictly calculated from the Chart of Accounts & General Ledger.
     */
    public function trading(Request $request)
    {
        $shop = current_shop();
        $this->journalService->ensureSeededFromOperations($shop->id, $this->accountingService);

        $from = $request->get('from');
        $to = $request->get('to');
        $trading = $this->journalService->getGlTradingAccount($shop->id, (float) $shop->exchange_rate, $from, $to);

        return view('reports.trading', [
            'trading' => $trading,
            'shop' => $shop,
        ]);
    }

    /**
     * Profit & Loss Account strictly calculated from the Chart of Accounts & General Ledger.
     */
    public function pnl(Request $request)
    {
        $shop = current_shop();
        $this->journalService->ensureSeededFromOperations($shop->id, $this->accountingService);

        $from = $request->get('from');
        $to = $request->get('to');
        $pnl = $this->journalService->getGlProfitAndLoss($shop->id, (float) $shop->exchange_rate, $from, $to);

        return view('reports.pnl', [
            'pnl' => $pnl,
            'shop' => $shop,
        ]);
    }

    /**
     * Balance Sheet strictly calculated from the Chart of Accounts & General Ledger.
     */
    public function balanceSheet(Request $request)
    {
        $shop = current_shop();
        $this->journalService->ensureSeededFromOperations($shop->id, $this->accountingService);

        $asOf = $request->get('as_of') ?: $request->get('to');
        $bs = $this->journalService->getGlBalanceSheet($shop->id, (float) $shop->exchange_rate, $asOf);

        return view('reports.balance', [
            'bs' => $bs,
            'shop' => $shop,
        ]);
    }

    /**
     * Combined Final Accounts package strictly calculated from the General Ledger.
     */
    public function finalAccounts(Request $request)
    {
        $shop = current_shop();
        $this->journalService->ensureSeededFromOperations($shop->id, $this->accountingService);

        $rate = (float) $shop->exchange_rate;
        $from = $request->get('from');
        $to = $request->get('to');

        $trading = $this->journalService->getGlTradingAccount($shop->id, $rate, $from, $to);
        $pnl = $this->journalService->getGlProfitAndLoss($shop->id, $rate, $from, $to);
        $bs = $this->journalService->getGlBalanceSheet($shop->id, $rate, $to);

        return view('reports.final-accounts', [
            'trading' => $trading,
            'pnl' => $pnl,
            'bs' => $bs,
            'from' => $from,
            'to' => $to,
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

    public function profitability()
    {
        $shop = current_shop();
        return view('reports.profitability', [
            'rows' => $this->accountingService->getInventoryValuation($shop->id),
            'shop' => $shop,
        ]);
    }
}
