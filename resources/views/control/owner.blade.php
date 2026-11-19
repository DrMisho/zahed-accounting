@extends('layouts.app')
@section('title', 'تقرير صاحب العمل')
@section('content')
<div class="bg-white rounded-2xl p-6 border space-y-4">
    <h1 class="text-xl font-bold">تقرير صاحب المحل</h1>
    <div class="grid grid-cols-2 gap-3 text-sm">
        <div class="p-4 bg-slate-50 rounded-xl">صافي المبيعات $ {{ number_format($report['pnl']['net_sales_usd'],2) }}</div>
        <div class="p-4 bg-slate-50 rounded-xl">صافي الربح التقديري $ {{ number_format($report['pnl']['estimated_net_profit_usd'],2) }}</div>
        <div class="p-4 bg-slate-50 rounded-xl">صندوق $ {{ number_format($report['cash']['usd']['balance'],2) }}</div>
        <div class="p-4 bg-slate-50 rounded-xl">صندوق ل.س {{ number_format($report['cash']['syp']['balance']) }}</div>
        <div class="p-4 bg-slate-50 rounded-xl">ذمم عملاء $ {{ number_format($report['bs']['ar_usd'],2) }}</div>
        <div class="p-4 bg-slate-50 rounded-xl">ذمم عملاء ل.س {{ number_format($report['bs']['ar_syp']) }}</div>
        <div class="p-4 bg-gold-50 rounded-xl">أصناف منخفضة {{ $report['low'] }}</div>
        <div class="p-4 bg-slate-50 rounded-xl">مسحوبات نهائية $ {{ number_format($report['pnl']['total_withdrawals_usd'],2) }}</div>
    </div>
</div>
@endsection
