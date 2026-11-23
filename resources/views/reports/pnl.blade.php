@extends('layouts.app')

@section('title', 'حساب الأرباح والخسائر')

@section('content')
@php
    $fxDiff = (float) ($pnl['fx_diff_usd'] ?? 0);
    $fxGain = (float) ($pnl['fx_gain_usd'] ?? ($fxDiff > 0 ? $fxDiff : 0));
    $fxLoss = (float) ($pnl['fx_loss_usd'] ?? ($fxDiff < 0 ? abs($fxDiff) : 0));
    $grossProfit = (float) $pnl['gross_profit_usd'];
    $netProfit = (float) $pnl['estimated_net_profit_usd'];
    $pnlTotal = (float) ($pnl['pnl_debit_total_usd'] ?? max(
        ($grossProfit < 0 ? abs($grossProfit) : 0) + $pnl['total_expenses_usd'] + $pnl['total_salaries_usd'] + $fxLoss,
        ($grossProfit > 0 ? $grossProfit : 0) + $fxGain
    ));
@endphp
<div class="max-w-5xl mx-auto space-y-5">
    @include('reports.partials.final-accounts-tabs')

    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
        <!-- Header -->
        <div class="border-b border-slate-200 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">حساب الأرباح والخسائر وقائمة الدخل (Profit &amp; Loss)</h1>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $shop->name }} · العملة الأساسية: <strong class="text-brand-800">الدولار الأمريكي (USD)</strong> ·
                    شامل فروق صرف العملة على سعر <span class="font-mono font-semibold text-slate-700" dir="ltr">{{ number_format($pnl['current_rate'] ?? ($shop->exchange_rate ?? 14500)) }}</span> ل.س
                </p>
            </div>
            <div class="no-print flex items-center gap-2">
                <a href="{{ route('reports.fx') }}" class="px-3.5 py-2 text-xs font-semibold border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-xl">
                    تفاصيل فروق الصرف
                </a>
                <button type="button" onclick="window.print()" class="px-4 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors">
                    طباعة الحساب
                </button>
            </div>
        </div>

        <!-- Classic Two-Sided T-Account for P&L -->
        <div class="grid grid-cols-1 md:grid-cols-2 border border-slate-300 rounded-2xl overflow-hidden text-xs">
            <!-- Debit Side / منه -->
            <div class="border-b md:border-b-0 md:border-l border-slate-300 flex flex-col">
                <div class="px-4 py-3 bg-brand-950 text-white font-bold flex items-center justify-between">
                    <span>منه (المصاريف والخسائر)</span>
                    <span class="font-mono text-[11px] text-brand-300">USD $</span>
                </div>
                <div class="divide-y divide-slate-100 flex-1">
                    @if($grossProfit < 0)
                        <div class="px-4 py-3 flex items-center justify-between gap-2 text-rose-700 font-semibold">
                            <span>مجمل الخسارة المرحّل من حساب المتاجرة</span>
                            <span class="font-mono" dir="ltr">${{ number_format(abs($grossProfit), 2) }}</span>
                        </div>
                    @endif
                    <div class="px-4 py-3 flex items-center justify-between gap-2">
                        <span class="text-slate-700 font-medium">مصاريف عامة وتشغيلية</span>
                        <span class="font-mono font-semibold text-slate-900" dir="ltr">${{ number_format($pnl['total_expenses_usd'], 2) }}</span>
                    </div>
                    <div class="px-4 py-3 flex items-center justify-between gap-2">
                        <span class="text-slate-700 font-medium">رواتب وأجور الموظفين</span>
                        <span class="font-mono font-semibold text-slate-900" dir="ltr">${{ number_format($pnl['total_salaries_usd'], 2) }}</span>
                    </div>
                    @if($fxLoss > 0)
                        <div class="px-4 py-3 flex items-center justify-between gap-2 text-rose-700">
                            <span class="font-medium">خسائر فروق صرف العملة</span>
                            <span class="font-mono font-semibold" dir="ltr">${{ number_format($fxLoss, 2) }}</span>
                        </div>
                    @endif
                    @if($netProfit > 0)
                        <div class="px-4 py-3 flex items-center justify-between gap-2 bg-brand-50/90 text-brand-900 font-bold">
                            <span>صافي الربح (يُرحّل إلى الميزانية العمومية)</span>
                            <span class="font-mono font-extrabold" dir="ltr">${{ number_format($netProfit, 2) }}</span>
                        </div>
                    @endif
                </div>
                <div class="px-4 py-3 bg-slate-100 border-t-2 border-slate-300 flex items-center justify-between font-extrabold text-slate-900">
                    <span>مجموع الجانب المدين</span>
                    <span class="font-mono text-sm" dir="ltr">${{ number_format($pnlTotal, 2) }}</span>
                </div>
            </div>

            <!-- Credit Side / له -->
            <div class="flex flex-col">
                <div class="px-4 py-3 bg-brand-950 text-white font-bold flex items-center justify-between">
                    <span>له (الإيرادات والأرباح)</span>
                    <span class="font-mono text-[11px] text-brand-300">USD $</span>
                </div>
                <div class="divide-y divide-slate-100 flex-1">
                    @if($grossProfit >= 0)
                        <div class="px-4 py-3 flex items-center justify-between gap-2">
                            <span class="text-slate-700 font-medium">مجمل الربح المرحّل من حساب المتاجرة</span>
                            <span class="font-mono font-semibold text-brand-800" dir="ltr">${{ number_format($grossProfit, 2) }}</span>
                        </div>
                    @endif
                    @if($fxGain > 0)
                        <div class="px-4 py-3 flex items-center justify-between gap-2 text-brand-700">
                            <span class="font-medium">أرباح فروق صرف العملة</span>
                            <span class="font-mono font-semibold" dir="ltr">${{ number_format($fxGain, 2) }}</span>
                        </div>
                    @endif
                    @if($netProfit < 0)
                        <div class="px-4 py-3 flex items-center justify-between gap-2 bg-rose-50 text-rose-800 font-bold">
                            <span>صافي الخسارة (يُرحّل إلى الميزانية العمومية)</span>
                            <span class="font-mono font-extrabold" dir="ltr">${{ number_format(abs($netProfit), 2) }}</span>
                        </div>
                    @endif
                </div>
                <div class="px-4 py-3 bg-slate-100 border-t-2 border-slate-300 flex items-center justify-between font-extrabold text-slate-900">
                    <span>مجموع الجانب الدائن</span>
                    <span class="font-mono text-sm" dir="ltr">${{ number_format($pnlTotal, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Detailed Vertical Income Statement -->
        <div class="space-y-2.5 font-mono text-xs divide-y divide-slate-100 pt-2">
            <h2 class="font-sans text-sm font-bold text-slate-800 pb-1">التحليل التفصيلي لقائمة الدخل (USD)</h2>

            <div class="pt-2 flex justify-between text-slate-800">
                <span class="font-sans font-semibold">إجمالي المبيعات قبل الحسم (Gross Sales)</span>
                <span class="font-bold" dir="ltr">${{ number_format($pnl['total_sales_usd'], 2) }}</span>
            </div>

            <div class="pt-2 flex justify-between text-amber-700">
                <span class="font-sans">(-) حسم ممنوح على المبيعات (Sales Discounts)</span>
                <span dir="ltr">-${{ number_format($pnl['sales_discount_usd'] ?? 0, 2) }}</span>
            </div>

            <div class="pt-2 flex justify-between text-slate-500">
                <span class="font-sans">(-) مردودات المبيعات (Sales Returns)</span>
                <span dir="ltr">-${{ number_format($pnl['total_sales_returns_usd'], 2) }}</span>
            </div>

            <div class="pt-2 flex justify-between text-slate-900 font-bold bg-slate-50 p-3 rounded-xl">
                <span class="font-sans">صافي المبيعات (Net Sales)</span>
                <span dir="ltr">${{ number_format($pnl['net_sales_usd'], 2) }}</span>
            </div>

            <div class="pt-3 flex justify-between text-rose-700">
                <span class="font-sans font-semibold">(-) تكلفة البضاعة المباعة (COGS حسب متوسط التكلفة WAC شاملة الشحن وحسم الشراء)</span>
                <span class="font-bold" dir="ltr">-${{ number_format($pnl['cogs_usd'], 2) }}</span>
            </div>

            @if(($pnl['total_shipping_usd'] ?? 0) > 0 || ($pnl['purchase_discount_usd'] ?? 0) > 0)
                <div class="pt-2 flex flex-wrap justify-between gap-2 text-[11px] text-slate-500 bg-slate-50/70 px-3 py-2 rounded-lg">
                    <span class="font-sans">أجور شحن مشتريات: +${{ number_format($pnl['total_shipping_usd'] ?? 0, 2) }}</span>
                    <span class="font-sans">حسم مكتسب على المشتريات: -${{ number_format($pnl['purchase_discount_usd'] ?? 0, 2) }}</span>
                </div>
            @endif

            <div class="pt-2 flex justify-between text-brand-900 font-bold bg-brand-50 p-3 rounded-xl">
                <span class="font-sans">مجمل الربح (Gross Profit)</span>
                <span dir="ltr">${{ number_format($pnl['gross_profit_usd'], 2) }}</span>
            </div>

            <div class="pt-3 flex justify-between text-slate-600">
                <span class="font-sans">(-) مصاريف عامة وتشغيلية</span>
                <span dir="ltr">-${{ number_format($pnl['total_expenses_usd'], 2) }}</span>
            </div>

            <div class="pt-2 flex justify-between text-slate-600">
                <span class="font-sans">(-) رواتب وأجور الموظفين</span>
                <span dir="ltr">-${{ number_format($pnl['total_salaries_usd'], 2) }}</span>
            </div>

            <div class="pt-2 flex justify-between {{ $fxDiff >= 0 ? 'text-brand-700' : 'text-rose-700' }}">
                <span class="font-sans font-semibold">({{ $fxDiff >= 0 ? '+' : '-' }}) صافي فروق صرف العملة (محقق وإعادة تقييم)</span>
                <span class="font-bold inline-block" dir="ltr">{{ $fxDiff >= 0 ? '+' : '-' }}${{ number_format(abs($fxDiff), 2) }}</span>
            </div>

            <div class="pt-4 flex justify-between p-4 rounded-2xl text-base font-extrabold {{ $netProfit >= 0 ? 'bg-brand-800 text-white' : 'bg-rose-600 text-white' }}">
                <span class="font-sans">{{ $netProfit >= 0 ? 'صافي الربح النهائي (Net Profit)' : 'صافي الخسارة النهائي (Net Loss)' }}</span>
                <span class="text-xl" dir="ltr">${{ number_format($netProfit, 2) }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
