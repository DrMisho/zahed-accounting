@extends('layouts.app')

@section('title', 'الميزانية العمومية')

@section('content')
<div class="max-w-5xl mx-auto space-y-5">
    @include('reports.partials.final-accounts-tabs')

    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">الميزانية العمومية (المركز المالي - Balance Sheet)</h1>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $shop->name }} · العملة الأساسية: <strong class="text-brand-800">الدولار الأمريكي (USD)</strong> ·
                    تقييم أرصدة الليرة السورية على سعر <span class="font-mono font-semibold text-slate-700" dir="ltr">{{ number_format($bs['current_rate'] ?? ($shop->exchange_rate ?? 14500)) }}</span> ل.س
                </p>
            </div>
            <div class="no-print flex items-center gap-2">
                <span class="px-3 py-1.5 rounded-xl text-xs font-bold {{ ($bs['balanced'] ?? true) ? 'bg-brand-50 text-brand-800 border border-brand-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                    {{ ($bs['balanced'] ?? true) ? 'الميزانية متوازنة' : 'يوجد فرق توازن' }}
                </span>
                <button type="button" onclick="window.print()" class="px-4 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors">
                    طباعة الميزانية
                </button>
            </div>
        </div>

        <!-- Summary Strip -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div class="p-4 rounded-xl bg-brand-50 border border-brand-200/80">
                <span class="block text-[11px] font-semibold text-brand-800">إجمالي الموجودات (الأصول)</span>
                <strong class="block mt-1 text-xl font-mono font-extrabold text-brand-950" dir="ltr">${{ number_format($bs['total_assets_usd'], 2) }}</strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] font-semibold text-slate-600">إجمالي المطاليب (الالتزامات)</span>
                <strong class="block mt-1 text-xl font-mono font-bold text-rose-700" dir="ltr">${{ number_format($bs['total_liabilities_usd'], 2) }}</strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] font-semibold text-slate-600">صافي حقوق الملكية</span>
                <strong class="block mt-1 text-xl font-mono font-bold text-slate-900" dir="ltr">${{ number_format($bs['net_equity_usd'], 2) }}</strong>
            </div>
        </div>

        <!-- Two-Sided Balance Sheet -->
        <div class="grid grid-cols-1 md:grid-cols-2 border border-slate-300 rounded-2xl overflow-hidden text-xs">
            <!-- Assets / الموجودات -->
            <div class="border-b md:border-b-0 md:border-l border-slate-300 flex flex-col">
                <div class="px-4 py-3 bg-brand-950 text-white font-bold flex items-center justify-between">
                    <span>الموجودات (الأصول - Assets)</span>
                    <span class="font-mono text-[11px] text-brand-300">USD $</span>
                </div>
                <div class="divide-y divide-slate-100 flex-1">
                    <div class="px-4 py-2 bg-slate-50 text-[11px] font-bold text-slate-600">الموجودات المتداولة</div>
                    <div class="px-4 py-3 flex items-center justify-between gap-2">
                        <span class="text-slate-700 font-medium">صندوق النقدية — دولار (USD)</span>
                        <span class="font-mono font-semibold text-slate-900" dir="ltr">${{ number_format($bs['cash_usd'], 2) }}</span>
                    </div>
                    <div class="px-4 py-3 flex items-center justify-between gap-2">
                        <div>
                            <span class="text-slate-700 font-medium block">صندوق النقدية — ليرة سورية (معادل $)</span>
                            <span class="text-[11px] font-mono text-slate-400" dir="ltr">{{ number_format($bs['cash_syp']) }} SYP</span>
                        </div>
                        <span class="font-mono font-semibold text-slate-900" dir="ltr">${{ number_format($bs['cash_syp_usd'], 2) }}</span>
                    </div>
                    <div class="px-4 py-3 flex items-center justify-between gap-2">
                        <span class="text-slate-700 font-medium">ذمم العملاء (مدينون) — دولار</span>
                        <span class="font-mono font-semibold text-slate-900" dir="ltr">${{ number_format($bs['ar_usd'], 2) }}</span>
                    </div>
                    <div class="px-4 py-3 flex items-center justify-between gap-2">
                        <div>
                            <span class="text-slate-700 font-medium block">ذمم العملاء (مدينون) — ليرة سورية (معادل $)</span>
                            <span class="text-[11px] font-mono text-slate-400" dir="ltr">{{ number_format($bs['ar_syp']) }} SYP</span>
                        </div>
                        <span class="font-mono font-semibold text-slate-900" dir="ltr">${{ number_format($bs['ar_syp_usd'], 2) }}</span>
                    </div>
                    <div class="px-4 py-3 flex items-center justify-between gap-2">
                        <span class="text-slate-700 font-medium">بضاعة آخر المدة (تقييم المخزون WAC)</span>
                        <span class="font-mono font-semibold text-slate-900" dir="ltr">${{ number_format($bs['inventory_usd'], 2) }}</span>
                    </div>
                </div>
                <div class="px-4 py-3.5 bg-slate-100 border-t-2 border-slate-300 flex items-center justify-between font-extrabold text-slate-900">
                    <span>مجموع الموجودات</span>
                    <span class="font-mono text-sm" dir="ltr">${{ number_format($bs['total_assets_usd'], 2) }}</span>
                </div>
            </div>

            <!-- Liabilities & Equity / المطاليب وحقوق الملكية -->
            <div class="flex flex-col">
                <div class="px-4 py-3 bg-brand-950 text-white font-bold flex items-center justify-between">
                    <span>المطاليب وحقوق الملكية (Liabilities &amp; Equity)</span>
                    <span class="font-mono text-[11px] text-brand-300">USD $</span>
                </div>
                <div class="divide-y divide-slate-100 flex-1">
                    <div class="px-4 py-2 bg-slate-50 text-[11px] font-bold text-slate-600">المطاليب المتداولة (الالتزامات)</div>
                    <div class="px-4 py-3 flex items-center justify-between gap-2">
                        <span class="text-slate-700 font-medium">ذمم الموردين (دائنون) — دولار</span>
                        <span class="font-mono font-semibold text-rose-700" dir="ltr">${{ number_format($bs['ap_usd'], 2) }}</span>
                    </div>
                    <div class="px-4 py-3 flex items-center justify-between gap-2">
                        <div>
                            <span class="text-slate-700 font-medium block">ذمم الموردين (دائنون) — ليرة سورية (معادل $)</span>
                            <span class="text-[11px] font-mono text-slate-400" dir="ltr">{{ number_format($bs['ap_syp']) }} SYP</span>
                        </div>
                        <span class="font-mono font-semibold text-rose-700" dir="ltr">${{ number_format($bs['ap_syp_usd'], 2) }}</span>
                    </div>

                    <div class="px-4 py-2 bg-slate-50 text-[11px] font-bold text-slate-600">حقوق الملكية ورأس المال</div>
                    <div class="px-4 py-3 flex items-center justify-between gap-2">
                        <span class="text-slate-700 font-medium">رأس المال والأرصدة الافتتاحية المدوّرة</span>
                        <span class="font-mono font-semibold text-slate-900" dir="ltr">${{ number_format($bs['capital_usd'], 2) }}</span>
                    </div>
                    <div class="px-4 py-3 flex items-center justify-between gap-2">
                        <div>
                            <span class="text-slate-700 font-medium block">صافي الربح / الخسارة (شامل فروق صرف العملة)</span>
                            @if(abs((float) ($bs['fx_diff_usd'] ?? 0)) >= 0.01)
                                <span class="text-[11px] text-slate-500">
                                    منها فروق صرف:
                                    <span class="font-mono inline-block {{ $bs['fx_diff_usd'] >= 0 ? 'text-brand-700' : 'text-rose-600' }}" dir="ltr">{{ $bs['fx_diff_usd'] >= 0 ? '+' : '-' }}${{ number_format(abs($bs['fx_diff_usd']), 2) }}</span>
                                </span>
                            @endif
                        </div>
                        <span class="font-mono font-bold {{ $bs['profit_usd'] >= 0 ? 'text-brand-700' : 'text-rose-700' }}" dir="ltr">${{ number_format($bs['profit_usd'], 2) }}</span>
                    </div>
                    <div class="px-4 py-3 flex items-center justify-between gap-2">
                        <span class="text-slate-700 font-medium">(-) مسحوبات الشركاء الشخصية</span>
                        <span class="font-mono font-semibold text-amber-700" dir="ltr">-${{ number_format($bs['withdrawals_usd'], 2) }}</span>
                    </div>
                </div>
                <div class="px-4 py-3.5 bg-slate-100 border-t-2 border-slate-300 flex items-center justify-between font-extrabold text-slate-900">
                    <span>مجموع المطاليب وحقوق الملكية</span>
                    <span class="font-mono text-sm" dir="ltr">${{ number_format($bs['total_liabilities_and_equity_usd'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
