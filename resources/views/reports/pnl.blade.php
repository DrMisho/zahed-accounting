@extends('layouts.app')

@section('title', 'حساب الأرباح والخسائر')

@section('content')
@php
    $fxDiff = (float) ($pnl['fx_diff_usd'] ?? 0);
    $grossProfit = (float) $pnl['gross_profit_usd'];
    $netProfit = (float) $pnl['estimated_net_profit_usd'];
    $pnlTotal = (float) ($pnl['pnl_debit_total_usd'] ?? 0);
    $debitItems = $pnl['debit_items'] ?? [];
    $creditItems = $pnl['credit_items'] ?? [];
@endphp
<div class="max-w-5xl mx-auto space-y-5">
    @include('reports.partials.final-accounts-tabs')

    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 space-y-6">
        <!-- Header -->
        <div class="border-b border-slate-200 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">حساب الأرباح والخسائر وقائمة الدخل (من دفتر الأستاذ العام)</h1>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $shop->name }} · مصدر الأرقام: <strong class="text-brand-800">شجرة الحسابات ودفتر الأستاذ</strong> ·
                    العملة الأساسية: <strong class="text-slate-800">الدولار الأمريكي (USD)</strong>
                </p>
            </div>
            <div class="no-print flex flex-wrap items-center gap-2 text-xs">
                <a href="{{ route('reports.fx') }}" class="px-3.5 py-2 font-semibold border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-xl whitespace-nowrap">
                    فروق صرف العملة
                </a>
                <button type="button" onclick="window.print()" class="px-4 py-2 font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors whitespace-nowrap">
                    طباعة الحساب
                </button>
            </div>
        </div>

        <!-- Date Range Filter -->
        <form method="GET" action="{{ route('reports.pnl') }}" class="no-print flex flex-wrap items-end gap-3 text-xs">
            <div>
                <label for="pnl-from" class="block text-[11px] font-semibold text-slate-600 mb-1">من تاريخ</label>
                <input id="pnl-from" type="date" name="from" value="{{ $pnl['from'] ?? '' }}" class="p-2 rounded-xl border border-slate-300 font-mono">
            </div>
            <div>
                <label for="pnl-to" class="block text-[11px] font-semibold text-slate-600 mb-1">إلى تاريخ</label>
                <input id="pnl-to" type="date" name="to" value="{{ $pnl['to'] ?? '' }}" class="p-2 rounded-xl border border-slate-300 font-mono">
            </div>
            <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl">عرض</button>
            @if(!empty($pnl['from']) || !empty($pnl['to']))
                <a href="{{ route('reports.pnl') }}" class="px-3 py-2 border border-slate-300 text-slate-600 hover:bg-slate-50 rounded-xl">الكل</a>
            @endif
        </form>

        <!-- Classic Two-Sided GL T-Account for P&L -->
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
                            <span class="font-mono whitespace-nowrap" dir="ltr">${{ number_format(abs($grossProfit), 2) }}</span>
                        </div>
                    @endif
                    @foreach($debitItems as $item)
                        <div class="px-4 py-3 flex items-center justify-between gap-2 hover:bg-slate-50/70">
                            <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="text-slate-800 font-medium hover:text-brand-700 hover:underline">
                                <span class="font-mono text-slate-400 ml-1">{{ $item['code'] }}</span>
                                {{ $item['label'] }}
                            </a>
                            <span class="font-mono font-semibold text-slate-900 whitespace-nowrap" dir="ltr">${{ number_format($item['amount_usd'], 2) }}</span>
                        </div>
                    @endforeach
                    @if($netProfit > 0)
                        <div class="px-4 py-3 flex items-center justify-between gap-2 bg-brand-50/90 text-brand-900 font-bold">
                            <span>صافي الربح (يُرحّل إلى الميزانية العمومية)</span>
                            <span class="font-mono font-extrabold whitespace-nowrap" dir="ltr">${{ number_format($netProfit, 2) }}</span>
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
                            <span class="text-slate-800 font-medium">مجمل الربح المرحّل من حساب المتاجرة</span>
                            <span class="font-mono font-semibold text-brand-800 whitespace-nowrap" dir="ltr">${{ number_format($grossProfit, 2) }}</span>
                        </div>
                    @endif
                    @foreach($creditItems as $item)
                        <div class="px-4 py-3 flex items-center justify-between gap-2 hover:bg-slate-50/70">
                            <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="text-slate-800 font-medium hover:text-brand-700 hover:underline">
                                <span class="font-mono text-slate-400 ml-1">{{ $item['code'] }}</span>
                                {{ $item['label'] }}
                            </a>
                            <span class="font-mono font-semibold text-brand-700 whitespace-nowrap" dir="ltr">${{ number_format($item['amount_usd'], 2) }}</span>
                        </div>
                    @endforeach
                    @if($netProfit < 0)
                        <div class="px-4 py-3 flex items-center justify-between gap-2 bg-rose-50 text-rose-800 font-bold">
                            <span>صافي الخسارة (يُرحّل إلى الميزانية العمومية)</span>
                            <span class="font-mono font-extrabold whitespace-nowrap" dir="ltr">${{ number_format(abs($netProfit), 2) }}</span>
                        </div>
                    @endif
                </div>
                <div class="px-4 py-3 bg-slate-100 border-t-2 border-slate-300 flex items-center justify-between font-extrabold text-slate-900">
                    <span>مجموع الجانب الدائن</span>
                    <span class="font-mono text-sm" dir="ltr">${{ number_format($pnlTotal, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Vertical Summary Statement -->
        <div class="space-y-2.5 font-mono text-xs divide-y divide-slate-100 pt-2">
            <h2 class="font-sans text-sm font-bold text-slate-800 pb-1">ملخص قائمة الدخل (من دفتر الأستاذ العام — USD)</h2>

            <div class="pt-2 flex justify-between text-slate-800">
                <span class="font-sans font-semibold">إجمالي المبيعات (4111)</span>
                <span class="font-bold" dir="ltr">${{ number_format($pnl['total_sales_usd'], 2) }}</span>
            </div>
            <div class="pt-2 flex justify-between text-amber-700">
                <span class="font-sans">(-) الحسم الممنوح للمبيعات (4113)</span>
                <span dir="ltr">-${{ number_format($pnl['sales_discount_usd'] ?? 0, 2) }}</span>
            </div>
            <div class="pt-2 flex justify-between text-slate-500">
                <span class="font-sans">(-) مردودات المبيعات (4112)</span>
                <span dir="ltr">-${{ number_format($pnl['total_sales_returns_usd'], 2) }}</span>
            </div>
            <div class="pt-2 flex justify-between text-slate-900 font-bold bg-slate-50 p-3 rounded-xl">
                <span class="font-sans">صافي المبيعات (Net Sales)</span>
                <span dir="ltr">${{ number_format($pnl['net_sales_usd'], 2) }}</span>
            </div>
            <div class="pt-3 flex justify-between text-rose-700">
                <span class="font-sans font-semibold">(-) صافي تكلفة البضاعة المباعة (حسابات 51xx)</span>
                <span class="font-bold" dir="ltr">-${{ number_format($pnl['cogs_usd'], 2) }}</span>
            </div>
            <div class="pt-2 flex justify-between text-brand-900 font-bold bg-brand-50 p-3 rounded-xl">
                <span class="font-sans">مجمل الربح / الخسارة (Gross Profit)</span>
                <span dir="ltr">{{ $grossProfit < 0 ? '-' : '' }}${{ number_format(abs($grossProfit), 2) }}</span>
            </div>
            @if(($pnl['other_revenues_usd'] ?? 0) > 0)
                <div class="pt-2 flex justify-between text-brand-700">
                    <span class="font-sans">(+) إيرادات متنوعة أخرى (4211)</span>
                    <span dir="ltr">+${{ number_format($pnl['other_revenues_usd'], 2) }}</span>
                </div>
            @endif
            <div class="pt-3 flex justify-between text-slate-600">
                <span class="font-sans">(-) المصاريف التشغيلية والإدارية (حسابات 52xx)</span>
                <span dir="ltr">-${{ number_format($pnl['total_expenses_usd'], 2) }}</span>
            </div>
            <div class="pt-2 flex justify-between text-slate-600">
                <span class="font-sans">(-) الرواتب والأجور (5211)</span>
                <span dir="ltr">-${{ number_format($pnl['total_salaries_usd'], 2) }}</span>
            </div>
            <div class="pt-2 flex justify-between {{ $fxDiff >= 0 ? 'text-brand-700' : 'text-rose-700' }}">
                <span class="font-sans font-semibold">({{ $fxDiff >= 0 ? '+' : '-' }}) صافي فروق صرف العملة المرحلة (4212 − 5311)</span>
                <span class="font-bold inline-block" dir="ltr">{{ $fxDiff >= 0 ? '+' : '-' }}${{ number_format(abs($fxDiff), 2) }}</span>
            </div>
            <div class="pt-4 flex justify-between p-4 rounded-2xl text-base font-extrabold {{ $netProfit >= 0 ? 'bg-brand-800 text-white' : 'bg-rose-600 text-white' }}">
                <span class="font-sans">{{ $netProfit >= 0 ? 'صافي الربح النهائي (Net Profit)' : 'صافي الخسارة النهائي (Net Loss)' }}</span>
                <span class="text-xl" dir="ltr">{{ $netProfit < 0 ? '-' : '' }}${{ number_format(abs($netProfit), 2) }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
