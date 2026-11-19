@extends('layouts.app')
@section('title', 'المؤشرات الإدارية')
@section('content')
<div class="bg-white rounded-2xl p-6 border space-y-4">
    <h1 class="text-xl font-bold">المؤشرات الإدارية</h1>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
        <div class="p-4 bg-slate-50 rounded-xl">أرصدة العملاء $ {{ number_format($kpis['ar_usd'],2) }}</div>
        <div class="p-4 bg-slate-50 rounded-xl">عدد العملاء المدينين {{ $kpis['debtors'] }}</div>
        <div class="p-4 bg-slate-50 rounded-xl">نسبة المقبوضات/المبيعات {{ $kpis['receipt_ratio'] }}%</div>
        <div class="p-4 bg-gold-50 rounded-xl">أصناف منخفضة {{ $kpis['low_stock'] }}</div>
        <div class="p-4 bg-slate-50 rounded-xl">أصناف راكدة {{ $kpis['stale'] }}</div>
        <div class="p-4 bg-brand-50 rounded-xl">صافي ربح تقديري $ {{ number_format($kpis['net_profit'],2) }}</div>
    </div>
</div>
@endsection
