<?php

namespace App\Http\Controllers;

use App\Models\CurrencyExchange;
use App\Services\AccountingService;
use App\Services\DocumentPostingService;
use Illuminate\Http\Request;

class CurrencyExchangeController extends Controller
{
    public function __construct(
        protected DocumentPostingService $posting,
        protected AccountingService $accounting,
    ) {}

    public function index()
    {
        $shop = current_shop();
        $rows = CurrencyExchange::latest('date')->latest('id')->get();
        $summary = $this->accounting->getCashboxSummary($shop->id, (float) $shop->exchange_rate);

        $stats = [
            'syp_to_usd_syp' => (float) $rows->where('direction', 'syp_to_usd')->sum('from_amount'),
            'syp_to_usd_usd' => (float) $rows->where('direction', 'syp_to_usd')->sum('to_amount'),
            'usd_to_syp_usd' => (float) $rows->where('direction', 'usd_to_syp')->sum('from_amount'),
            'usd_to_syp_syp' => (float) $rows->where('direction', 'usd_to_syp')->sum('to_amount'),
            'count' => $rows->count(),
        ];

        return view('currency-exchanges.index', [
            'shop' => $shop,
            'rows' => $rows,
            'summary' => $summary,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'direction' => 'required|in:syp_to_usd,usd_to_syp',
            'from_amount' => 'required|numeric|min:0.01',
            'exchange_rate' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $data['from_currency'] = $data['direction'] === 'syp_to_usd' ? 'SYP' : 'USD';
        $data['to_currency'] = $data['direction'] === 'syp_to_usd' ? 'USD' : 'SYP';

        $exchange = $this->posting->saveCurrencyExchange($data);

        return redirect()
            ->route('currency-exchanges.index')
            ->with(
                'success',
                'تم تحويل ' . number_format((float) $exchange->from_amount, 2) . ' ' . $exchange->from_currency
                . ' إلى ' . number_format((float) $exchange->to_amount, 2) . ' ' . $exchange->to_currency
                . ' وتحديث أرصدة الصناديق.'
            );
    }

    public function destroy(CurrencyExchange $currencyExchange)
    {
        $this->posting->destroyCurrencyExchange($currencyExchange);

        return redirect()
            ->route('currency-exchanges.index')
            ->with('success', 'تم حذف عملية تحويل العملة وعكس أثرها على الصندوقين.');
    }
}
