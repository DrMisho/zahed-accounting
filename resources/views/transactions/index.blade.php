@extends('layouts.app')

@section('title', 'فواتير البيع والشراء')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">فواتير البيع والشراء والعمليات اليومية</h1>
            <p class="text-xs text-slate-500 mt-1">تسجيل فواتير نقدي وآجل مع المعادل بالدولار لحظياً</p>
        </div>
        <a href="{{ route('transactions.create') }}" class="px-4 py-2.5 text-xs font-bold rounded-xl bg-brand-800 hover:bg-brand-900 text-white shadow-xs transition-colors self-start sm:self-center">
            + إضافة فاتورة جديدة
        </a>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                        <th class="py-3 px-4">رقم الفاتورة</th>
                        <th class="py-3 px-4">التاريخ</th>
                        <th class="py-3 px-4">نوع العملية</th>
                        <th class="py-3 px-4">طريقة الدفع</th>
                        <th class="py-3 px-4">الطرف الثاني</th>
                        <th class="py-3 px-4">الصنف والكمية</th>
                        <th class="py-3 px-4 text-left">السعر الإفرادي</th>
                        <th class="py-3 px-4 text-left">الإجمالي بالعملة</th>
                        <th class="py-3 px-4 text-left">المعادل بالدولار (USD)</th>
                        <th class="py-3 px-4 text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 px-4 font-mono font-bold">{{ $tx->invoice_number }}</td>
                            <td class="py-3 px-4 font-mono text-slate-500">{{ $tx->date->format('Y-m-d') }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $tx->type === 'sale' ? 'bg-brand-50 text-brand-800' : 'bg-blue-50 text-blue-700' }}">
                                    {{ $tx->type === 'sale' ? 'بيع' : ($tx->type === 'purchase' ? 'شراء' : 'مرتجع') }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[11px] {{ $tx->payment_type === 'cash' ? 'bg-brand-50 text-brand-800' : 'bg-gold-50 text-gold-700' }}">
                                    {{ $tx->payment_type === 'cash' ? 'نقدي (صندوق)' : 'آجل (ذمة)' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-medium">{{ $tx->contact->name ?? '—' }}</td>
                            <td class="py-3 px-4">{{ $tx->item->item_name ?? '—' }} ({{ $tx->qty }})</td>
                            <td class="py-3 px-4 text-left font-mono">{{ number_format($tx->unit_price) }}</td>
                            <td class="py-3 px-4 text-left font-mono font-bold">{{ number_format($tx->total) }} {{ $tx->currency }}</td>
                            <td class="py-3 px-4 text-left font-mono font-bold text-brand-800">${{ number_format($tx->total_usd, 2) }}</td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('transactions.show', $tx) }}" class="p-1.5 text-slate-500 hover:text-slate-800" title="طباعة">
                                        🖨️
                                    </a>
                                    <form method="POST" action="{{ route('transactions.destroy', $tx) }}" onsubmit="return confirm('هل أنت متأكد من الحذف؟')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-rose-500 hover:text-rose-700">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-slate-400">لا توجد فواتير مسجلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
