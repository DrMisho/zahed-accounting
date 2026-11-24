@extends('layouts.app')

@section('title', 'فروق صرف العملة')

@section('content')
<div class="space-y-6">
    <!-- Top Header & Navigation Tabs -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">تقرير فروق صرف العملة (مربوط بجميع العمليات)</h1>
                <p class="text-xs text-slate-500 mt-1">
                    العملة الأساسية للنظام: <strong class="text-brand-800">الدولار الأمريكي (USD)</strong> ·
                    سعر الصرف المرجعي الحالي: <span class="font-mono font-bold text-slate-800" dir="ltr">{{ number_format($fx['current_rate']) }}</span> ل.س لكل 1$
                </p>
            </div>
            <div class="no-print flex flex-wrap items-center gap-2 text-xs">
                <a href="{{ route('reports.final-accounts') }}" class="px-3.5 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold">الحسابات الختامية</a>
                <a href="{{ route('currency-exchanges.index') }}" class="px-3.5 py-2 rounded-xl border border-brand-300 bg-brand-50 text-brand-800 hover:bg-brand-100 font-semibold">تحويل العملة</a>
                <button type="button" onclick="window.print()" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold">طباعة</button>
            </div>
        </div>

        <!-- Net FX Impact Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
            <div class="p-4 rounded-xl border {{ $fx['net_fx_diff_usd'] >= 0 ? 'bg-brand-50 border-brand-200' : 'bg-rose-50 border-rose-200' }}">
                <span class="block text-[11px] font-semibold {{ $fx['net_fx_diff_usd'] >= 0 ? 'text-brand-800' : 'text-rose-800' }}">
                    صافي فروق صرف العملة (يدخل في الأرباح والخسائر)
                </span>
                <strong class="block mt-1 text-xl font-mono font-extrabold {{ $fx['net_fx_diff_usd'] >= 0 ? 'text-brand-950' : 'text-rose-700' }}" dir="ltr">
                    {{ $fx['net_fx_diff_usd'] >= 0 ? '+' : '-' }}${{ number_format(abs($fx['net_fx_diff_usd']), 2) }}
                </strong>
                <span class="block mt-0.5 text-[11px] {{ $fx['net_fx_diff_usd'] >= 0 ? 'text-brand-700' : 'text-rose-600' }}">
                    {{ $fx['net_fx_diff_usd'] >= 0 ? 'صافي أرباح فروق صرف' : 'صافي خسائر فروق صرف' }}
                </span>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-600 font-semibold">فروق تحويل العملة (محقق)</span>
                <strong class="block mt-1 text-lg font-mono font-bold {{ $fx['realized_exchange_fx_usd'] >= 0 ? 'text-brand-700' : 'text-rose-600' }}" dir="ltr">
                    {{ $fx['realized_exchange_fx_usd'] >= 0 ? '+' : '-' }}${{ number_format(abs($fx['realized_exchange_fx_usd']), 2) }}
                </strong>
                <span class="block mt-0.5 text-[10px] text-slate-400">من عمليات تصريف SYP ⇄ USD</span>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-600 font-semibold">إعادة تقييم صندوق الليرة</span>
                <strong class="block mt-1 text-lg font-mono font-bold {{ $fx['cashbox_unrealized_fx_usd'] >= 0 ? 'text-brand-700' : 'text-rose-600' }}" dir="ltr">
                    {{ $fx['cashbox_unrealized_fx_usd'] >= 0 ? '+' : '-' }}${{ number_format(abs($fx['cashbox_unrealized_fx_usd']), 2) }}
                </strong>
                <span class="block mt-0.5 text-[10px] font-mono text-slate-500">رصيد: {{ number_format($fx['syp_cash_balance']) }} ل.س (${{ number_format($fx['syp_cash_current_usd'], 2) }})</span>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-600 font-semibold">فروق ذمم العملاء (ل.س)</span>
                <strong class="block mt-1 text-lg font-mono font-bold {{ $fx['receivables_fx_usd'] >= 0 ? 'text-brand-700' : 'text-rose-600' }}" dir="ltr">
                    {{ $fx['receivables_fx_usd'] >= 0 ? '+' : '-' }}${{ number_format(abs($fx['receivables_fx_usd']), 2) }}
                </strong>
                <span class="block mt-0.5 text-[10px] font-mono text-slate-500">ذمم: {{ number_format($fx['receivables_syp']) }} ل.س</span>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-600 font-semibold">فروق ذمم الموردين (ل.س)</span>
                <strong class="block mt-1 text-lg font-mono font-bold {{ $fx['payables_fx_usd'] >= 0 ? 'text-brand-700' : 'text-rose-600' }}" dir="ltr">
                    {{ $fx['payables_fx_usd'] >= 0 ? '+' : '-' }}${{ number_format(abs($fx['payables_fx_usd']), 2) }}
                </strong>
                <span class="block mt-0.5 text-[10px] font-mono text-slate-500">ذمم: {{ number_format($fx['payables_syp']) }} ل.س</span>
            </div>
        </div>

        <!-- Date Filter for Operation Audit Table -->
        <form method="GET" action="{{ route('reports.fx') }}" class="no-print flex flex-wrap items-end gap-3 text-xs pt-1">
            <div>
                <label for="fx-from" class="block text-[11px] font-semibold text-slate-600 mb-1">من تاريخ</label>
                <input id="fx-from" type="date" name="from" value="{{ $fx['from'] ?? '' }}" class="p-2 rounded-xl border border-slate-300 font-mono">
            </div>
            <div>
                <label for="fx-to" class="block text-[11px] font-semibold text-slate-600 mb-1">إلى تاريخ</label>
                <input id="fx-to" type="date" name="to" value="{{ $fx['to'] ?? '' }}" class="p-2 rounded-xl border border-slate-300 font-mono">
            </div>
            <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl font-bold">تصفية الحركات</button>
            @if(!empty($fx['from']) || !empty($fx['to']))
                <a href="{{ route('reports.fx') }}" class="px-3.5 py-2 border border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50">إعادة ضبط</a>
            @endif
        </form>
    </div>

    <!-- GL IAS 21 FX Revaluation Card -->
    @if(isset($glFxPreview))
        @php
            $netAdj = (float) ($glFxPreview['net_adjustment_usd'] ?? 0);
            $needsReval = (bool) ($glFxPreview['needs_revaluation'] ?? false);
            $revalItems = $glFxPreview['items'] ?? [];
        @endphp
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">إعادة تقييم الحسابات النقدية بالليرة السورية في الأستاذ العام (IAS 21)</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        مقارنة القيمة الدفترية بالدولار في دفتر الأستاذ مع القيمة الفعلية بسعر الصرف الحالي
                        (<span class="font-mono font-semibold text-slate-700 inline-block" dir="ltr">{{ number_format($glFxPreview['current_rate'] ?? $shop->exchange_rate) }}</span> ل.س / $)
                        وترحيل فرق التقييم إلى حساب أرباح/خسائر فروق الصرف.
                    </p>
                </div>
                <div class="no-print shrink-0 flex items-center gap-2">
                    @if($needsReval)
                        <form method="POST" action="{{ route('books.revalue-fx') }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-xl bg-brand-700 hover:bg-brand-800 text-white text-xs font-bold shadow-sm">
                                ترحيل قيد إعادة التقييم الآن
                            </button>
                        </form>
                    @else
                        <span class="px-3 py-1.5 rounded-lg bg-brand-50 text-brand-800 border border-brand-200 text-xs font-semibold">
                            الأرصدة الدفترية مطابقة لسعر الصرف الحالي
                        </span>
                    @endif
                </div>
            </div>

            @if(!empty($revalItems))
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                                <th class="py-2.5 px-3">رقم الحساب</th>
                                <th class="py-2.5 px-3">اسم الحساب في الشجرة</th>
                                <th class="py-2.5 px-3 text-left">الرصيد الثانوي (ل.س)</th>
                                <th class="py-2.5 px-3 text-left">القيمة الدفترية ($)</th>
                                <th class="py-2.5 px-3 text-left">القيمة بسعر اليوم ($)</th>
                                <th class="py-2.5 px-3 text-left">فرق التقييم ($)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($revalItems as $item)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="py-2.5 px-3 font-mono font-semibold text-brand-700 whitespace-nowrap">
                                        <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="hover:underline">{{ $item['code'] }}</a>
                                    </td>
                                    <td class="py-2.5 px-3 text-slate-800 font-medium">
                                        <a href="{{ route('books.ledger', ['account_id' => $item['account_id']]) }}" class="hover:text-brand-700">{{ $item['name'] }}</a>
                                    </td>
                                    <td class="py-2.5 px-3 text-left font-mono text-slate-700 whitespace-nowrap" dir="ltr">
                                        {{ $item['balance_syp'] < 0 ? '-' : '' }}{{ number_format(abs($item['balance_syp'])) }}
                                    </td>
                                    <td class="py-2.5 px-3 text-left font-mono text-slate-700 whitespace-nowrap" dir="ltr">
                                        {{ $item['book_usd'] < 0 ? '-' : '' }}${{ number_format(abs($item['book_usd']), 2) }}
                                    </td>
                                    <td class="py-2.5 px-3 text-left font-mono text-slate-900 font-semibold whitespace-nowrap" dir="ltr">
                                        {{ $item['current_usd'] < 0 ? '-' : '' }}${{ number_format(abs($item['current_usd']), 2) }}
                                    </td>
                                    <td class="py-2.5 px-3 text-left font-mono font-bold whitespace-nowrap {{ $item['fx_diff_usd'] > 0 ? 'text-brand-700' : ($item['fx_diff_usd'] < 0 ? 'text-rose-600' : 'text-slate-400') }}" dir="ltr">
                                        {{ abs($item['fx_diff_usd']) >= 0.01 ? (($item['fx_diff_usd'] > 0 ? '+' : '-') . '$' . number_format(abs($item['fx_diff_usd']), 2)) : '$0.00' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 border-t border-slate-200 font-bold">
                                <td colspan="5" class="py-2.5 px-3 text-slate-800">صافي أثر قيد إعادة التقييم على الأرباح والخسائر (4212 / 5311)</td>
                                <td class="py-2.5 px-3 text-left font-mono whitespace-nowrap {{ $netAdj > 0 ? 'text-brand-700' : ($netAdj < 0 ? 'text-rose-600' : 'text-slate-500') }}" dir="ltr">
                                    {{ abs($netAdj) >= 0.01 ? (($netAdj > 0 ? '+' : '-') . '$' . number_format(abs($netAdj), 2)) : '$0.00' }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="p-4 text-xs text-slate-500 text-center">لا توجد أرصدة نقدية مفتوحة بالليرة السورية في دفتر الأستاذ تتطلب إعادة تقييم حالياً.</div>
            @endif
        </div>
    @endif

    <!-- All Operations FX Audit Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">
                سجل أثر فروق صرف العملة على جميع عمليات الليرة السورية ({{ $fx['operations_count'] }} عملية)
            </h2>
            <span class="text-xs text-slate-500">مقارنة سعر صرف كل عملية مع السعر المعتمد حالياً</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                        <th class="py-3 px-3">التاريخ</th>
                        <th class="py-3 px-3">نوع العملية</th>
                        <th class="py-3 px-3">المرجع</th>
                        <th class="py-3 px-3">الجهة / البيان</th>
                        <th class="py-3 px-3 text-left">المبلغ ل.س</th>
                        <th class="py-3 px-3 text-left">سعر العملية</th>
                        <th class="py-3 px-3 text-left">المعادل التاريخي $</th>
                        <th class="py-3 px-3 text-left">المعادل الحالي $</th>
                        <th class="py-3 px-3 text-left">فرق الصرف $</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($fx['rows'] as $row)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-2.5 px-3 font-mono text-slate-500 whitespace-nowrap">{{ $row['date'] }}</td>
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $row['realized'] ? 'bg-brand-50 text-brand-800 border border-brand-200/80' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $row['module_label'] }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 font-mono font-semibold text-slate-800 whitespace-nowrap">{{ $row['reference'] }}</td>
                            <td class="py-2.5 px-3 text-slate-700">
                                <span class="font-medium">{{ $row['party'] }}</span>
                                @if(!empty($row['notes']))
                                    <span class="block text-[11px] text-slate-400">{{ $row['notes'] }}</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-left font-mono text-slate-800 whitespace-nowrap" dir="ltr">{{ number_format($row['amount_syp']) }}</td>
                            <td class="py-2.5 px-3 text-left font-mono text-slate-600 whitespace-nowrap" dir="ltr">{{ number_format($row['op_rate']) }}</td>
                            <td class="py-2.5 px-3 text-left font-mono text-slate-700 whitespace-nowrap" dir="ltr">${{ number_format($row['historical_usd'], 2) }}</td>
                            <td class="py-2.5 px-3 text-left font-mono text-slate-900 whitespace-nowrap" dir="ltr">${{ number_format($row['current_usd'], 2) }}</td>
                            <td class="py-2.5 px-3 text-left font-mono font-bold whitespace-nowrap {{ $row['fx_diff_usd'] > 0 ? 'text-brand-700' : ($row['fx_diff_usd'] < 0 ? 'text-rose-600' : 'text-slate-400') }}" dir="ltr">
                                {{ abs($row['fx_diff_usd']) >= 0.01 ? (($row['fx_diff_usd'] > 0 ? '+' : '-') . '$' . number_format(abs($row['fx_diff_usd']), 2)) : '$0.00' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-slate-400">لا توجد عمليات بالليرة السورية مسجلة لحساب فروق صرفها.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
