@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
<div class="space-y-6">
    <!-- Top Workspace Header -->
    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/90 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        حساب متجر نشط
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 truncate">{{ $shop->name }}</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
                    ملخص فوري للصندوق، الأرباح التقديرية، وتقييم المخزون بمتوسط التكلفة المرجّح (WAC).
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 w-full sm:w-auto shrink-0">
                <a href="{{ route('transactions.create') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-semibold rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span>فاتورة جديدة</span>
                </a>
                <a href="{{ route('cashbox.index') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-semibold rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 transition-colors">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3"/></svg>
                    <span>سند صندوق</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Dual Cashbox Balances and Primary KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- USD Cashbox -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-slate-500">رصيد الصندوق (دولار USD)</span>
                <span class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 font-bold text-sm flex items-center justify-center shrink-0">$</span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-mono text-slate-900 text-right" dir="ltr">
                    ${{ number_format($cashbox['usd']['balance'], 2) }}
                </div>
                <div class="text-[11px] text-slate-400 mt-1.5 flex justify-between gap-2">
                    <span>الوارد: <span class="font-mono" dir="ltr">${{ number_format($cashbox['usd']['inflows']) }}</span></span>
                    <span>الصادر: <span class="font-mono" dir="ltr">${{ number_format($cashbox['usd']['outflows']) }}</span></span>
                </div>
            </div>
        </div>

        <!-- SYP Cashbox -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-slate-500">رصيد الصندوق (ليرة سورية SYP)</span>
                <span class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 font-bold text-xs flex items-center justify-center shrink-0">ل.س</span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-mono text-slate-900">
                    {{ number_format($cashbox['syp']['balance']) }} <span class="text-xs text-slate-400 font-sans font-normal">ل.س</span>
                </div>
                <div class="text-[11px] text-brand-600 font-semibold mt-1.5">
                    المعادل: <span class="font-mono" dir="ltr">${{ number_format(($shop->exchange_rate > 0 ? $cashbox['syp']['balance'] / $shop->exchange_rate : 0), 2) }} USD</span>
                </div>
            </div>
        </div>

        <!-- Estimated Net Profit -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-slate-500">صافي الربح التقديري</span>
                <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-mono text-right {{ $pnl['estimated_net_profit_usd'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}" dir="ltr">
                    ${{ number_format($pnl['estimated_net_profit_usd'], 2) }}
                </div>
                <div class="text-[11px] text-slate-400 mt-1.5">
                    المبيعات: <span class="font-mono" dir="ltr">${{ number_format($pnl['net_sales_usd']) }}</span> · التكلفة: <span class="font-mono" dir="ltr">${{ number_format($pnl['cogs_usd']) }}</span>
                </div>
            </div>
        </div>

        <!-- Inventory Asset Value -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-slate-500">قيمة بضاعة المخزن (WAC)</span>
                <span class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-mono text-slate-900 text-right" dir="ltr">
                    ${{ number_format(array_sum(array_column($stockReports, 'stock_value_usd')), 2) }}
                </div>
                <div class="text-[11px] text-slate-400 mt-1.5">
                    @if(count($lowStockItems) > 0)
                        <span class="text-amber-700 font-semibold">{{ count($lowStockItems) }} أصناف تحت حد الطلب</span>
                    @else
                        <span class="text-emerald-700 font-medium">المخزون بوضع ممتاز</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">آخر الفواتير المسجلة</h2>
            <a href="{{ route('transactions.index') }}" class="text-xs text-brand-600 hover:text-brand-700 hover:underline font-semibold">عرض كل الفواتير</a>
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
