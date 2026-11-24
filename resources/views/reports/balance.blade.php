@extends('layouts.app')

@section('title', 'الميزانية العمومية')

@section('content')
<div class="max-w-5xl mx-auto space-y-5">
    @include('reports.partials.final-accounts-tabs')

    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">الميزانية العمومية (من دفتر الأستاذ العام)</h1>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $shop->name }} · مصدر الأرقام: <strong class="text-brand-800">شجرة الحسابات ودفتر الأستاذ</strong> ·
                    العملة الأساسية: <strong class="text-slate-800">الدولار الأمريكي (USD)</strong>
                    @if(!empty($bs['as_of']))
                        · حتى تاريخ <span class="font-mono font-semibold text-slate-700">{{ $bs['as_of'] }}</span>
                    @endif
                </p>
            </div>
            <div class="no-print flex flex-wrap items-center gap-2 text-xs">
                <span class="font-bold {{ ($bs['balanced'] ?? true) ? 'text-brand-800' : 'text-rose-700' }}">
                    {{ ($bs['balanced'] ?? true) ? 'الميزانية متوازنة 100%' : 'يوجد فرق توازن ($' . number_format(abs($bs['difference_usd'] ?? 0), 2) . ')' }}
                </span>
                <button type="button" onclick="window.print()" class="px-4 py-2 font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors whitespace-nowrap">
                    طباعة الميزانية
                </button>
            </div>
        </div>

        <!-- As-of Date Filter -->
        <form method="GET" action="{{ route('reports.balance') }}" class="no-print flex flex-wrap items-end gap-3 text-xs">
            <div>
                <label for="bs-asof" class="block text-[11px] font-semibold text-slate-600 mb-1">المركز المالي حتى تاريخ</label>
                <input id="bs-asof" type="date" name="as_of" value="{{ $bs['as_of'] ?? '' }}" class="p-2 rounded-xl border border-slate-300 font-mono">
            </div>
            <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl">عرض</button>
            @if(!empty($bs['as_of']))
                <a href="{{ route('reports.balance') }}" class="px-3 py-2 border border-slate-300 text-slate-600 hover:bg-slate-50 rounded-xl">حتى اليوم</a>
            @endif
        </form>

        <!-- Summary Strip -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div class="p-4 rounded-xl bg-brand-50 border border-brand-200/80">
                <span class="block text-[11px] font-semibold text-brand-800">إجمالي الموجودات (الأصول)</span>
                <strong class="block mt-1 text-xl font-mono font-extrabold text-brand-950" dir="ltr">
                    {{ $bs['total_assets_usd'] < 0 ? '-' : '' }}${{ number_format(abs($bs['total_assets_usd']), 2) }}
                </strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] font-semibold text-slate-600">إجمالي المطاليب (الالتزامات)</span>
                <strong class="block mt-1 text-xl font-mono font-bold text-rose-700" dir="ltr">
                    {{ $bs['total_liabilities_usd'] < 0 ? '-' : '' }}${{ number_format(abs($bs['total_liabilities_usd']), 2) }}
                </strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] font-semibold text-slate-600">صافي حقوق الملكية (شامل الربح/الخسارة)</span>
                <strong class="block mt-1 text-xl font-mono font-bold text-slate-900" dir="ltr">
                    {{ $bs['net_equity_usd'] < 0 ? '-' : '' }}${{ number_format(abs($bs['net_equity_usd']), 2) }}
                </strong>
            </div>
        </div>

        <!-- Two-Sided GL Balance Sheet -->
        <div class="grid grid-cols-1 md:grid-cols-2 border border-slate-300 rounded-2xl overflow-hidden text-xs">
            <!-- Assets / الموجودات -->
            <div class="border-b md:border-b-0 md:border-l border-slate-300 flex flex-col">
                <div class="px-4 py-3 bg-brand-950 text-white font-bold flex items-center justify-between">
                    <span>الموجودات (الأصول — 1)</span>
                    <span class="font-mono text-[11px] text-brand-300">USD $</span>
                </div>
                <div class="divide-y divide-slate-100 flex-1">
                    <div class="px-4 py-2 bg-slate-50 text-[11px] font-bold text-slate-600 flex justify-between">
                        <span>11 · الأصول المتداولة</span>
                        <span class="font-mono" dir="ltr">${{ number_format($bs['total_current_assets_usd'], 2) }}</span>
                    </div>
                    @forelse($bs['current_assets'] as $item)
                        <div class="px-4 py-2.5 flex items-center justify-between gap-2 hover:bg-slate-50/70">
                            <div>
                                <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="text-slate-800 font-medium hover:text-brand-700 hover:underline">
                                    <span class="font-mono text-slate-400 ml-1">{{ $item['code'] }}</span>
                                    {{ $item['label'] }}
                                </a>
                                @if($item['amount_syp'] !== null && abs($item['amount_syp']) >= 1)
                                    <span class="block text-[11px] font-mono text-slate-400" dir="ltr">{{ number_format($item['amount_syp']) }} SYP</span>
                                @endif
                            </div>
                            <span class="font-mono font-semibold text-slate-900 whitespace-nowrap" dir="ltr">
                                {{ $item['amount_usd'] < 0 ? '-' : '' }}${{ number_format(abs($item['amount_usd']), 2) }}
                            </span>
                        </div>
                    @empty
                        <div class="px-4 py-3 text-slate-400">لا توجد أرصدة أصول متداولة.</div>
                    @endforelse

                    @if(!empty($bs['fixed_assets']))
                        <div class="px-4 py-2 bg-slate-50 text-[11px] font-bold text-slate-600 flex justify-between">
                            <span>12 · الأصول الثابتة (بالصافي)</span>
                            <span class="font-mono" dir="ltr">${{ number_format($bs['total_fixed_assets_usd'], 2) }}</span>
                        </div>
                        @foreach($bs['fixed_assets'] as $item)
                            <div class="px-4 py-2.5 flex items-center justify-between gap-2 hover:bg-slate-50/70">
                                <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="text-slate-800 font-medium hover:text-brand-700 hover:underline">
                                    <span class="font-mono text-slate-400 ml-1">{{ $item['code'] }}</span>
                                    {{ $item['label'] }}
                                </a>
                                <span class="font-mono font-semibold whitespace-nowrap {{ $item['amount_usd'] < 0 ? 'text-rose-700' : 'text-slate-900' }}" dir="ltr">
                                    {{ $item['amount_usd'] < 0 ? '-' : '' }}${{ number_format(abs($item['amount_usd']), 2) }}
                                </span>
                            </div>
                        @endforeach
                    @endif
                </div>
                <div class="px-4 py-3.5 bg-slate-100 border-t-2 border-slate-300 flex items-center justify-between font-extrabold text-slate-900">
                    <span>مجموع الموجودات</span>
                    <span class="font-mono text-sm" dir="ltr">
                        {{ $bs['total_assets_usd'] < 0 ? '-' : '' }}${{ number_format(abs($bs['total_assets_usd']), 2) }}
                    </span>
                </div>
            </div>

            <!-- Liabilities & Equity / المطاليب وحقوق الملكية -->
            <div class="flex flex-col">
                <div class="px-4 py-3 bg-brand-950 text-white font-bold flex items-center justify-between">
                    <span>المطاليب وحقوق الملكية (2 + 3)</span>
                    <span class="font-mono text-[11px] text-brand-300">USD $</span>
                </div>
                <div class="divide-y divide-slate-100 flex-1">
                    <div class="px-4 py-2 bg-slate-50 text-[11px] font-bold text-slate-600 flex justify-between">
                        <span>21 · الالتزامات المتداولة</span>
                        <span class="font-mono" dir="ltr">${{ number_format($bs['total_current_liabilities_usd'], 2) }}</span>
                    </div>
                    @forelse($bs['current_liabilities'] as $item)
                        <div class="px-4 py-2.5 flex items-center justify-between gap-2 hover:bg-slate-50/70">
                            <div>
                                <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="text-slate-800 font-medium hover:text-brand-700 hover:underline">
                                    <span class="font-mono text-slate-400 ml-1">{{ $item['code'] }}</span>
                                    {{ $item['label'] }}
                                </a>
                                @if($item['amount_syp'] !== null && abs($item['amount_syp']) >= 1)
                                    <span class="block text-[11px] font-mono text-slate-400" dir="ltr">{{ number_format($item['amount_syp']) }} SYP</span>
                                @endif
                            </div>
                            <span class="font-mono font-semibold text-rose-700 whitespace-nowrap" dir="ltr">
                                {{ $item['amount_usd'] < 0 ? '-' : '' }}${{ number_format(abs($item['amount_usd']), 2) }}
                            </span>
                        </div>
                    @empty
                        <div class="px-4 py-3 text-slate-400">لا توجد التزامات متداولة.</div>
                    @endforelse

                    @if(!empty($bs['long_term_liabilities']))
                        <div class="px-4 py-2 bg-slate-50 text-[11px] font-bold text-slate-600 flex justify-between">
                            <span>22 · الالتزامات طويلة الأجل</span>
                            <span class="font-mono" dir="ltr">${{ number_format($bs['total_long_term_liabilities_usd'], 2) }}</span>
                        </div>
                        @foreach($bs['long_term_liabilities'] as $item)
                            <div class="px-4 py-2.5 flex items-center justify-between gap-2 hover:bg-slate-50/70">
                                <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="text-slate-800 font-medium hover:text-brand-700 hover:underline">
                                    <span class="font-mono text-slate-400 ml-1">{{ $item['code'] }}</span>
                                    {{ $item['label'] }}
                                </a>
                                <span class="font-mono font-semibold text-rose-700 whitespace-nowrap" dir="ltr">${{ number_format($item['amount_usd'], 2) }}</span>
                            </div>
                        @endforeach
                    @endif

                    <div class="px-4 py-2 bg-slate-50 text-[11px] font-bold text-slate-600 flex justify-between">
                        <span>3 · حقوق الملكية ونتيجة الفترة</span>
                        <span class="font-mono" dir="ltr">{{ $bs['net_equity_usd'] < 0 ? '-' : '' }}${{ number_format(abs($bs['net_equity_usd']), 2) }}</span>
                    </div>
                    @foreach($bs['equity_items'] as $item)
                        <div class="px-4 py-2.5 flex items-center justify-between gap-2 hover:bg-slate-50/70">
                            <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="text-slate-800 font-medium hover:text-brand-700 hover:underline">
                                <span class="font-mono text-slate-400 ml-1">{{ $item['code'] }}</span>
                                {{ $item['label'] }}
                            </a>
                            <span class="font-mono font-semibold whitespace-nowrap {{ $item['amount_usd'] < 0 ? 'text-amber-700' : 'text-slate-900' }}" dir="ltr">
                                {{ $item['amount_usd'] < 0 ? '-' : '' }}${{ number_format(abs($item['amount_usd']), 2) }}
                            </span>
                        </div>
                    @endforeach
                    <div class="px-4 py-2.5 flex items-center justify-between gap-2 bg-brand-50/60">
                        <div>
                            <span class="text-slate-800 font-bold block">صافي الربح / الخسارة المرحّل من الأرباح والخسائر</span>
                            @if(abs((float) ($bs['fx_diff_usd'] ?? 0)) >= 0.01)
                                <span class="text-[11px] text-slate-500">
                                    شامل فروق صرف مرحلة:
                                    <span class="font-mono inline-block {{ $bs['fx_diff_usd'] >= 0 ? 'text-brand-700' : 'text-rose-600' }}" dir="ltr">{{ $bs['fx_diff_usd'] >= 0 ? '+' : '-' }}${{ number_format(abs($bs['fx_diff_usd']), 2) }}</span>
                                </span>
                            @endif
                        </div>
                        <span class="font-mono font-extrabold whitespace-nowrap {{ $bs['profit_usd'] >= 0 ? 'text-brand-800' : 'text-rose-700' }}" dir="ltr">
                            {{ $bs['profit_usd'] < 0 ? '-' : '' }}${{ number_format(abs($bs['profit_usd']), 2) }}
                        </span>
                    </div>
                </div>
                <div class="px-4 py-3.5 bg-slate-100 border-t-2 border-slate-300 flex items-center justify-between font-extrabold text-slate-900">
                    <span>مجموع المطاليب وحقوق الملكية</span>
                    <span class="font-mono text-sm" dir="ltr">
                        {{ $bs['total_liabilities_and_equity_usd'] < 0 ? '-' : '' }}${{ number_format(abs($bs['total_liabilities_and_equity_usd']), 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
