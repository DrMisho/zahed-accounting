@extends('layouts.app')

@section('title', 'حركة الصندوق')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">حركة الصندوق التراكمي</h1>
            <p class="text-xs text-slate-500 mt-1">تتبع الوارد والصادر لكل من صندوق الدولار وصندوق الليرة السورية</p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-center">
            <a href="{{ route('currency-exchanges.index') }}" class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 transition-colors">
                ⇄ تحويل عملة (SYP / USD)
            </a>
            <button onclick="document.getElementById('modal-movement').classList.remove('hidden')" class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-sm transition-colors">
                + تسجيل سند صندوق
            </button>
        </div>
    </div>

    <!-- Dual Balance Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <!-- USD Cashbox -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
            <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                <span class="text-xs font-bold text-slate-500">صندوق الدولار (USD)</span>
                <span class="px-2 py-0.5 rounded bg-brand-50 text-brand-800 font-bold text-xs">$ Cashbox</span>
            </div>
            <div class="text-3xl font-bold font-mono text-slate-900 mt-4">
                ${{ number_format($summary['usd']['balance'], 2) }}
            </div>
            <div class="grid grid-cols-2 gap-2 mt-4 pt-3 border-t text-xs">
                <div class="p-2.5 bg-brand-50 rounded-xl text-brand-900">
                    <span class="block text-[11px] text-brand-800">إجمالي الوارد</span>
                    <strong class="font-mono text-sm">+${{ number_format($summary['usd']['inflows']) }}</strong>
                </div>
                <div class="p-2.5 bg-rose-50 rounded-xl text-rose-800">
                    <span class="block text-[11px] text-rose-600">إجمالي الصادر</span>
                    <strong class="font-mono text-sm">-${{ number_format($summary['usd']['outflows']) }}</strong>
                </div>
            </div>
        </div>

        <!-- SYP Cashbox -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
            <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                <span class="text-xs font-bold text-slate-500">صندوق الليرة السورية (SYP)</span>
                <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold text-xs">SYP Cashbox</span>
            </div>
            <div class="text-3xl font-bold font-mono text-slate-900 mt-4">
                {{ number_format($summary['syp']['balance']) }} <span class="text-sm font-normal text-slate-400">ل.س</span>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-4 pt-3 border-t text-xs">
                <div class="p-2.5 bg-blue-50 rounded-xl text-blue-800">
                    <span class="block text-[11px] text-blue-600">إجمالي الوارد</span>
                    <strong class="font-mono text-sm">+{{ number_format($summary['syp']['inflows']) }}</strong>
                </div>
                <div class="p-2.5 bg-rose-50 rounded-xl text-rose-800">
                    <span class="block text-[11px] text-rose-600">إجمالي الصادر</span>
                    <strong class="font-mono text-sm">-{{ number_format($summary['syp']['outflows']) }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Movements Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                        <th class="py-3 px-4">التاريخ</th>
                        <th class="py-3 px-4">نوع الحركة</th>
                        <th class="py-3 px-4">الجهة / التصنيف</th>
                        <th class="py-3 px-4 text-left">المبلغ بالعملة</th>
                        <th class="py-3 px-4 text-left">المعادل بالدولار</th>
                        <th class="py-3 px-4">ملاحظات</th>
                        <th class="py-3 px-4 text-center">إجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($movements as $cm)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 px-4 font-mono text-slate-500">{{ $cm->date->format('Y-m-d') }}</td>
                            <td class="py-3 px-4 font-bold">
                                @if($cm->source_type === 'currency_exchange_in')
                                    تحويل عملة (وارد)
                                @elseif($cm->source_type === 'currency_exchange_out')
                                    تحويل عملة (صادر)
                                @elseif($cm->source_type === 'shipping_expense')
                                    مصاريف أجور شحن
                                @else
                                    {{ $cm->type === 'receipt' ? 'قبض من عميل' : ($cm->type === 'payment' ? 'صرف لمورد' : ($cm->type === 'expense' ? 'مصروف' : ($cm->type === 'salary' ? 'رواتب' : ($cm->type === 'withdrawal' ? 'مسحوبات شركاء' : 'رصيد افتتاحي')))) }}
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-600">{{ $cm->contact->name ?? $cm->category ?? '—' }}</td>
                            <td class="py-3 px-4 text-left font-mono font-bold">{{ number_format($cm->amount) }} {{ $cm->currency }}</td>
                            <td class="py-3 px-4 text-left font-mono font-bold text-brand-800">${{ number_format($cm->amount_usd, 2) }}</td>
                            <td class="py-3 px-4 text-slate-500">{{ $cm->notes ?? '—' }}</td>
                            <td class="py-3 px-4 text-center">
                                <form method="POST" action="{{ route('cashbox.destroy', $cm) }}" onsubmit="return confirm('هل تريد حذف هذا السند؟')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-rose-500 hover:text-rose-700">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">لا توجد حركات صندوق مسجلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $movements->links() }}
        </div>
    </div>
</div>

<!-- Modal Create Movement -->
<div id="modal-movement" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex justify-between items-center pb-3 border-b">
            <h2 class="text-base font-bold text-slate-900">تسجيل سند حركة صندوق</h2>
            <button onclick="document.getElementById('modal-movement').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 font-bold">✕</button>
        </div>
        <form method="POST" action="{{ route('cashbox.store') }}" class="mt-4 space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">نوع الحركة</label>
                <select name="type" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
                    <option value="receipt">قبض دفعة من عميل (وارد +)</option>
                    <option value="payment">صرف دفعة لمورد (صادر -)</option>
                    <option value="expense">مصاريف عامة وتشغيلية (صادر -)</option>
                    <option value="salary">رواتب وأجور موظفين (صادر -)</option>
                    <option value="withdrawal">مسحوبات شخصية للشركاء (صادر -)</option>
                    <option value="opening">رصيد افتتاحي للصندوق (وارد +)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">التاريخ</label>
                <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300 font-mono">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">الجهة (إذا كان عميلاً أو مورداً)</label>
                <select name="contact_id" class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
                    <option value="">-- اختياري --</option>
                    @foreach($contacts as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->type }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">التصنيف أو اسم البند (في حال المصاريف)</label>
                <input type="text" name="category" placeholder="مثال: فاتورة كهرباء" class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">المبلغ</label>
                    <input type="number" step="0.01" min="0.01" name="amount" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300 font-mono font-bold text-left">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">العملة</label>
                    <select name="currency" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 font-bold">
                        <option value="USD">دولار (USD)</option>
                        <option value="SYP">ليرة سورية (SYP)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">سعر الصرف</label>
                <input type="number" step="1" name="exchange_rate" value="{{ $shop->exchange_rate ?? 14500 }}" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300 font-mono text-left">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">البيان والملاحظات</label>
                <input type="text" name="notes" placeholder="ملاحظات السند..." class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-movement').classList.add('hidden')" class="px-4 py-2 text-xs text-slate-600">إلغاء</button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-brand-800 rounded-xl">حفظ السند</button>
            </div>
        </form>
    </div>
</div>
@endsection
