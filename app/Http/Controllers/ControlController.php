<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\Contact;
use App\Models\Transaction;
use App\Services\AccountingService;
use App\Services\ControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ControlController extends Controller
{
    public function __construct(
        protected ControlService $control,
        protected AccountingService $accounting,
    ) {}

    public function preApproval()
    {
        $shop = current_shop();
        return view('control.pre-approval', [
            'report' => $this->control->preApproval($shop->id, (float) $shop->exchange_rate),
            'shop' => $shop,
        ]);
    }

    public function daily(Request $request)
    {
        $shop = current_shop();
        $date = $request->get('date', now()->toDateString());
        return view('control.daily', [
            'report' => $this->control->dailyReport($shop->id, $date, (float) $shop->exchange_rate),
            'shop' => $shop,
            'date' => $date,
        ]);
    }

    public function owner()
    {
        $shop = current_shop();
        return view('control.owner', [
            'report' => $this->control->ownerReport($shop->id, (float) $shop->exchange_rate),
            'shop' => $shop,
        ]);
    }

    public function kpis()
    {
        $shop = current_shop();
        return view('control.kpis', [
            'kpis' => $this->control->kpis($shop->id, (float) $shop->exchange_rate),
            'shop' => $shop,
        ]);
    }

    public function collection()
    {
        $list = [];
        foreach (Contact::where('type', 'customer')->get() as $c) {
            $s = $this->accounting->getContactStatement($c);
            if ($s['final_balance_usd'] > 0 || $s['final_balance_syp'] > 0) {
                $priority = $s['final_balance_usd'] >= 200 ? 'عالية' : 'متوسطة';
                $list[] = $s + ['priority' => $priority];
            }
        }
        return view('control.collection', ['list' => $list, 'shop' => current_shop()]);
    }

    public function currencies()
    {
        $shop = current_shop();
        $rows = [];
        foreach (Transaction::latest('date')->limit(200)->get() as $tx) {
            $note = $tx->currency === 'SYP' && (float) $tx->exchange_rate <= 0 ? 'سعر صرف ناقص' : 'سليم';
            $rows[] = [
                'source' => 'العمليات اليومية',
                'date' => $tx->date->format('Y-m-d'),
                'type' => $tx->type,
                'amount' => $tx->total,
                'currency' => $tx->currency,
                'rate' => $tx->exchange_rate,
                'note' => $note,
            ];
        }
        foreach (CashMovement::latest('date')->limit(200)->get() as $cm) {
            $rows[] = [
                'source' => 'حركة الصندوق',
                'date' => $cm->date->format('Y-m-d'),
                'type' => $cm->type,
                'amount' => $cm->amount,
                'currency' => $cm->currency,
                'rate' => $cm->exchange_rate,
                'note' => $cm->currency === 'SYP' && (float) $cm->exchange_rate <= 0 ? 'سعر صرف ناقص' : 'سليم',
            ];
        }
        return view('control.currencies', ['rows' => $rows, 'shop' => $shop]);
    }

    public function cashClose()
    {
        $shop = current_shop();
        $cash = $this->accounting->getCashboxSummary($shop->id, (float) $shop->exchange_rate);
        return view('control.cash-close', ['cash' => $cash, 'shop' => $shop]);
    }

    public function match()
    {
        $shop = current_shop();
        return view('control.match', [
            'match' => $this->control->voucherMatch($shop->id),
            'shop' => $shop,
        ]);
    }
}
