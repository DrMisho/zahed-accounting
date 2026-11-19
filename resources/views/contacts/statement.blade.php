@extends('layouts.app')

@section('title', 'كشف حساب')
@section('custom_print_chrome', '1')

@push('styles')
<style>
    @media print {
        @page {
            size: A4;
            margin: 0;
        }
        body {
            background: #ffffff !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        main {
            padding: 0 !important;
            margin: 0 !important;
            max-width: none !important;
        }
        .statement-sheet {
            max-width: none !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 14mm 14mm 32mm 14mm !important;
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
            position: fixed;
            bottom: 8mm;
            left: 14mm;
            right: 14mm;
            background: #ffffff;
            padding-top: 6px;
        }
    }
</style>
@endpush

@section('content')
<div class="statement-sheet max-w-4xl mx-auto bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/90 shadow-sm space-y-6">
    <!-- Top Row: Zahed Logo on the Right Only (no Zahed name or client name in top header) -->
    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
        <div class="flex items-center">
            <img src="{{ asset('images/logo-mark.png') }}?v=2" alt="شعار زاهد" class="h-16 w-16 object-contain">
        </div>
        <div class="text-left">
            <span class="inline-block text-xs font-semibold text-slate-500">
                كشف حساب {{ $statement['contact']->type === 'customer' ? 'عميل' : 'مورد' }}
            </span>
        </div>
    </div>

    <!-- Clear Prominent Client Title with "السيد" -->
    <div class="text-center py-1">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            السيد {{ $statement['contact']->name }}
        </h1>
    </div>

    <!-- Statement Transactions Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead>
                <tr class="bg-slate-50 border-y border-slate-200 text-slate-600 font-semibold">
                    <th class="py-3 px-3">التاريخ</th>
                    <th class="py-3 px-3">البيان والحركة</th>
                    <th class="py-3 px-3 text-left">مدين</th>
                    <th class="py-3 px-3 text-left">دائن</th>
                    <th class="py-3 px-3 text-left">عملة</th>
                    <th class="py-3 px-3 text-left">رصيد $</th>
                    <th class="py-3 px-3 text-left">رصيد ل.س</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($statement['rows'] as $row)
                    <tr class="hover:bg-slate-50">
                        <td class="py-2.5 px-3 font-mono text-slate-500">{{ $row['date'] }}</td>
                        <td class="py-2.5 px-3 text-slate-800">{{ $row['description'] }}</td>
                        <td class="py-2.5 px-3 text-left font-mono text-brand-700">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '—' }}</td>
                        <td class="py-2.5 px-3 text-left font-mono text-rose-600">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '—' }}</td>
                        <td class="py-2.5 px-3 text-left font-mono">{{ $row['currency'] }}</td>
                        <td class="py-2.5 px-3 text-left font-mono font-bold text-slate-900">${{ number_format($row['balance_usd'], 2) }}</td>
                        <td class="py-2.5 px-3 text-left font-mono text-slate-700">{{ number_format($row['balance_syp']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">لا توجد حركات آجلة أو دفعات مسجلة لهذا الحساب.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Final Balance Box -->
    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 flex flex-wrap justify-between items-center gap-2 text-sm">
        <span class="font-bold text-slate-700">الرصيد النهائي المستحق:</span>
        <span class="font-mono font-extrabold text-lg text-slate-900">
            <span dir="ltr">${{ number_format($statement['final_balance_usd'], 2) }}</span>
            <span class="mx-1.5 text-slate-300">·</span>
            <span>{{ number_format($statement['final_balance_syp']) }} <span class="font-sans text-sm font-semibold text-slate-500">ل.س</span></span>
        </span>
    </div>

    <!-- Action Buttons (Screen Only) -->
    <div class="no-print flex items-center justify-between pt-1">
        <button type="button" onclick="window.print()" class="no-print inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/></svg>
            <span>طباعة كشف الحساب</span>
        </button>
        <a href="{{ route('contacts.index', ['type' => $statement['contact']->type]) }}" class="text-xs font-medium text-slate-500 hover:text-slate-800">
            العودة للقائمة
        </a>
    </div>

    <!-- Bottom Page Footer (Screen + Pinned at Bottom of Printed Page) -->
    <div class="statement-print-footer-wrap pt-4">
        <div class="border-t border-slate-300 pt-3 flex items-end justify-between gap-4">
            <!-- Right: Zahed System Name + Phone Number Underneath -->
            <div class="text-right">
                <div class="text-xs font-bold text-slate-800">نظام زاهد للحلول المحاسبية والمالية</div>
                <div class="text-[11px] font-mono text-slate-600 mt-0.5 text-right" dir="ltr">0984393644</div>
            </div>

            <!-- Left: Date & Time -->
            <div class="text-left text-xs text-slate-600">
                <span>التاريخ والوقت: </span>
                <span class="font-mono font-semibold text-slate-800" dir="ltr">{{ now()->format('Y-m-d H:i') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
