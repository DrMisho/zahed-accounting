@extends('layouts.app')

@section('title', 'كشف المبيعات')

@section('content')
@php
    $totals = $statement['totals'];
    $filters = $statement['filters'] ?? [];
@endphp
<div class="space-y-6">
    <!-- Header & Summary KPIs -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">كشف المبيعات التفصيلي</h1>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $shop->name }} · العملة الأساسية: الدولار الأمريكي (USD) · سعر الصرف المعتمد
                    <span class="font-mono font-semibold text-slate-700" dir="ltr">{{ number_format($statement['current_rate']) }}</span> ل.س
                </p>
            </div>
            <div class="no-print flex items-center gap-2">
                <a href="{{ route('transactions.create', ['type' => 'sale']) }}" class="px-4 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors">
                    + فاتورة بيع جديدة
                </a>
                <button type="button" onclick="window.print()" class="px-3.5 py-2 text-xs font-semibold border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-xl">
                    طباعة الكشف
                </button>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
            <div class="p-4 rounded-xl bg-brand-50 border border-brand-200/80">
                <span class="block text-[11px] font-semibold text-brand-800">صافي المبيعات $</span>
                <strong class="block mt-1 text-lg font-mono font-extrabold text-brand-950" dir="ltr">${{ number_format($totals['net_usd'], 2) }}</strong>
                <span class="block mt-0.5 text-[10px] text-brand-700">{{ $totals['invoice_count'] }} فاتورة · {{ $totals['return_count'] }} مرتجع</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-500">إجمالي قبل الحسم</span>
                <strong class="block mt-1 text-base font-mono font-bold text-slate-900" dir="ltr">${{ number_format($totals['gross_usd'], 2) }}</strong>
            </div>
            <div class="p-4 rounded-xl bg-amber-50/70 border border-amber-200/80">
                <span class="block text-[11px] text-amber-800">حسم ممنوح</span>
                <strong class="block mt-1 text-base font-mono font-bold text-amber-900" dir="ltr">${{ number_format($totals['discount_usd'], 2) }}</strong>
            </div>
            <div class="p-4 rounded-xl bg-rose-50/70 border border-rose-200/80">
                <span class="block text-[11px] text-rose-700">مرتجعات المبيعات</span>
                <strong class="block mt-1 text-base font-mono font-bold text-rose-800" dir="ltr">${{ number_format($totals['returns_usd'], 2) }}</strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-500">المقبوض / المتبقي</span>
                <strong class="block mt-1 text-sm font-mono font-bold text-brand-700" dir="ltr">${{ number_format($totals['paid_usd'], 2) }}</strong>
                <span class="block mt-0.5 text-[11px] font-mono text-rose-600" dir="ltr">متبقي ${{ number_format($totals['remaining_usd'], 2) }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-500">فرق صرف العملة</span>
                <strong class="block mt-1 text-base font-mono font-bold {{ $totals['fx_diff_usd'] >= 0 ? 'text-brand-700' : 'text-rose-600' }}" dir="ltr">
                    {{ $totals['fx_diff_usd'] >= 0 ? '+' : '-' }}${{ number_format(abs($totals['fx_diff_usd']), 2) }}
                </strong>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('reports.sales') }}" class="no-print grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-2.5 text-xs pt-1">
            <div>
                <label for="sales-from" class="block text-[11px] font-semibold text-slate-600 mb-1">من تاريخ</label>
                <input id="sales-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full p-2 rounded-xl border border-slate-300 font-mono">
            </div>
            <div>
                <label for="sales-to" class="block text-[11px] font-semibold text-slate-600 mb-1">إلى تاريخ</label>
                <input id="sales-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full p-2 rounded-xl border border-slate-300 font-mono">
            </div>
            <div>
                <label for="sales-customer" class="block text-[11px] font-semibold text-slate-600 mb-1">العميل</label>
                <select id="sales-customer" name="contact_id" class="w-full p-2 rounded-xl border border-slate-300 bg-white">
                    <option value="">كل العملاء</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ (string) ($filters['contact_id'] ?? '') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sales-pay" class="block text-[11px] font-semibold text-slate-600 mb-1">نوع الدفع</label>
                <select id="sales-pay" name="payment_type" class="w-full p-2 rounded-xl border border-slate-300 bg-white">
                    <option value="">الكل</option>
                    <option value="cash" {{ ($filters['payment_type'] ?? '') === 'cash' ? 'selected' : '' }}>نقدي</option>
                    <option value="credit" {{ ($filters['payment_type'] ?? '') === 'credit' ? 'selected' : '' }}>آجل</option>
                </select>
            </div>
            <div>
                <label for="sales-cur" class="block text-[11px] font-semibold text-slate-600 mb-1">العملة</label>
                <select id="sales-cur" name="currency" class="w-full p-2 rounded-xl border border-slate-300 bg-white">
                    <option value="">الكل</option>
                    <option value="USD" {{ ($filters['currency'] ?? '') === 'USD' ? 'selected' : '' }}>USD</option>
                    <option value="SYP" {{ ($filters['currency'] ?? '') === 'SYP' ? 'selected' : '' }}>SYP</option>
                </select>
            </div>
            <div>
                <label for="sales-search" class="block text-[11px] font-semibold text-slate-600 mb-1">بحث</label>
                <input id="sales-search" type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="رقم فاتورة أو عميل..." class="w-full p-2 rounded-xl border border-slate-300">
            </div>
            <div class="flex items-end gap-1.5">
                <button type="submit" class="flex-1 py-2 px-3 bg-brand-600 hover:bg-brand-700 text-white rounded-xl font-bold transition-colors">تصفية</button>
                <a href="{{ route('reports.sales') }}" class="py-2 px-3 border border-slate-300 text-slate-600 hover:bg-slate-50 rounded-xl">مسح</a>
            </div>
        </form>
    </div>

    <!-- Sales Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                        <th class="py-3 px-3">التاريخ</th>
                        <th class="py-3 px-3">رقم الفاتورة</th>
                        <th class="py-3 px-3">العميل</th>
                        <th class="py-3 px-3">الأصناف</th>
                        <th class="py-3 px-3">الدفع</th>
                        <th class="py-3 px-3 text-left">الصافي (عملة الإدخال)</th>
                        <th class="py-3 px-3 text-left">حسم $</th>
                        <th class="py-3 px-3 text-left">الإجمالي $</th>
                        <th class="py-3 px-3 text-left">المتبقي $</th>
                        <th class="py-3 px-3 text-left">فرق صرف $</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($statement['rows'] as $row)
                        @php $dec = $row['currency'] === 'SYP' ? 0 : 2; @endphp
                        <tr class="hover:bg-slate-50/80 {{ $row['is_return'] ? 'bg-rose-50/30' : '' }}">
                            <td class="py-3 px-3 font-mono text-slate-500 whitespace-nowrap">{{ $row['date'] }}</td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <a href="{{ route('transactions.show', $row['id']) }}" class="font-mono font-bold text-brand-700 hover:underline">{{ $row['invoice_number'] }}</a>
                                @if($row['is_return'])
                                    <span class="mr-1 px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 text-[10px] font-bold">مرتجع</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 font-semibold text-slate-800">
                                @if($row['contact_id'])
                                    <a href="{{ route('contacts.statement', $row['contact_id']) }}" class="hover:text-brand-700 hover:underline">{{ $row['contact_name'] }}</a>
                                @else
                                    {{ $row['contact_name'] }}
                                @endif
                            </td>
                            <td class="py-3 px-3 text-slate-600 max-w-xs">
                                @if(count($row['items']) > 0)
                                    <div class="space-y-0.5">
                                        @foreach($row['items'] as $it)
                                            <div class="text-[11px]">
                                                <span class="font-medium text-slate-800">{{ $it['item_name'] }}</span>
                                                <span class="text-slate-400 font-mono" dir="ltr">× {{ rtrim(rtrim(number_format($it['qty'], 2), '0'), '.') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $row['payment_type'] === 'cash' ? 'bg-brand-50 text-brand-800' : 'bg-amber-50 text-amber-800' }}">
                                    {{ $row['payment_type'] === 'cash' ? 'نقدي' : 'آجل' }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-left font-mono whitespace-nowrap" dir="ltr">
                                {{ number_format($row['total'], $dec) }} {{ $row['currency'] }}
                            </td>
                            <td class="py-3 px-3 text-left font-mono text-amber-700 whitespace-nowrap" dir="ltr">
                                {{ $row['discount_usd'] > 0 ? '$' . number_format($row['discount_usd'], 2) : '—' }}
                            </td>
                            <td class="py-3 px-3 text-left font-mono font-bold whitespace-nowrap {{ $row['is_return'] ? 'text-rose-700' : 'text-slate-900' }}" dir="ltr">
                                {{ $row['is_return'] ? '-' : '' }}${{ number_format($row['total_usd'], 2) }}
                            </td>
                            <td class="py-3 px-3 text-left font-mono whitespace-nowrap {{ $row['remaining_usd'] > 0 ? 'text-rose-600 font-semibold' : 'text-slate-400' }}" dir="ltr">
                                {{ $row['remaining_usd'] > 0 ? '$' . number_format($row['remaining_usd'], 2) : '—' }}
                            </td>
                            <td class="py-3 px-3 text-left font-mono whitespace-nowrap {{ $row['fx_diff_usd'] > 0 ? 'text-brand-700' : ($row['fx_diff_usd'] < 0 ? 'text-rose-600' : 'text-slate-400') }}" dir="ltr">
                                {{ abs($row['fx_diff_usd']) >= 0.01 ? (($row['fx_diff_usd'] > 0 ? '+' : '-') . '$' . number_format(abs($row['fx_diff_usd']), 2)) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-10 text-center text-slate-400">لا توجد فواتير مبيعات مطابقة للفلاتر المحددة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
