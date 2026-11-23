@extends('layouts.app')

@section('title', 'الحسابات الختامية والميزانية العمومية')

@section('content')
@php
    $fxDiff = (float) ($pnl['fx_diff_usd'] ?? 0);
    $fxGain = (float) ($pnl['fx_gain_usd'] ?? ($fxDiff > 0 ? $fxDiff : 0));
    $fxLoss = (float) ($pnl['fx_loss_usd'] ?? ($fxDiff < 0 ? abs($fxDiff) : 0));
    $grossProfit = (float) $pnl['gross_profit_usd'];
    $netProfit = (float) $pnl['estimated_net_profit_usd'];
    $pnlTotal = (float) ($pnl['pnl_debit_total_usd'] ?? 0);
@endphp
<div class="max-w-5xl mx-auto space-y-6">
    @include('reports.partials.final-accounts-tabs')

    <!-- Executive Header -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">الحسابات الختامية الشاملة — {{ $shop->name }}</h1>
            <p class="text-xs text-slate-500 mt-1">
                حساب المتاجرة + حساب الأرباح والخسائر + الميزانية العمومية · العملة الأساسية: <strong class="text-brand-800">الدولار الأمريكي (USD)</strong> ·
                سعر الصرف: <span class="font-mono font-semibold text-slate-800" dir="ltr">{{ number_format($bs['current_rate']) }}</span> ل.س
            </p>
        </div>
        <button type="button" onclick="window.print()" class="no-print px-4 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors self-start sm:self-center">
            طباعة الحسابات الختامية
        </button>
    </div>

    <!-- Key Highlights -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
        <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-sm">
            <span class="block text-[11px] text-slate-500">مجمل الربح (المتاجرة)</span>
            <strong class="block mt-1 text-lg font-mono font-extrabold {{ $trading['trading_result_usd'] >= 0 ? 'text-brand-800' : 'text-rose-700' }}" dir="ltr">
                ${{ number_format($trading['trading_result_usd'], 2) }}
            </strong>
        </div>
        <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-sm">
            <span class="block text-[11px] text-slate-500">فروق صرف العملة</span>
            <strong class="block mt-1 text-lg font-mono font-extrabold {{ $fxDiff >= 0 ? 'text-brand-700' : 'text-rose-600' }}" dir="ltr">
                {{ $fxDiff >= 0 ? '+' : '-' }}${{ number_format(abs($fxDiff), 2) }}
            </strong>
        </div>
        <div class="p-4 rounded-xl bg-brand-50 border border-brand-200 shadow-sm">
            <span class="block text-[11px] text-brand-800 font-semibold">صافي الربح (الأرباح والخسائر)</span>
            <strong class="block mt-1 text-lg font-mono font-extrabold {{ $netProfit >= 0 ? 'text-brand-950' : 'text-rose-700' }}" dir="ltr">
                ${{ number_format($netProfit, 2) }}
            </strong>
        </div>
        <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-sm">
            <span class="block text-[11px] text-slate-500">إجمالي الميزانية العمومية</span>
            <strong class="block mt-1 text-lg font-mono font-extrabold text-slate-900" dir="ltr">
                ${{ number_format($bs['total_assets_usd'], 2) }}
            </strong>
        </div>
    </div>

    <!-- 1. Trading Account -->
    <section class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">1) حساب المتاجرة</h2>
            <a href="{{ route('reports.trading') }}" class="no-print text-xs font-semibold text-brand-700 hover:underline">عرض مستقل</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 border border-slate-300 rounded-xl overflow-hidden text-xs">
            <div class="border-b md:border-b-0 md:border-l border-slate-300 flex flex-col">
                <div class="px-4 py-2.5 bg-brand-950 text-white font-bold">منه (مدين)</div>
                <div class="divide-y divide-slate-100 flex-1">
                    @foreach($trading['debit_items'] as $item)
                        <div class="px-4 py-2.5 flex justify-between">
                            <span>{{ $item['label'] }}</span>
                            <span class="font-mono font-semibold" dir="ltr">${{ number_format($item['amount_usd'], 2) }}</span>
                        </div>
                    @endforeach
                    @if($trading['gross_profit_usd'] > 0)
                        <div class="px-4 py-2.5 flex justify-between bg-brand-50 text-brand-900 font-bold">
                            <span>مجمل الربح (مرحّل إلى أ.خ)</span>
                            <span class="font-mono" dir="ltr">${{ number_format($trading['gross_profit_usd'], 2) }}</span>
                        </div>
                    @endif
                </div>
                <div class="px-4 py-2.5 bg-slate-100 border-t border-slate-300 flex justify-between font-extrabold">
                    <span>المجموع</span>
                    <span class="font-mono" dir="ltr">${{ number_format($trading['debit_total_usd'], 2) }}</span>
                </div>
            </div>
            <div class="flex flex-col">
                <div class="px-4 py-2.5 bg-brand-950 text-white font-bold">له (دائن)</div>
                <div class="divide-y divide-slate-100 flex-1">
                    @foreach($trading['credit_items'] as $item)
                        <div class="px-4 py-2.5 flex justify-between">
                            <span>{{ $item['label'] }}</span>
                            <span class="font-mono font-semibold" dir="ltr">${{ number_format($item['amount_usd'], 2) }}</span>
                        </div>
                    @endforeach
                    @if($trading['gross_loss_usd'] > 0)
                        <div class="px-4 py-2.5 flex justify-between bg-rose-50 text-rose-800 font-bold">
                            <span>مجمل الخسارة (مرحّل إلى أ.خ)</span>
                            <span class="font-mono" dir="ltr">${{ number_format($trading['gross_loss_usd'], 2) }}</span>
                        </div>
                    @endif
                </div>
                <div class="px-4 py-2.5 bg-slate-100 border-t border-slate-300 flex justify-between font-extrabold">
                    <span>المجموع</span>
                    <span class="font-mono" dir="ltr">${{ number_format($trading['credit_total_usd'], 2) }}</span>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. Profit & Loss Account -->
    <section class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">2) حساب الأرباح والخسائر</h2>
            <a href="{{ route('reports.pnl') }}" class="no-print text-xs font-semibold text-brand-700 hover:underline">عرض مستقل</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 border border-slate-300 rounded-xl overflow-hidden text-xs">
            <div class="border-b md:border-b-0 md:border-l border-slate-300 flex flex-col">
                <div class="px-4 py-2.5 bg-brand-950 text-white font-bold">منه (مصاريف وخسائر)</div>
                <div class="divide-y divide-slate-100 flex-1">
                    @if($grossProfit < 0)
                        <div class="px-4 py-2.5 flex justify-between text-rose-700 font-semibold">
                            <span>مجمل الخسارة</span>
                            <span class="font-mono" dir="ltr">${{ number_format(abs($grossProfit), 2) }}</span>
                        </div>
                    @endif
                    <div class="px-4 py-2.5 flex justify-between">
                        <span>مصاريف عامة وتشغيلية</span>
                        <span class="font-mono font-semibold" dir="ltr">${{ number_format($pnl['total_expenses_usd'], 2) }}</span>
                    </div>
                    <div class="px-4 py-2.5 flex justify-between">
                        <span>رواتب وأجور الموظفين</span>
                        <span class="font-mono font-semibold" dir="ltr">${{ number_format($pnl['total_salaries_usd'], 2) }}</span>
                    </div>
                    @if($fxLoss > 0)
                        <div class="px-4 py-2.5 flex justify-between text-rose-700">
                            <span>خسائر فروق صرف العملة</span>
                            <span class="font-mono font-semibold" dir="ltr">${{ number_format($fxLoss, 2) }}</span>
                        </div>
                    @endif
                    @if($netProfit > 0)
                        <div class="px-4 py-2.5 flex justify-between bg-brand-50 text-brand-900 font-bold">
                            <span>صافي الربح (مرحّل للميزانية)</span>
                            <span class="font-mono" dir="ltr">${{ number_format($netProfit, 2) }}</span>
                        </div>
                    @endif
                </div>
                <div class="px-4 py-2.5 bg-slate-100 border-t border-slate-300 flex justify-between font-extrabold">
                    <span>المجموع</span>
                    <span class="font-mono" dir="ltr">${{ number_format($pnlTotal, 2) }}</span>
                </div>
            </div>
            <div class="flex flex-col">
                <div class="px-4 py-2.5 bg-brand-950 text-white font-bold">له (إيرادات وأرباح)</div>
                <div class="divide-y divide-slate-100 flex-1">
                    @if($grossProfit >= 0)
                        <div class="px-4 py-2.5 flex justify-between">
                            <span>مجمل الربح المرحّل</span>
                            <span class="font-mono font-semibold text-brand-800" dir="ltr">${{ number_format($grossProfit, 2) }}</span>
                        </div>
                    @endif
                    @if($fxGain > 0)
                        <div class="px-4 py-2.5 flex justify-between text-brand-700">
                            <span>أرباح فروق صرف العملة</span>
                            <span class="font-mono font-semibold" dir="ltr">${{ number_format($fxGain, 2) }}</span>
                        </div>
                    @endif
                    @if($netProfit < 0)
                        <div class="px-4 py-2.5 flex justify-between bg-rose-50 text-rose-800 font-bold">
                            <span>صافي الخسارة (مرحّل للميزانية)</span>
                            <span class="font-mono" dir="ltr">${{ number_format(abs($netProfit), 2) }}</span>
                        </div>
                    @endif
                </div>
                <div class="px-4 py-2.5 bg-slate-100 border-t border-slate-300 flex justify-between font-extrabold">
                    <span>المجموع</span>
                    <span class="font-mono" dir="ltr">${{ number_format($pnlTotal, 2) }}</span>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Balance Sheet -->
    <section class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">3) الميزانية العمومية (المركز المالي)</h2>
            <a href="{{ route('reports.balance') }}" class="no-print text-xs font-semibold text-brand-700 hover:underline">عرض مستقل</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 border border-slate-300 rounded-xl overflow-hidden text-xs">
            <div class="border-b md:border-b-0 md:border-l border-slate-300 flex flex-col">
                <div class="px-4 py-2.5 bg-brand-950 text-white font-bold">الموجودات (الأصول)</div>
                <div class="divide-y divide-slate-100 flex-1">
                    <div class="px-4 py-2.5 flex justify-between">
                        <span>النقدية في الصندوق (دولار + ل.س معادل)</span>
                        <span class="font-mono font-semibold" dir="ltr">${{ number_format($bs['total_cash_usd'], 2) }}</span>
                    </div>
                    <div class="px-4 py-2.5 flex justify-between">
                        <span>ذمم العملاء (دولار + ل.س معادل)</span>
                        <span class="font-mono font-semibold" dir="ltr">${{ number_format($bs['total_ar_usd'], 2) }}</span>
                    </div>
                    <div class="px-4 py-2.5 flex justify-between">
                        <span>بضاعة آخر المدة (المخزون)</span>
                        <span class="font-mono font-semibold" dir="ltr">${{ number_format($bs['inventory_usd'], 2) }}</span>
                    </div>
                </div>
                <div class="px-4 py-2.5 bg-slate-100 border-t border-slate-300 flex justify-between font-extrabold">
                    <span>مجموع الموجودات</span>
                    <span class="font-mono" dir="ltr">${{ number_format($bs['total_assets_usd'], 2) }}</span>
                </div>
            </div>
            <div class="flex flex-col">
                <div class="px-4 py-2.5 bg-brand-950 text-white font-bold">المطاليب وحقوق الملكية</div>
                <div class="divide-y divide-slate-100 flex-1">
                    <div class="px-4 py-2.5 flex justify-between">
                        <span>ذمم الموردين (دولار + ل.س معادل)</span>
                        <span class="font-mono font-semibold text-rose-700" dir="ltr">${{ number_format($bs['total_ap_usd'], 2) }}</span>
                    </div>
                    <div class="px-4 py-2.5 flex justify-between">
                        <span>رأس المال والأرصدة المدوّرة</span>
                        <span class="font-mono font-semibold" dir="ltr">${{ number_format($bs['capital_usd'], 2) }}</span>
                    </div>
                    <div class="px-4 py-2.5 flex justify-between">
                        <span>صافي الربح / الخسارة</span>
                        <span class="font-mono font-bold {{ $bs['profit_usd'] >= 0 ? 'text-brand-700' : 'text-rose-700' }}" dir="ltr">${{ number_format($bs['profit_usd'], 2) }}</span>
                    </div>
                    <div class="px-4 py-2.5 flex justify-between">
                        <span>(-) مسحوبات الشركاء</span>
                        <span class="font-mono font-semibold text-amber-700" dir="ltr">-${{ number_format($bs['withdrawals_usd'], 2) }}</span>
                    </div>
                </div>
                <div class="px-4 py-2.5 bg-slate-100 border-t border-slate-300 flex justify-between font-extrabold">
                    <span>مجموع المطاليب وحقوق الملكية</span>
                    <span class="font-mono" dir="ltr">${{ number_format($bs['total_liabilities_and_equity_usd'], 2) }}</span>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
