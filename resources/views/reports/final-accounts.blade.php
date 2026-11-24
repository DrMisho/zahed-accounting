@extends('layouts.app')

@section('title', 'الحسابات الختامية والميزانية العمومية')

@section('content')
@php
    $fxDiff = (float) ($pnl['fx_diff_usd'] ?? 0);
    $grossProfit = (float) $pnl['gross_profit_usd'];
    $netProfit = (float) $pnl['estimated_net_profit_usd'];
    $pnlTotal = (float) ($pnl['pnl_debit_total_usd'] ?? 0);
@endphp
<div class="max-w-5xl mx-auto space-y-6">
    @include('reports.partials.final-accounts-tabs')

    <!-- Executive Header -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">الحسابات الختامية الشاملة (من دفتر الأستاذ العام) — {{ $shop->name }}</h1>
                <p class="text-xs text-slate-500 mt-1">
                    المتاجرة + الأرباح والخسائر + الميزانية العمومية · مصدر البيانات: <strong class="text-brand-800">شجرة الحسابات والقيود المرحلة (USD)</strong>
                </p>
            </div>
            <div class="no-print flex items-center gap-2 text-xs">
                <a href="{{ route('books.trial') }}" class="px-3.5 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold rounded-xl whitespace-nowrap">ميزان المراجعة</a>
                <button type="button" onclick="window.print()" class="px-4 py-2 font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors whitespace-nowrap">
                    طباعة الحسابات الختامية
                </button>
            </div>
        </div>

        <form method="GET" action="{{ route('reports.final-accounts') }}" class="no-print flex flex-wrap items-end gap-3 text-xs pt-1 border-t border-slate-100">
            <div>
                <label for="fa-from" class="block text-[11px] font-semibold text-slate-600 mb-1">من تاريخ</label>
                <input id="fa-from" type="date" name="from" value="{{ $from ?? '' }}" class="p-2 rounded-xl border border-slate-300 font-mono">
            </div>
            <div>
                <label for="fa-to" class="block text-[11px] font-semibold text-slate-600 mb-1">إلى تاريخ</label>
                <input id="fa-to" type="date" name="to" value="{{ $to ?? '' }}" class="p-2 rounded-xl border border-slate-300 font-mono">
            </div>
            <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl">تحديث</button>
            @if(!empty($from) || !empty($to))
                <a href="{{ route('reports.final-accounts') }}" class="px-3 py-2 border border-slate-300 text-slate-600 hover:bg-slate-50 rounded-xl">الكل</a>
            @endif
        </form>
    </div>

    <!-- Key Highlights -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
        <div class="p-4 rounded-xl bg-white border border-slate-200">
            <span class="block text-[11px] text-slate-500">مجمل الربح (المتاجرة)</span>
            <strong class="block mt-1 text-lg font-mono font-extrabold {{ $trading['trading_result_usd'] >= 0 ? 'text-brand-800' : 'text-rose-700' }}" dir="ltr">
                {{ $trading['trading_result_usd'] < 0 ? '-' : '' }}${{ number_format(abs($trading['trading_result_usd']), 2) }}
            </strong>
        </div>
        <div class="p-4 rounded-xl bg-white border border-slate-200">
            <span class="block text-[11px] text-slate-500">صافي فروق الصرف المرحلة</span>
            <strong class="block mt-1 text-lg font-mono font-extrabold {{ $fxDiff >= 0 ? 'text-brand-700' : 'text-rose-600' }}" dir="ltr">
                {{ $fxDiff >= 0 ? '+' : '-' }}${{ number_format(abs($fxDiff), 2) }}
            </strong>
        </div>
        <div class="p-4 rounded-xl bg-brand-50 border border-brand-200">
            <span class="block text-[11px] text-brand-800 font-semibold">صافي الربح (الأرباح والخسائر)</span>
            <strong class="block mt-1 text-lg font-mono font-extrabold {{ $netProfit >= 0 ? 'text-brand-950' : 'text-rose-700' }}" dir="ltr">
                {{ $netProfit < 0 ? '-' : '' }}${{ number_format(abs($netProfit), 2) }}
            </strong>
        </div>
        <div class="p-4 rounded-xl bg-white border border-slate-200">
            <span class="block text-[11px] text-slate-500">إجمالي الميزانية ({{ $bs['balanced'] ? 'متوازنة' : 'فرق' }})</span>
            <strong class="block mt-1 text-lg font-mono font-extrabold text-slate-900" dir="ltr">
                ${{ number_format($bs['total_assets_usd'], 2) }}
            </strong>
        </div>
    </div>

    <!-- 1. Trading Account -->
    <section class="bg-white rounded-2xl p-6 border border-slate-200 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">1) حساب المتاجرة</h2>
            <a href="{{ route('reports.trading') }}" class="no-print text-xs font-semibold text-brand-700 hover:underline">عرض مستقل</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 border border-slate-300 rounded-xl overflow-hidden text-xs">
            <div class="border-b md:border-b-0 md:border-l border-slate-300 flex flex-col">
                <div class="px-4 py-2.5 bg-brand-950 text-white font-bold">منه (مدين)</div>
                <div class="divide-y divide-slate-100 flex-1">
                    @foreach($trading['debit_items'] as $item)
                        <div class="px-4 py-2.5 flex justify-between gap-2">
                            <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="hover:text-brand-700 hover:underline">
                                <span class="font-mono text-slate-400 ml-1">{{ $item['code'] }}</span>{{ $item['label'] }}
                            </a>
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
                        <div class="px-4 py-2.5 flex justify-between gap-2">
                            <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="hover:text-brand-700 hover:underline">
                                <span class="font-mono text-slate-400 ml-1">{{ $item['code'] }}</span>{{ $item['label'] }}
                            </a>
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
    <section class="bg-white rounded-2xl p-6 border border-slate-200 space-y-4">
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
                            <span>مجمل الخسارة المرحّل</span>
                            <span class="font-mono" dir="ltr">${{ number_format(abs($grossProfit), 2) }}</span>
                        </div>
                    @endif
                    @foreach($pnl['debit_items'] ?? [] as $item)
                        <div class="px-4 py-2.5 flex justify-between gap-2">
                            <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="hover:text-brand-700 hover:underline">
                                <span class="font-mono text-slate-400 ml-1">{{ $item['code'] }}</span>{{ $item['label'] }}
                            </a>
                            <span class="font-mono font-semibold" dir="ltr">${{ number_format($item['amount_usd'], 2) }}</span>
                        </div>
                    @endforeach
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
                    @foreach($pnl['credit_items'] ?? [] as $item)
                        <div class="px-4 py-2.5 flex justify-between gap-2">
                            <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="hover:text-brand-700 hover:underline">
                                <span class="font-mono text-slate-400 ml-1">{{ $item['code'] }}</span>{{ $item['label'] }}
                            </a>
                            <span class="font-mono font-semibold text-brand-700" dir="ltr">${{ number_format($item['amount_usd'], 2) }}</span>
                        </div>
                    @endforeach
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
    <section class="bg-white rounded-2xl p-6 border border-slate-200 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">3) الميزانية العمومية (المركز المالي)</h2>
            <a href="{{ route('reports.balance') }}" class="no-print text-xs font-semibold text-brand-700 hover:underline">عرض مستقل</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 border border-slate-300 rounded-xl overflow-hidden text-xs">
            <div class="border-b md:border-b-0 md:border-l border-slate-300 flex flex-col">
                <div class="px-4 py-2.5 bg-brand-950 text-white font-bold">الموجودات (الأصول)</div>
                <div class="divide-y divide-slate-100 flex-1">
                    @foreach(array_merge($bs['current_assets'] ?? [], $bs['fixed_assets'] ?? []) as $item)
                        <div class="px-4 py-2.5 flex justify-between gap-2">
                            <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="hover:text-brand-700 hover:underline">
                                <span class="font-mono text-slate-400 ml-1">{{ $item['code'] }}</span>{{ $item['label'] }}
                            </a>
                            <span class="font-mono font-semibold" dir="ltr">{{ $item['amount_usd'] < 0 ? '-' : '' }}${{ number_format(abs($item['amount_usd']), 2) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="px-4 py-2.5 bg-slate-100 border-t border-slate-300 flex justify-between font-extrabold">
                    <span>مجموع الموجودات</span>
                    <span class="font-mono" dir="ltr">${{ number_format($bs['total_assets_usd'], 2) }}</span>
                </div>
            </div>
            <div class="flex flex-col">
                <div class="px-4 py-2.5 bg-brand-950 text-white font-bold">المطاليب وحقوق الملكية</div>
                <div class="divide-y divide-slate-100 flex-1">
                    @foreach(array_merge($bs['current_liabilities'] ?? [], $bs['long_term_liabilities'] ?? []) as $item)
                        <div class="px-4 py-2.5 flex justify-between gap-2">
                            <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="hover:text-brand-700 hover:underline">
                                <span class="font-mono text-slate-400 ml-1">{{ $item['code'] }}</span>{{ $item['label'] }}
                            </a>
                            <span class="font-mono font-semibold text-rose-700" dir="ltr">${{ number_format($item['amount_usd'], 2) }}</span>
                        </div>
                    @endforeach
                    @foreach($bs['equity_items'] ?? [] as $item)
                        <div class="px-4 py-2.5 flex justify-between gap-2">
                            <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="hover:text-brand-700 hover:underline">
                                <span class="font-mono text-slate-400 ml-1">{{ $item['code'] }}</span>{{ $item['label'] }}
                            </a>
                            <span class="font-mono font-semibold {{ $item['amount_usd'] < 0 ? 'text-amber-700' : 'text-slate-900' }}" dir="ltr">{{ $item['amount_usd'] < 0 ? '-' : '' }}${{ number_format(abs($item['amount_usd']), 2) }}</span>
                        </div>
                    @endforeach
                    <div class="px-4 py-2.5 flex justify-between bg-brand-50/60">
                        <span class="font-bold">صافي الربح / الخسارة</span>
                        <span class="font-mono font-bold {{ $bs['profit_usd'] >= 0 ? 'text-brand-700' : 'text-rose-700' }}" dir="ltr">{{ $bs['profit_usd'] < 0 ? '-' : '' }}${{ number_format(abs($bs['profit_usd']), 2) }}</span>
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
