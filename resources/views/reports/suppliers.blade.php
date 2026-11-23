@extends('layouts.app')

@section('title', 'كشف الموردين')

@section('content')
@php
    $totals = $statement['totals'];
    $filters = $statement['filters'] ?? [];
@endphp
<div class="space-y-6">
    <!-- Header & KPI Summary -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">كشف أرصدة وحركة الموردين</h1>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $shop->name }} · العملة الأساسية: الدولار الأمريكي (USD) · سعر الصرف المعتمد
                    <span class="font-mono font-semibold text-slate-700" dir="ltr">{{ number_format($statement['current_rate']) }}</span> ل.س
                </p>
            </div>
            <div class="no-print flex items-center gap-2">
                <a href="{{ route('contacts.index', ['type' => 'supplier']) }}" class="px-3.5 py-2 text-xs font-semibold border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-xl">
                    إدارة الموردين
                </a>
                <button type="button" onclick="window.print()" class="px-4 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors">
                    طباعة الكشف
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
            <div class="p-4 rounded-xl bg-brand-50 border border-brand-200/80">
                <span class="block text-[11px] font-semibold text-brand-800">إجمالي ذمم الموردين (USD)</span>
                <strong class="block mt-1 text-lg font-mono font-extrabold text-brand-950" dir="ltr">${{ number_format($totals['balance_total_usd'], 2) }}</strong>
                <span class="block mt-0.5 text-[10px] text-brand-700">{{ $totals['count'] }} مورد ({{ $totals['active_debtors_count'] }} مستحق)</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-500">أرصدة الدولار ($)</span>
                <strong class="block mt-1 text-base font-mono font-bold text-slate-900" dir="ltr">${{ number_format($totals['balance_usd'], 2) }}</strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-500">أرصدة الليرة (ل.س)</span>
                <strong class="block mt-1 text-base font-mono font-bold text-slate-900" dir="ltr">{{ number_format($totals['balance_syp']) }}</strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-500">إجمالي المشتريات $</span>
                <strong class="block mt-1 text-base font-mono font-bold text-slate-900" dir="ltr">${{ number_format($totals['total_invoices_usd'], 2) }}</strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-500">إجمالي المدفوعات $</span>
                <strong class="block mt-1 text-base font-mono font-bold text-brand-700" dir="ltr">${{ number_format($totals['total_paid_usd'], 2) }}</strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-500">فرق صرف الذمم $</span>
                <strong class="block mt-1 text-base font-mono font-bold {{ $totals['syp_fx_diff_usd'] >= 0 ? 'text-brand-700' : 'text-rose-600' }}" dir="ltr">
                    {{ $totals['syp_fx_diff_usd'] >= 0 ? '+' : '-' }}${{ number_format(abs($totals['syp_fx_diff_usd']), 2) }}
                </strong>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('reports.suppliers') }}" class="no-print grid grid-cols-2 sm:grid-cols-5 gap-2.5 text-xs pt-1">
            <div>
                <label for="sup-search" class="block text-[11px] font-semibold text-slate-600 mb-1">بحث بالاسم أو الهاتف</label>
                <input id="sup-search" type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="اسم المورد..." class="w-full p-2 rounded-xl border border-slate-300">
            </div>
            <div>
                <label for="sup-status" class="block text-[11px] font-semibold text-slate-600 mb-1">حالة الرصيد</label>
                <select id="sup-status" name="balance_status" class="w-full p-2 rounded-xl border border-slate-300 bg-white">
                    <option value="all" {{ ($filters['balance_status'] ?? 'all') === 'all' ? 'selected' : '' }}>جميع الموردين</option>
                    <option value="nonzero" {{ ($filters['balance_status'] ?? '') === 'nonzero' ? 'selected' : '' }}>ذوي الأرصدة المستحقة</option>
                    <option value="zero" {{ ($filters['balance_status'] ?? '') === 'zero' ? 'selected' : '' }}>الأرصدة الصفرية</option>
                </select>
            </div>
            <div>
                <label for="sup-from" class="block text-[11px] font-semibold text-slate-600 mb-1">من تاريخ</label>
                <input id="sup-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full p-2 rounded-xl border border-slate-300 font-mono">
            </div>
            <div>
                <label for="sup-to" class="block text-[11px] font-semibold text-slate-600 mb-1">إلى تاريخ</label>
                <input id="sup-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full p-2 rounded-xl border border-slate-300 font-mono">
            </div>
            <div class="flex items-end gap-1.5">
                <button type="submit" class="flex-1 py-2 px-3 bg-brand-600 hover:bg-brand-700 text-white rounded-xl font-bold transition-colors">عرض</button>
                <a href="{{ route('reports.suppliers') }}" class="py-2 px-3 border border-slate-300 text-slate-600 hover:bg-slate-50 rounded-xl">مسح</a>
            </div>
        </form>
    </div>

    <!-- Suppliers Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                        <th class="py-3 px-4">المورد</th>
                        <th class="py-3 px-3">الهاتف</th>
                        <th class="py-3 px-3 text-center">الفواتير</th>
                        <th class="py-3 px-3 text-left">إجمالي المشتريات $</th>
                        <th class="py-3 px-3 text-left">المدفوعات $</th>
                        <th class="py-3 px-3 text-left">الرصيد $</th>
                        <th class="py-3 px-3 text-left">الرصيد ل.س</th>
                        <th class="py-3 px-3 text-left">الإجمالي الموحّد $</th>
                        <th class="py-3 px-3 text-left">فرق صرف $</th>
                        <th class="py-3 px-3 text-center no-print">كشف الحساب</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($statement['rows'] as $row)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3 px-4 font-bold text-slate-900">{{ $row['contact']->name }}</td>
                            <td class="py-3 px-3 font-mono text-slate-500" dir="ltr">{{ $row['contact']->phone ?: '—' }}</td>
                            <td class="py-3 px-3 text-center font-mono text-slate-600">{{ $row['invoice_count'] }}</td>
                            <td class="py-3 px-3 text-left font-mono text-slate-700" dir="ltr">${{ number_format($row['total_invoices_usd'], 2) }}</td>
                            <td class="py-3 px-3 text-left font-mono text-brand-700" dir="ltr">${{ number_format($row['total_paid_usd'], 2) }}</td>
                            <td class="py-3 px-3 text-left font-mono font-semibold text-slate-800" dir="ltr">${{ number_format($row['balance_usd'], 2) }}</td>
                            <td class="py-3 px-3 text-left font-mono text-slate-700" dir="ltr">{{ number_format($row['balance_syp']) }}</td>
                            <td class="py-3 px-3 text-left font-mono font-extrabold {{ $row['balance_total_usd'] > 0 ? 'text-rose-700' : 'text-brand-800' }}" dir="ltr">
                                ${{ number_format($row['balance_total_usd'], 2) }}
                            </td>
                            <td class="py-3 px-3 text-left font-mono {{ $row['syp_fx_diff_usd'] > 0 ? 'text-brand-700' : ($row['syp_fx_diff_usd'] < 0 ? 'text-rose-600' : 'text-slate-400') }}" dir="ltr">
                                {{ abs($row['syp_fx_diff_usd']) >= 0.01 ? (($row['syp_fx_diff_usd'] > 0 ? '+' : '-') . '$' . number_format(abs($row['syp_fx_diff_usd']), 2)) : '—' }}
                            </td>
                            <td class="py-3 px-3 text-center no-print">
                                <a href="{{ route('contacts.statement', $row['contact']) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-brand-50 text-brand-800 hover:bg-brand-100 font-semibold transition-colors">
                                    كشف تفصيلي
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-10 text-center text-slate-400">لا يوجد موردون مطابقون لمعايير البحث.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($statement['rows']) > 0)
                    <tfoot>
                        <tr class="bg-slate-50 border-t-2 border-slate-200 font-bold text-slate-900">
                            <td colspan="3" class="py-3 px-4">الإجمالي ({{ $totals['count'] }} مورد)</td>
                            <td class="py-3 px-3 text-left font-mono" dir="ltr">${{ number_format($totals['total_invoices_usd'], 2) }}</td>
                            <td class="py-3 px-3 text-left font-mono text-brand-700" dir="ltr">${{ number_format($totals['total_paid_usd'], 2) }}</td>
                            <td class="py-3 px-3 text-left font-mono" dir="ltr">${{ number_format($totals['balance_usd'], 2) }}</td>
                            <td class="py-3 px-3 text-left font-mono" dir="ltr">{{ number_format($totals['balance_syp']) }}</td>
                            <td class="py-3 px-3 text-left font-mono text-brand-950" dir="ltr">${{ number_format($totals['balance_total_usd'], 2) }}</td>
                            <td class="py-3 px-3 text-left font-mono {{ $totals['syp_fx_diff_usd'] >= 0 ? 'text-brand-700' : 'text-rose-600' }}" dir="ltr">
                                {{ $totals['syp_fx_diff_usd'] >= 0 ? '+' : '-' }}${{ number_format(abs($totals['syp_fx_diff_usd']), 2) }}
                            </td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
