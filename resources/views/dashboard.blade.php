@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
<div class="space-y-6">
    <!-- Top Welcome Banner -->
    <div class="bg-gradient-to-l from-brand-950 via-brand-900 to-brand-800 text-white rounded-3xl p-6 sm:p-8 border border-gold-500/30">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-gold-500/20 text-gold-300 border border-gold-400/40">
                        محل تجاري نشط · اشتراك ساري
                    </span>
                    <span class="text-xs text-slate-300 font-mono">
                        shop_id: <code class="text-gold-400 font-bold">{{ $shop->id }}</code>
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">{{ $shop->name }}</h1>
                <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl">
                    المعادلات المحاسبية المطبقة لحظياً بحسب وثيقة SRS: الصندوق، كشوفات الحساب، وتقييم المخزون بمتوسط التكلفة WAC.
                </p>
            </div>

            <div class="flex flex-col xs:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                <a href="{{ route('transactions.create') }}" class="px-4 py-2.5 text-xs font-bold rounded-xl bg-brand-800 hover:bg-brand-900 text-white text-center">
                    + فاتورة جديدة
                </a>
                <a href="{{ route('cashbox.index') }}" class="px-4 py-2.5 text-xs font-medium rounded-xl bg-brand-800 hover:bg-brand-900 text-white text-center">
                    سند صندوق
                </a>
            </div>
        </div>
    </div>

    <!-- Dual Cashbox Balances and Primary KPIs (SRS 4.ب & 4.د) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- USD Cashbox -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">رصيد الصندوق (دولار USD)</span>
                <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-800 font-bold text-sm flex items-center justify-center">$</span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-mono text-slate-900">
                    ${{ number_format($cashbox['usd']['balance'], 2) }}
                </div>
                <div class="text-[11px] text-slate-400 mt-1 flex justify-between">
                    <span>الوارد: ${{ number_format($cashbox['usd']['inflows']) }}</span>
                    <span>الصادر: ${{ number_format($cashbox['usd']['outflows']) }}</span>
                </div>
            </div>
        </div>

        <!-- SYP Cashbox -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">رصيد الصندوق (ليرة سورية SYP)</span>
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 font-bold text-xs flex items-center justify-center">ل.س</span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-mono text-slate-900">
                    {{ number_format($cashbox['syp']['balance']) }} <span class="text-xs text-slate-400 font-normal">ل.س</span>
                </div>
                <div class="text-[11px] text-blue-600 font-semibold mt-1">
                    المعادل: ${{ number_format(($shop->exchange_rate > 0 ? $cashbox['syp']['balance'] / $shop->exchange_rate : 0), 2) }} USD
                </div>
            </div>
        </div>

        <!-- Estimated Net Profit -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">صافي الربح التقديري (SRS 4.د)</span>
                <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-800 font-bold text-xs flex items-center justify-center">📈</span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-mono {{ $pnl['estimated_net_profit_usd'] >= 0 ? 'text-brand-800' : 'text-rose-600' }}">
                    ${{ number_format($pnl['estimated_net_profit_usd'], 2) }}
                </div>
                <div class="text-[11px] text-slate-400 mt-1">
                    المبيعات: ${{ number_format($pnl['net_sales_usd']) }} · COGS: ${{ number_format($pnl['cogs_usd']) }}
                </div>
            </div>
        </div>

        <!-- Inventory Asset Value -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">قيمة بضاعة المخزن (WAC)</span>
                <span class="w-8 h-8 rounded-lg bg-gold-50 text-gold-700 font-bold text-xs flex items-center justify-center">📦</span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-mono text-slate-900">
                    ${{ number_format(array_sum(array_column($stockReports, 'stock_value_usd')), 2) }}
                </div>
                <div class="text-[11px] text-slate-400 mt-1">
                    @if(count($lowStockItems) > 0)
                        <span class="text-gold-700 font-bold">⚠️ {{ count($lowStockItems) }} أصناف تحت حد الطلب</span>
                    @else
                        <span class="text-brand-800">المخزون بوضع ممتاز</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">آخر الفواتير المسجلة</h2>
            <a href="{{ route('transactions.index') }}" class="text-xs text-brand-800 hover:underline font-semibold">عرض كل الفواتير</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold">
                        <th class="py-3 px-4">رقم الفاتورة</th>
                        <th class="py-3 px-4">التاريخ</th>
                        <th class="py-3 px-4">النوع</th>
                        <th class="py-3 px-4">طريقة الدفع</th>
                        <th class="py-3 px-4">الطرف</th>
                        <th class="py-3 px-4">الصنف والكمية</th>
                        <th class="py-3 px-4 text-left">الإجمالي بالعملة</th>
                        <th class="py-3 px-4 text-left">المعادل بالدولار (USD)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentTransactions as $tx)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 px-4 font-mono font-bold">{{ $tx->invoice_number }}</td>
                            <td class="py-3 px-4 font-mono text-slate-500">{{ $tx->date->format('Y-m-d') }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $tx->type === 'sale' ? 'bg-brand-50 text-brand-800' : 'bg-blue-50 text-blue-700' }}">
                                    {{ $tx->type === 'sale' ? 'بيع' : ($tx->type === 'purchase' ? 'شراء' : 'مرتجع') }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                {{ $tx->payment_type === 'cash' ? 'نقدي (صندوق)' : 'آجل (ذمم)' }}
                            </td>
                            <td class="py-3 px-4 font-medium">{{ $tx->contact->name ?? '—' }}</td>
                            <td class="py-3 px-4">{{ $tx->item->item_name ?? '—' }} ({{ $tx->qty }})</td>
                            <td class="py-3 px-4 text-left font-mono font-bold">{{ number_format($tx->total) }} {{ $tx->currency }}</td>
                            <td class="py-3 px-4 text-left font-mono font-bold text-brand-800">${{ number_format($tx->total_usd, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">لا توجد فواتير مسجلة بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
