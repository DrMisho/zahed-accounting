@extends('layouts.app')

@section('title', 'تحويل العملة (SYP ⇄ USD)')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">تحويل العملة بين الليرة السورية والدولار الأمريكي</h1>
            <p class="text-xs text-slate-500 mt-1">تصريف فوري مع ترحيل تلقائي لصندوقي الدولار والليرة السورية دون التأثير على ذمم العملاء والموردين</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 self-start sm:self-center">
            <a href="{{ route('reports.fx') }}" class="px-4 py-2 text-xs font-bold rounded-xl border border-brand-300 bg-brand-50 text-brand-800 hover:bg-brand-100">
                تقرير فروق صرف العملة
            </a>
            <a href="{{ route('cashbox.index') }}" class="px-4 py-2 text-xs font-bold rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50">
                العودة إلى حركة الصندوق
            </a>
        </div>
    </div>

    <!-- Live Dual Cashbox Balances -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-500">رصيد صندوق الدولار الحالي</span>
            <div class="text-2xl font-bold font-mono text-brand-900 mt-2">${{ number_format($summary['usd']['balance'], 2) }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-500">رصيد صندوق الليرة الحالي</span>
            <div class="text-2xl font-bold font-mono text-blue-900 mt-2">{{ number_format($summary['syp']['balance']) }} <span class="text-xs font-normal text-slate-400">ل.س</span></div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-500">إجمالي المحوّل (ل.س ← $)</span>
            <div class="text-xl font-bold font-mono text-emerald-700 mt-2">${{ number_format($stats['syp_to_usd_usd'], 2) }}</div>
            <div class="text-[11px] font-mono text-slate-400 mt-0.5">{{ number_format($stats['syp_to_usd_syp']) }} ل.س</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-500">إجمالي المحوّل ($ ← ل.س)</span>
            <div class="text-xl font-bold font-mono text-amber-700 mt-2">{{ number_format($stats['usd_to_syp_syp']) }} <span class="text-xs font-normal">ل.س</span></div>
            <div class="text-[11px] font-mono text-slate-400 mt-0.5">${{ number_format($stats['usd_to_syp_usd'], 2) }}</div>
        </div>
    </div>

    @php $totalFxDiff = (float) ($stats['total_fx_diff_usd'] ?? 0); @endphp
    <div class="p-4 rounded-2xl border flex flex-wrap items-center justify-between gap-2 text-xs {{ $totalFxDiff >= 0 ? 'bg-brand-50 border-brand-200 text-brand-900' : 'bg-rose-50 border-rose-200 text-rose-900' }}">
        <div>
            <strong>العملة الأساسية: الدولار الأمريكي (USD)</strong> ·
            سعر الصرف المعتمد في الإعدادات: <span class="font-mono font-bold" dir="ltr">{{ number_format((float) ($shop->exchange_rate ?? 14500)) }}</span> ل.س
        </div>
        <div class="font-bold">
            صافي فروق الصرف المحققة من التحويلات:
            <span class="font-mono text-sm mr-1 inline-block" dir="ltr">{{ $totalFxDiff >= 0 ? '+' : '-' }}${{ number_format(abs($totalFxDiff), 2) }}</span>
        </div>
    </div>

    <!-- Conversion Form -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
        <h2 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100">تسجيل عملية تحويل عملة جديدة</h2>

        <form method="POST" action="{{ route('currency-exchanges.store') }}" class="mt-4 space-y-4">
            @csrf

            <!-- Direction Toggle Buttons -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="cursor-pointer">
                    <input type="radio" name="direction" value="syp_to_usd" checked class="peer sr-only" onchange="calcExchange()">
                    <div class="p-4 rounded-2xl border-2 border-slate-200 peer-checked:border-brand-800 peer-checked:bg-brand-50 transition-colors">
                        <div class="font-bold text-sm text-slate-900">من ليرة سورية (SYP) ← إلى دولار (USD)</div>
                        <div class="text-xs text-slate-500 mt-1">سحب من صندوق الليرة السورية وإيداع المعادل في صندوق الدولار</div>
                    </div>
                </label>

                <label class="cursor-pointer">
                    <input type="radio" name="direction" value="usd_to_syp" class="peer sr-only" onchange="calcExchange()">
                    <div class="p-4 rounded-2xl border-2 border-slate-200 peer-checked:border-blue-700 peer-checked:bg-blue-50 transition-colors">
                        <div class="font-bold text-sm text-slate-900">من دولار (USD) ← إلى ليرة سورية (SYP)</div>
                        <div class="text-xs text-slate-500 mt-1">سحب من صندوق الدولار وإيداع المعادل في صندوق الليرة السورية</div>
                    </div>
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div>
                    <label for="ex_date" class="block font-bold text-slate-700 mb-1">التاريخ</label>
                    <input id="ex_date" type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required class="w-full p-2.5 rounded-xl border border-slate-300 font-mono">
                </div>

                <div>
                    <label for="ex_ref" class="block font-bold text-slate-700 mb-1">رقم المرجع / السند</label>
                    <input id="ex_ref" type="text" name="reference_number" value="{{ old('reference_number', 'EX-' . date('Ymd-His')) }}" class="w-full p-2.5 rounded-xl border border-slate-300 font-mono">
                </div>

                <div>
                    <label for="from_amount" id="from_amount_label" class="block font-bold text-slate-700 mb-1">المبلغ بالليرة السورية (SYP)</label>
                    <input id="from_amount" type="number" step="0.01" min="0.01" name="from_amount" value="{{ old('from_amount') }}" required placeholder="0.00" oninput="calcExchange()" class="w-full p-2.5 rounded-xl border border-slate-300 font-mono font-bold text-left">
                </div>

                <div>
                    <label for="exchange_rate" class="block font-bold text-slate-700 mb-1">سعر الصرف (ل.س لكل 1$)</label>
                    <input id="exchange_rate" type="number" step="1" min="1" name="exchange_rate" value="{{ old('exchange_rate', $shop->exchange_rate ?? 14500) }}" required oninput="calcExchange()" class="w-full p-2.5 rounded-xl border border-slate-300 font-mono font-bold text-left">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs items-end">
                <div>
                    <label for="ex_notes" class="block font-bold text-slate-700 mb-1">ملاحظات / اسم مكتب الصرافة</label>
                    <input id="ex_notes" type="text" name="notes" value="{{ old('notes') }}" placeholder="مثال: تصريف يومي في الصندوق..." class="w-full p-2.5 rounded-xl border border-slate-300">
                </div>

                <div class="p-3.5 rounded-xl bg-gold-50 border border-gold-200 flex items-center justify-between">
                    <div>
                        <span class="block text-[11px] text-gold-800 font-semibold">المبلغ الناتج بعد التحويل</span>
                        <span id="exchange_preview_desc" class="block text-[11px] text-slate-500 mt-0.5">أدخل المبلغ وسعر الصرف للمعاينة</span>
                    </div>
                    <strong id="to_amount_preview" class="font-mono text-lg font-extrabold text-brand-900">0.00 USD</strong>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="px-6 py-2.5 text-xs font-bold text-white bg-brand-800 hover:bg-brand-900 rounded-xl shadow-xs transition-colors">
                    تأكيد وترحيل التحويل للصندوقين
                </button>
            </div>
        </form>
    </div>

    <!-- Exchange History Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">سجل عمليات تحويل العملة ({{ $stats['count'] }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                        <th class="py-3 px-4">التاريخ</th>
                        <th class="py-3 px-4">المرجع</th>
                        <th class="py-3 px-4">اتجاه التحويل</th>
                        <th class="py-3 px-4 text-left">المبلغ الصادر</th>
                        <th class="py-3 px-4 text-left">سعر الصرف</th>
                        <th class="py-3 px-4 text-left">المبلغ الوارد</th>
                        <th class="py-3 px-4 text-left">فرق الصرف $</th>
                        <th class="py-3 px-4">ملاحظات</th>
                        <th class="py-3 px-4 text-center">إجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php $refShopRate = (float) ($shop->exchange_rate > 0 ? $shop->exchange_rate : 14500); @endphp
                    @forelse($rows as $r)
                        @php $rowFx = $r->fxDiffUsd($refShopRate); @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 px-4 font-mono text-slate-500">{{ $r->date->format('Y-m-d') }}</td>
                            <td class="py-3 px-4 font-mono font-bold text-slate-800">{{ $r->reference_number ?: 'EX-' . $r->id }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded font-bold text-[11px] {{ $r->direction === 'syp_to_usd' ? 'bg-brand-50 text-brand-800' : 'bg-blue-50 text-blue-700' }}">
                                    {{ $r->direction === 'syp_to_usd' ? 'ليرة سورية ← دولار' : 'دولار ← ليرة سورية' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-left font-mono font-bold text-rose-700">
                                -{{ number_format((float) $r->from_amount, $r->from_currency === 'SYP' ? 0 : 2) }} {{ $r->from_currency }}
                            </td>
                            <td class="py-3 px-4 text-left font-mono text-slate-600">{{ number_format((float) $r->exchange_rate) }}</td>
                            <td class="py-3 px-4 text-left font-mono font-bold text-emerald-700">
                                +{{ number_format((float) $r->to_amount, $r->to_currency === 'SYP' ? 0 : 2) }} {{ $r->to_currency }}
                            </td>
                            <td class="py-3 px-4 text-left font-mono font-bold {{ $rowFx > 0 ? 'text-brand-700' : ($rowFx < 0 ? 'text-rose-600' : 'text-slate-400') }}" dir="ltr">
                                {{ abs($rowFx) >= 0.01 ? (($rowFx > 0 ? '+' : '-') . '$' . number_format(abs($rowFx), 2)) : '$0.00' }}
                            </td>
                            <td class="py-3 px-4 text-slate-500">{{ $r->notes ?: '—' }}</td>
                            <td class="py-3 px-4 text-center">
                                <form method="POST" action="{{ route('currency-exchanges.destroy', $r) }}" onsubmit="return confirm('هل تريد حذف عملية التحويل وعكس أثرها على الصندوقين؟')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-rose-500 hover:text-rose-700" title="حذف">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400">لا توجد عمليات تحويل عملة مسجلة بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function calcExchange() {
        const direction = document.querySelector('input[name="direction"]:checked')?.value || 'syp_to_usd';
        const fromAmount = parseFloat(document.getElementById('from_amount').value || 0);
        const rate = parseFloat(document.getElementById('exchange_rate').value || 0);
        const fromLabel = document.getElementById('from_amount_label');
        const preview = document.getElementById('to_amount_preview');
        const desc = document.getElementById('exchange_preview_desc');

        if (direction === 'syp_to_usd') {
            fromLabel.textContent = 'المبلغ بالليرة السورية (SYP)';
            const result = rate > 0 ? (fromAmount / rate) : 0;
            preview.textContent = '$' + result.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            desc.textContent = fromAmount > 0
                ? 'سحب ' + fromAmount.toLocaleString() + ' ل.س ← إيداع $' + result.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : 'أدخل المبلغ وسعر الصرف للمعاينة';
        } else {
            fromLabel.textContent = 'المبلغ بالدولار الأمريكي (USD)';
            const result = fromAmount * rate;
            preview.textContent = Math.round(result).toLocaleString() + ' ل.س';
            desc.textContent = fromAmount > 0
                ? 'سحب $' + fromAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ← إيداع ' + Math.round(result).toLocaleString() + ' ل.س'
                : 'أدخل المبلغ وسعر الصرف للمعاينة';
        }
    }
    calcExchange();
</script>
@endsection
