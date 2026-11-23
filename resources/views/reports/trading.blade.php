@extends('layouts.app')

@section('title', 'حساب المتاجرة')

@section('content')
<div class="max-w-5xl mx-auto space-y-5">
    @include('reports.partials.final-accounts-tabs')

    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">حساب المتاجرة (Trading Account)</h1>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $shop->name }} · العملة الأساسية: <strong class="text-brand-800">الدولار الأمريكي (USD)</strong> ·
                    سعر الصرف المعتمد <span class="font-mono font-semibold text-slate-700" dir="ltr">{{ number_format($trading['current_rate']) }}</span> ل.س
                </p>
            </div>
            <div class="no-print flex items-center gap-2">
                <a href="{{ route('reports.pnl') }}" class="px-3.5 py-2 text-xs font-semibold border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-xl">
                    الأرباح والخسائر
                </a>
                <button type="button" onclick="window.print()" class="px-4 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors">
                    طباعة الحساب
                </button>
            </div>
        </div>

        <!-- Result Banner -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-500">صافي المبيعات (Net Sales)</span>
                <strong class="block mt-1 text-lg font-mono font-bold text-slate-900" dir="ltr">${{ number_format($trading['net_sales_usd'], 2) }}</strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-500">تكلفة المبيعات الدورية (COGS)</span>
                <strong class="block mt-1 text-lg font-mono font-bold text-rose-700" dir="ltr">${{ number_format($trading['periodic_cogs_usd'], 2) }}</strong>
            </div>
            <div class="p-4 rounded-xl border {{ $trading['trading_result_usd'] >= 0 ? 'bg-brand-50 border-brand-200' : 'bg-rose-50 border-rose-200' }}">
                <span class="block text-[11px] font-semibold {{ $trading['trading_result_usd'] >= 0 ? 'text-brand-800' : 'text-rose-800' }}">
                    {{ $trading['trading_result_usd'] >= 0 ? 'مجمل الربح المرحّل إلى أ.خ' : 'مجمل الخسارة المرحّل إلى أ.خ' }}
                </span>
                <strong class="block mt-1 text-lg font-mono font-extrabold {{ $trading['trading_result_usd'] >= 0 ? 'text-brand-950' : 'text-rose-700' }}" dir="ltr">
                    ${{ number_format(abs($trading['trading_result_usd']), 2) }}
                </strong>
            </div>
        </div>

        <!-- Classic Two-Sided T-Account -->
        <div class="grid grid-cols-1 md:grid-cols-2 border border-slate-300 rounded-2xl overflow-hidden text-xs">
            <!-- Right Side (RTL): Debit / منه -->
            <div class="border-b md:border-b-0 md:border-l border-slate-300 flex flex-col">
                <div class="px-4 py-3 bg-brand-950 text-white font-bold flex items-center justify-between">
                    <span>منه (الجانب المدين)</span>
                    <span class="font-mono text-[11px] text-brand-300">USD $</span>
                </div>
                <div class="divide-y divide-slate-100 flex-1">
                    @foreach($trading['debit_items'] as $item)
                        <div class="px-4 py-3 flex items-center justify-between gap-2">
                            <span class="text-slate-700 font-medium">{{ $item['label'] }}</span>
                            <span class="font-mono font-semibold text-slate-900" dir="ltr">${{ number_format($item['amount_usd'], 2) }}</span>
                        </div>
                    @endforeach
                    @if($trading['gross_profit_usd'] > 0)
                        <div class="px-4 py-3 flex items-center justify-between gap-2 bg-brand-50/90 text-brand-900 font-bold">
                            <span>مجمل الربح (يُرحّل إلى حساب الأرباح والخسائر)</span>
                            <span class="font-mono font-extrabold" dir="ltr">${{ number_format($trading['gross_profit_usd'], 2) }}</span>
                        </div>
                    @endif
                </div>
                <div class="px-4 py-3 bg-slate-100 border-t-2 border-slate-300 flex items-center justify-between font-extrabold text-slate-900">
                    <span>مجموع الجانب المدين</span>
                    <span class="font-mono text-sm" dir="ltr">${{ number_format($trading['debit_total_usd'], 2) }}</span>
                </div>
            </div>

            <!-- Left Side (RTL): Credit / له -->
            <div class="flex flex-col">
                <div class="px-4 py-3 bg-brand-950 text-white font-bold flex items-center justify-between">
                    <span>له (الجانب الدائن)</span>
                    <span class="font-mono text-[11px] text-brand-300">USD $</span>
                </div>
                <div class="divide-y divide-slate-100 flex-1">
                    @foreach($trading['credit_items'] as $item)
                        <div class="px-4 py-3 flex items-center justify-between gap-2">
                            <span class="text-slate-700 font-medium">{{ $item['label'] }}</span>
                            <span class="font-mono font-semibold text-slate-900" dir="ltr">${{ number_format($item['amount_usd'], 2) }}</span>
                        </div>
                    @endforeach
                    @if($trading['gross_loss_usd'] > 0)
                        <div class="px-4 py-3 flex items-center justify-between gap-2 bg-rose-50 text-rose-800 font-bold">
                            <span>مجمل الخسارة (يُرحّل إلى حساب الأرباح والخسائر)</span>
                            <span class="font-mono font-extrabold" dir="ltr">${{ number_format($trading['gross_loss_usd'], 2) }}</span>
                        </div>
                    @endif
                </div>
                <div class="px-4 py-3 bg-slate-100 border-t-2 border-slate-300 flex items-center justify-between font-extrabold text-slate-900">
                    <span>مجموع الجانب الدائن</span>
                    <span class="font-mono text-sm" dir="ltr">${{ number_format($trading['credit_total_usd'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
