@extends('layouts.app')

@section('title', 'كشف حساب')
@section('custom_print_chrome', '1')

@push('styles')
<style>
    @media print {
        @page {
            size: A4 portrait;
            margin: 0;
        }
        html, body {
            display: block !important;
            height: auto !important;
            min-height: 0 !important;
            background: #ffffff !important;
            padding: 0 !important;
            margin: 0 !important;
            overflow: visible !important;
        }
        body > div {
            display: block !important;
            min-height: 0 !important;
            width: 100% !important;
        }
        main {
            display: block !important;
            padding: 0 !important;
            margin: 0 !important;
            max-width: none !important;
            width: 100% !important;
        }
        .statement-sheet {
            display: block !important;
            max-width: none !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 14mm 14mm 30mm 14mm !important;
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }
        .statement-sheet thead {
            display: table-header-group;
        }
        .statement-sheet tr {
            page-break-inside: avoid;
        }
        .statement-print-footer-wrap {
            position: fixed !important;
            bottom: 10mm !important;
            left: 14mm !important;
            right: 14mm !important;
            margin: 0 !important;
            padding: 8px 0 0 0 !important;
            background: #ffffff !important;
            border-top: 1px solid #cbd5e1 !important;
        }
    }
</style>
@endpush

@section('content')
<div class="statement-sheet max-w-4xl mx-auto bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/90 shadow-sm space-y-6">
    <!-- Top Row: Logo on the Right + System Name + Contact Phone -->
    <div class="flex items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/logo-mark.png') }}?v=2" alt="زاهد للحلول المالية" class="h-14 w-14 sm:h-16 sm:w-16 object-contain shrink-0">
            <div class="text-right">
                <div class="text-base sm:text-lg font-extrabold text-brand-950 leading-snug">زاهد للحلول المالية</div>
                <div class="text-xs font-mono font-semibold text-brand-800 mt-0.5 text-right" dir="ltr">0984393644</div>
            </div>
        </div>
        <div class="text-left">
            <span class="inline-block text-xs font-semibold text-slate-500">
                كشف حساب {{ $statement['contact']->type === 'customer' ? 'عميل' : 'مورد' }}
            </span>
            @if(!empty($statement['from']) || !empty($statement['to']))
                <span class="block text-[11px] font-mono text-slate-400 mt-0.5" dir="ltr">
                    {{ $statement['from'] ?: 'البداية' }} → {{ $statement['to'] ?: 'اليوم' }}
                </span>
            @endif
        </div>
    </div>

    <!-- Clear Prominent Client Title with "السيد" -->
    <div class="text-center py-1">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            السيد {{ $statement['contact']->name }}
        </h1>
    </div>

    <!-- Screen-Only Date Range Filter -->
    <form method="GET" action="{{ route('contacts.statement', $statement['contact']) }}" class="no-print flex flex-wrap items-end gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs">
        <div>
            <label for="stmt-from" class="block text-[11px] font-semibold text-slate-600 mb-1">من تاريخ</label>
            <input id="stmt-from" type="date" name="from" value="{{ $statement['from'] ?? '' }}" class="p-2 rounded-lg border border-slate-300 font-mono bg-white">
        </div>
        <div>
            <label for="stmt-to" class="block text-[11px] font-semibold text-slate-600 mb-1">إلى تاريخ</label>
            <input id="stmt-to" type="date" name="to" value="{{ $statement['to'] ?? '' }}" class="p-2 rounded-lg border border-slate-300 font-mono bg-white">
        </div>
        <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-bold transition-colors">
            تصفية
        </button>
        @if(!empty($statement['from']) || !empty($statement['to']))
            <a href="{{ route('contacts.statement', $statement['contact']) }}" class="px-3 py-2 text-slate-600 hover:text-slate-900 border border-slate-300 rounded-lg bg-white">
                إعادة ضبط
            </a>
        @endif
    </form>

    <!-- Opening Balance Strip (when non-zero) -->
    @if(abs((float) ($statement['opening_usd'] ?? 0)) >= 0.01 || abs((float) ($statement['opening_syp'] ?? 0)) >= 0.01)
        <div class="px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200/80 flex flex-wrap items-center justify-between gap-2 text-xs">
            <span class="font-semibold text-slate-600">الرصيد الافتتاحي / المدوّر:</span>
            <span class="font-mono font-bold text-slate-800">
                <span dir="ltr">${{ number_format($statement['opening_usd'], 2) }}</span>
                <span class="mx-1.5 text-slate-300">·</span>
                <span>{{ number_format($statement['opening_syp']) }} <span class="font-sans font-normal text-slate-500">ل.س</span></span>
            </span>
        </div>
    @endif

    <!-- Statement Transactions Table (with full Invoice Contents) -->
    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead>
                <tr class="bg-slate-50 border-y border-slate-200 text-slate-600 font-semibold">
                    <th class="py-3 px-3 whitespace-nowrap">التاريخ</th>
                    <th class="py-3 px-3">البيان ومحتويات الفاتورة</th>
                    <th class="py-3 px-3 text-left whitespace-nowrap">مدين</th>
                    <th class="py-3 px-3 text-left whitespace-nowrap">دائن</th>
                    <th class="py-3 px-3 text-left whitespace-nowrap">عملة</th>
                    <th class="py-3 px-3 text-left whitespace-nowrap">رصيد $</th>
                    <th class="py-3 px-3 text-left whitespace-nowrap">رصيد ل.س</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($statement['rows'] as $row)
                    @php
                        $isInvoice = ($row['kind'] ?? null) === 'invoice';
                        $items = $row['items'] ?? [];
                        $isSyp = ($row['currency'] ?? 'USD') === 'SYP';
                        $dec = $isSyp ? 0 : 2;
                    @endphp
                    <tr class="hover:bg-slate-50/70 align-top">
                        <td class="py-3 px-3 font-mono text-slate-500 whitespace-nowrap">{{ $row['date'] }}</td>
                        <td class="py-3 px-3 text-slate-800">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="font-bold text-slate-900">{{ $row['description'] }}</span>
                                @if($isInvoice)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ ($row['payment_type'] ?? '') === 'cash' ? 'bg-brand-50 text-brand-700 border border-brand-200/80' : 'bg-amber-50 text-amber-800 border border-amber-200/80' }}">
                                        {{ ($row['payment_type'] ?? '') === 'cash' ? 'نقدي' : 'آجل' }}
                                    </span>
                                    @if(($row['discount'] ?? 0) > 0)
                                        <span class="text-[10px] text-amber-700 font-medium">
                                            ({{ $row['discount_label'] ?? 'حسم' }}: <span class="font-mono" dir="ltr">{{ number_format($row['discount'], $dec) }}</span> {{ $row['currency'] }})
                                        </span>
                                    @endif
                                @endif
                            </div>

                            @if($isInvoice && count($items) > 0)
                                <div class="mt-2 rounded-lg border border-slate-200/80 bg-slate-50/80 overflow-hidden">
                                    <table class="w-full text-[11px]">
                                        <thead>
                                            <tr class="border-b border-slate-200/80 text-slate-500 bg-slate-100/70">
                                                <th class="py-1.5 px-2.5 text-right font-semibold">الصنف</th>
                                                <th class="py-1.5 px-2 text-center font-semibold whitespace-nowrap">الكمية</th>
                                                <th class="py-1.5 px-2 text-left font-semibold whitespace-nowrap">السعر</th>
                                                <th class="py-1.5 px-2.5 text-left font-semibold whitespace-nowrap">الإجمالي</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200/60">
                                            @foreach($items as $it)
                                                <tr>
                                                    <td class="py-1.5 px-2.5 font-medium text-slate-700">
                                                        {{ $it['item_name'] }}
                                                        @if(!empty($it['unit']))
                                                            <span class="text-[10px] text-slate-400">({{ $it['unit'] }})</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-1.5 px-2 text-center font-mono text-slate-700" dir="ltr">
                                                        {{ rtrim(rtrim(number_format((float) $it['qty'], 2), '0'), '.') }}
                                                    </td>
                                                    <td class="py-1.5 px-2 text-left font-mono text-slate-600" dir="ltr">
                                                        {{ number_format((float) $it['unit_price'], $dec) }}
                                                    </td>
                                                    <td class="py-1.5 px-2.5 text-left font-mono font-semibold text-slate-800" dir="ltr">
                                                        {{ number_format((float) $it['total'], $dec) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if($isInvoice && !empty($row['notes']))
                                <div class="mt-1 text-[11px] text-slate-500">ملاحظة: {{ $row['notes'] }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-left font-mono text-brand-700 whitespace-nowrap" dir="ltr">{{ $row['debit'] > 0 ? number_format($row['debit'], $dec) : '—' }}</td>
                        <td class="py-3 px-3 text-left font-mono text-rose-600 whitespace-nowrap" dir="ltr">{{ $row['credit'] > 0 ? number_format($row['credit'], $dec) : '—' }}</td>
                        <td class="py-3 px-3 text-left font-mono whitespace-nowrap">{{ $row['currency'] }}</td>
                        <td class="py-3 px-3 text-left font-mono font-bold text-slate-900 whitespace-nowrap" dir="ltr">${{ number_format($row['balance_usd'], 2) }}</td>
                        <td class="py-3 px-3 text-left font-mono text-slate-700 whitespace-nowrap" dir="ltr">{{ number_format($row['balance_syp']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">لا توجد حركات أو فواتير مسجلة لهذا الحساب في الفترة المحددة.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Final Balance Box -->
    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-2 text-sm">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <span class="font-bold text-slate-700">الرصيد النهائي المستحق:</span>
            <span class="font-mono font-extrabold text-lg text-slate-900">
                <span dir="ltr">${{ number_format($statement['final_balance_usd'], 2) }}</span>
                <span class="mx-1.5 text-slate-300">·</span>
                <span>{{ number_format($statement['final_balance_syp']) }} <span class="font-sans text-sm font-semibold text-slate-500">ل.س</span></span>
            </span>
        </div>
        @if(abs((float) ($statement['final_balance_syp'] ?? 0)) >= 0.01 || abs((float) ($statement['syp_fx_diff_usd'] ?? 0)) >= 0.01)
            <div class="pt-2 border-t border-slate-200/80 flex flex-wrap justify-between items-center gap-2 text-xs text-slate-600">
                <span>
                    المعادل الإجمالي بالعملة الأساسية (USD) على سعر الصرف
                    <span class="font-mono font-semibold text-slate-800" dir="ltr">{{ number_format($statement['current_rate'] ?? 14500) }}</span>:
                    <strong class="font-mono text-brand-800 mr-1" dir="ltr">${{ number_format($statement['final_balance_total_usd'] ?? $statement['final_balance_usd'], 2) }}</strong>
                </span>
                @if(abs((float) ($statement['syp_fx_diff_usd'] ?? 0)) >= 0.01)
                    <span class="font-semibold {{ ($statement['syp_fx_diff_usd'] ?? 0) >= 0 ? 'text-brand-700' : 'text-rose-600' }}">
                        فرق صرف العملة:
                        <span class="font-mono inline-block" dir="ltr">{{ ($statement['syp_fx_diff_usd'] ?? 0) >= 0 ? '+' : '-' }}${{ number_format(abs((float) ($statement['syp_fx_diff_usd'] ?? 0)), 2) }}</span>
                    </span>
                @endif
            </div>
        @endif
    </div>

    <!-- Action Buttons (Screen Only) -->
    <div class="no-print flex items-center justify-between pt-1">
        <button type="button" onclick="window.print()" class="no-print inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/></svg>
            <span>طباعة كشف الحساب</span>
        </button>
        <div class="flex items-center gap-3 text-xs">
            <a href="{{ route($statement['contact']->type === 'customer' ? 'reports.customers' : 'reports.suppliers') }}" class="font-medium text-brand-700 hover:text-brand-800">
                {{ $statement['contact']->type === 'customer' ? 'كشف العملاء' : 'كشف الموردين' }}
            </a>
            <span class="text-slate-300">|</span>
            <a href="{{ route('contacts.index', ['type' => $statement['contact']->type]) }}" class="font-medium text-slate-500 hover:text-slate-800">
                العودة للقائمة
            </a>
        </div>
    </div>

    <!-- Bottom Page Footer (Screen + Pinned at Bottom of Printed Page) -->
    <div class="statement-print-footer-wrap border-t border-slate-300 pt-3">
        <div class="table w-full">
            <div class="table-row">
                <!-- Right: Zahed System Name + Phone Number Underneath -->
                <div class="table-cell align-bottom text-right">
                    <div class="text-xs font-bold text-slate-800">زاهد للحلول المالية</div>
                    <div class="text-[11px] font-mono font-semibold text-slate-700 mt-0.5 text-right" dir="ltr">0984393644</div>
                </div>

                <!-- Left: Date & Time -->
                <div class="table-cell align-bottom text-left text-xs text-slate-600">
                    <span>التاريخ والوقت: </span>
                    <span class="font-mono font-semibold text-slate-800" dir="ltr">{{ now()->format('Y-m-d H:i') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
