@extends('layouts.app')
@section('title', 'التقرير اليومي')
@section('content')
<div class="bg-white rounded-2xl p-6 border space-y-4">
    <h1 class="text-xl font-bold">التقرير اليومي</h1>
    <form class="flex gap-2 text-xs">
        <input type="date" name="date" value="{{ $date }}" class="p-2 rounded-xl border">
        <button class="px-3 py-2 bg-brand-900 text-white rounded-xl">عرض</button>
    </form>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
        <div class="p-4 bg-slate-50 rounded-xl">صافي المبيعات $ {{ number_format($report['net_sales_usd'],2) }}</div>
        <div class="p-4 bg-slate-50 rounded-xl">مقبوضات $ {{ number_format($report['receipts_usd'],2) }}</div>
        <div class="p-4 bg-slate-50 rounded-xl">مدفوعات $ {{ number_format($report['payments_usd'],2) }}</div>
        <div class="p-4 bg-slate-50 rounded-xl">مصاريف $ {{ number_format($report['expenses_usd'],2) }}</div>
        <div class="p-4 bg-slate-50 rounded-xl">عدد العمليات {{ $report['operations'] }}</div>
        <div class="p-4 bg-slate-50 rounded-xl">صافي صندوق اليوم $ {{ number_format($report['cash_usd'],2) }}</div>
    </div>
</div>
@endsection
