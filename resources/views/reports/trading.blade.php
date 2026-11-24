@extends('layouts.app')

@section('title', 'حساب المتاجرة')

@section('content')
<div class="max-w-5xl mx-auto space-y-5">
    @include('reports.partials.final-accounts-tabs')

    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">حساب المتاجرة (من دفتر الأستاذ العام)</h1>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $shop->name }} · مصدر الأرقام: <strong class="text-brand-800">شجرة الحسابات ودفتر الأستاذ</strong> ·
                    العملة الأساسية: <strong class="text-slate-800">الدولار الأمريكي (USD)</strong>
                </p>
            </div>
            <div class="no-print flex flex-wrap items-center gap-2 text-xs">
                <a href="{{ route('books.trial') }}" class="px-3.5 py-2 font-semibold border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-xl whitespace-nowrap">
                    ميزان المراجعة
                </a>
                <button type="button" onclick="window.print()" class="px-4 py-2 font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors whitespace-nowrap">
                    طباعة الحساب
                </button>
            </div>
        </div>

        <!-- Date Range Filter -->
        <form method="GET" action="{{ route('reports.trading') }}" class="no-print flex flex-wrap items-end gap-3 text-xs">
            <div>
                <label for="tr-from" class="block text-[11px] font-semibold text-slate-600 mb-1">من تاريخ</label>
                <input id="tr-from" type="date" name="from" value="{{ $trading['from'] ?? '' }}" class="p-2 rounded-xl border border-slate-300 font-mono">
            </div>
            <div>
                <label for="tr-to" class="block text-[11px] font-semibold text-slate-600 mb-1">إلى تاريخ</label>
                <input id="tr-to" type="date" name="to" value="{{ $trading['to'] ?? '' }}" class="p-2 rounded-xl border border-slate-300 font-mono">
            </div>
            <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl">عرض</button>
            @if(!empty($trading['from']) || !empty($trading['to']))
                <a href="{{ route('reports.trading') }}" class="px-3 py-2 border border-slate-300 text-slate-600 hover:bg-slate-50 rounded-xl">الكل</a>
            @endif
        </form>

        <!-- Result Strip -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-500">صافي المبيعات (Net Sales)</span>
                <strong class="block mt-1 text-lg font-mono font-bold text-slate-900" dir="ltr">${{ number_format($trading['net_sales_usd'], 2) }}</strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-500">تكلفة المبيعات (COGS)</span>
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

        <!-- Two-Sided GL Trading T-Account -->
        <div class="grid grid-cols-1 md:grid-cols-2 border border-slate-300 rounded-2xl overflow-hidden text-xs">
            <!-- Debit Side / منه -->
            <div class="border-b md:border-b-0 md:border-l border-slate-300 flex flex-col">
                <div class="px-4 py-3 bg-brand-950 text-white font-bold flex items-center justify-between">
                    <span>منه (الجانب المدين)</span>
                    <span class="font-mono text-[11px] text-brand-300">USD $</span>
                </div>
                <div class="divide-y divide-slate-100 flex-1">
                    @forelse($trading['debit_items'] as $item)
                        <div class="px-4 py-3 flex items-center justify-between gap-2 hover:bg-slate-50/70">
                            <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="text-slate-800 font-medium hover:text-brand-700 hover:underline">
                                <span class="font-mono text-slate-400 ml-1">{{ $item['code'] }}</span>
                                {{ $item['label'] }}
                            </a>
                            <span class="font-mono font-semibold text-slate-900 whitespace-nowrap" dir="ltr">${{ number_format($item['amount_usd'], 2) }}</span>
                        </div>
                    @empty
                        <div class="px-4 py-4 text-slate-400 text-center">لا توجد حركات مدينة في حسابات المتاجرة.</div>
                    @endforelse
                    @if($trading['gross_profit_usd'] > 0)
                        <div class="px-4 py-3 flex items-center justify-between gap-2 bg-brand-50/90 text-brand-900 font-bold">
                            <span>مجمل الربح (يُرحّل إلى حساب الأرباح والخسائر)</span>
                            <span class="font-mono font-extrabold whitespace-nowrap" dir="ltr">${{ number_format($trading['gross_profit_usd'], 2) }}</span>
                        </div>
                    @endif
                </div>
                <div class="px-4 py-3 bg-slate-100 border-t-2 border-slate-300 flex items-center justify-between font-extrabold text-slate-900">
                    <span>مجموع الجانب المدين</span>
                    <span class="font-mono text-sm" dir="ltr">${{ number_format($trading['debit_total_usd'], 2) }}</span>
                </div>
            </div>

            <!-- Credit Side / له -->
            <div class="flex flex-col">
                <div class="px-4 py-3 bg-brand-950 text-white font-bold flex items-center justify-between">
                    <span>له (الجانب الدائن)</span>
                    <span class="font-mono text-[11px] text-brand-300">USD $</span>
                </div>
                <div class="divide-y divide-slate-100 flex-1">
                    @forelse($trading['credit_items'] as $item)
                        <div class="px-4 py-3 flex items-center justify-between gap-2 hover:bg-slate-50/70">
                            <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="text-slate-800 font-medium hover:text-brand-700 hover:underline">
                                <span class="font-mono text-slate-400 ml-1">{{ $item['code'] }}</span>
                                {{ $item['label'] }}
                            </a>
                            <span class="font-mono font-semibold text-slate-900 whitespace-nowrap" dir="ltr">${{ number_format($item['amount_usd'], 2) }}</span>
                        </div>
                    @empty
                        <div class="px-4 py-4 text-slate-400 text-center">لا توجد حركات دائنة في حسابات المتاجرة.</div>
                    @endforelse
                    @if($trading['gross_loss_usd'] > 0)
                        <div class="px-4 py-3 flex items-center justify-between gap-2 bg-rose-50 text-rose-800 font-bold">
                            <span>مجمل الخسارة (يُرحّل إلى حساب الأرباح والخسائر)</span>
                            <span class="font-mono font-extrabold whitespace-nowrap" dir="ltr">${{ number_format($trading['gross_loss_usd'], 2) }}</span>
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
