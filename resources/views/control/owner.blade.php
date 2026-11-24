@extends('layouts.app')
@section('title', 'تقرير صاحب العمل')
@section('content')
@php
    $netSales = (float) ($report['pnl']['net_sales_usd'] ?? 0);
    $netProfit = (float) ($report['pnl']['estimated_net_profit_usd'] ?? 0);
    $withdrawals = (float) ($report['pnl']['total_withdrawals_usd'] ?? ($report['bs']['withdrawals_usd'] ?? 0));
@endphp
<div class="bg-white rounded-2xl p-6 border border-slate-200 space-y-4">
    <h1 class="text-xl font-bold text-slate-900">تقرير صاحب المحل</h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <div class="p-4 bg-slate-50 rounded-xl flex justify-between"><span>صافي المبيعات</span><strong class="font-mono" dir="ltr">${{ number_format($netSales, 2) }}</strong></div>
        <div class="p-4 bg-slate-50 rounded-xl flex justify-between"><span>صافي الربح التقديري</span><strong class="font-mono {{ $netProfit >= 0 ? 'text-brand-700' : 'text-rose-600' }}" dir="ltr">{{ $netProfit < 0 ? '-' : '' }}${{ number_format(abs($netProfit), 2) }}</strong></div>
        <div class="p-4 bg-slate-50 rounded-xl flex justify-between"><span>صندوق دولار (USD)</span><strong class="font-mono" dir="ltr">${{ number_format((float) ($report['cash']['usd']['balance'] ?? 0), 2) }}</strong></div>
        <div class="p-4 bg-slate-50 rounded-xl flex justify-between"><span>صندوق ليرة (SYP)</span><strong class="font-mono" dir="ltr">{{ number_format((float) ($report['cash']['syp']['balance'] ?? 0)) }} ل.س</strong></div>
        <div class="p-4 bg-slate-50 rounded-xl flex justify-between"><span>ذمم عملاء (USD)</span><strong class="font-mono" dir="ltr">${{ number_format((float) ($report['bs']['ar_usd'] ?? 0), 2) }}</strong></div>
        <div class="p-4 bg-slate-50 rounded-xl flex justify-between"><span>ذمم عملاء (SYP)</span><strong class="font-mono" dir="ltr">{{ number_format((float) ($report['bs']['ar_syp'] ?? 0)) }} ل.س</strong></div>
        <div class="p-4 bg-amber-50 rounded-xl flex justify-between"><span>أصناف منخفضة</span><strong class="font-mono">{{ (int) ($report['low'] ?? 0) }}</strong></div>
        <div class="p-4 bg-slate-50 rounded-xl flex justify-between"><span>مسحوبات الشركاء</span><strong class="font-mono" dir="ltr">${{ number_format($withdrawals, 2) }}</strong></div>
    </div>
</div>
@endsection
