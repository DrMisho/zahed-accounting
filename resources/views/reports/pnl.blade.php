@extends('layouts.app')

@section('title', 'قائمة الأرباح والخسائر')

@section('content')
<div class="max-w-3xl mx-auto bg-white rounded-3xl p-8 border border-slate-200 shadow-sm space-y-6">
    <div class="border-b pb-4 flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold text-slate-900">قائمة الدخل وصافي الأرباح التقديرية (SRS 4.د)</h1>
            <p class="text-xs text-slate-500">{{ $shop->name }} · محسوبة بالدولار الأمريكي (USD)</p>
        </div>
        <button onclick="window.print()" class="no-print px-4 py-2 text-xs font-semibold text-white bg-brand-900 hover:bg-brand-800 rounded-xl">
            طباعة 🖨️
        </button>
    </div>

    <div class="p-4 bg-brand-50 rounded-2xl text-xs text-brand-900 leading-relaxed border border-brand-200">
        <strong>معادلة الوثيقة:</strong> صافي الربح التقديري = (إجمالي المبيعات بالدولار) - (تكلفة البضاعة المباعة COGS بالدولار) - (المصاريف العامة والرواتب بالدولار).
    </div>

    <div class="space-y-3 font-mono text-xs divide-y divide-slate-100">
        <div class="pt-2 flex justify-between text-slate-800">
            <span class="font-sans font-semibold">إجمالي المبيعات قبل الحسم (Gross Sales)</span>
            <span class="font-bold">${{ number_format($pnl['total_sales_usd'], 2) }}</span>
        </div>

        <div class="pt-2 flex justify-between text-amber-700">
            <span class="font-sans">(-) حسم ممنوح على المبيعات (Sales Discounts)</span>
            <span>-${{ number_format($pnl['sales_discount_usd'] ?? 0, 2) }}</span>
        </div>

        <div class="pt-2 flex justify-between text-slate-500">
            <span class="font-sans">(-) مردودات المبيعات (Sales Returns)</span>
            <span>-${{ number_format($pnl['total_sales_returns_usd'], 2) }}</span>
        </div>

        <div class="pt-2 flex justify-between text-slate-900 font-bold bg-slate-50 p-3 rounded-xl">
            <span class="font-sans">صافي المبيعات (Net Sales)</span>
            <span>${{ number_format($pnl['net_sales_usd'], 2) }}</span>
        </div>

        <div class="pt-3 flex justify-between text-rose-700">
            <span class="font-sans font-semibold">(-) تكلفة البضاعة المباعة (COGS حسب متوسط التكلفة WAC شاملة الشحن وحسم الشراء)</span>
            <span class="font-bold">-${{ number_format($pnl['cogs_usd'], 2) }}</span>
        </div>

        @if(($pnl['total_shipping_usd'] ?? 0) > 0 || ($pnl['purchase_discount_usd'] ?? 0) > 0)
            <div class="pt-2 flex flex-wrap justify-between gap-2 text-[11px] text-slate-500 bg-slate-50/70 px-3 py-2 rounded-lg">
                <span class="font-sans">تفاصيل تكلفة المشتريات (مضمّنة في WAC): أجور شحن مشتريات +${{ number_format($pnl['total_shipping_usd'] ?? 0, 2) }}</span>
                <span class="font-sans">حسم مكتسب على المشتريات -${{ number_format($pnl['purchase_discount_usd'] ?? 0, 2) }}</span>
            </div>
        @endif

        <div class="pt-2 flex justify-between text-brand-900 font-bold bg-brand-50 p-3 rounded-xl">
            <span class="font-sans">مجمل الربح (Gross Profit)</span>
            <span>${{ number_format($pnl['gross_profit_usd'], 2) }}</span>
        </div>

        <div class="pt-3 flex justify-between text-slate-600">
            <span class="font-sans">(-) مصاريف عامة وتشغيلية</span>
            <span>-${{ number_format($pnl['total_expenses_usd'], 2) }}</span>
        </div>

        <div class="pt-2 flex justify-between text-slate-600">
            <span class="font-sans">(-) رواتب وأجور الموظفين</span>
            <span>-${{ number_format($pnl['total_salaries_usd'], 2) }}</span>
        </div>

        <div class="pt-4 flex justify-between p-4 rounded-2xl text-base font-extrabold {{ $pnl['estimated_net_profit_usd'] >= 0 ? 'bg-brand-800 text-white' : 'bg-rose-600 text-white' }}">
            <span class="font-sans">صافي الربح التقديري النهائي (Net Profit)</span>
            <span class="text-xl">${{ number_format($pnl['estimated_net_profit_usd'], 2) }}</span>
        </div>
    </div>
</div>
@endsection
