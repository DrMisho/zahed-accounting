@extends('layouts.app')

@section('title', 'مصاريف أجور الشحن')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">مصاريف أجور الشحن وتكلفة المشتريات</h1>
            <p class="text-xs text-slate-500 mt-1">تُحمّل مصاريف الشحن تلقائياً على تكلفة فواتير الشراء المرتبطة (تحديث متوسط التكلفة WAC وقيمة المخزون وتكلفة البضاعة المباعة)</p>
        </div>
        <a href="{{ route('transactions.index', ['type' => 'purchase']) }}" class="px-4 py-2 text-xs font-bold rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 self-start sm:self-center">
            استعراض فواتير الشراء
        </a>
    </div>

    <!-- Summary KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <span class="text-xs text-slate-500">إجمالي مصاريف أجور الشحن</span>
            <div class="text-2xl font-bold font-mono text-cyan-800 mt-1.5">${{ number_format($stats['total_shipping_usd'], 2) }}</div>
            <span class="text-[11px] text-slate-400">{{ $stats['count'] }} عملية ({{ $stats['linked_count'] }} مرتبطة بفاتورة)</span>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <span class="text-xs text-slate-500">صافي فواتير الشراء</span>
            <div class="text-2xl font-bold font-mono text-slate-900 mt-1.5">${{ number_format($stats['total_purchases_usd'], 2) }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <span class="text-xs text-slate-500">إجمالي تكلفة المشتريات شاملة الشحن</span>
            <div class="text-2xl font-bold font-mono text-brand-900 mt-1.5">${{ number_format($stats['total_landed_usd'], 2) }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <span class="text-xs text-slate-500">نسبة الشحن من المشتريات</span>
            <div class="text-2xl font-bold font-mono text-gold-700 mt-1.5">{{ number_format($stats['shipping_ratio'], 2) }}%</div>
        </div>
    </div>

    <!-- Add Shipping Expense Form -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs text-xs">
        <h2 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100">تسجيل مصروف أجور شحن جديد</h2>

        <form method="POST" action="{{ route('shipping.store') }}" class="mt-4 space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="ship_date" class="block font-bold text-slate-700 mb-1">التاريخ</label>
                    <input id="ship_date" type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required class="w-full p-2.5 rounded-xl border border-slate-300 font-mono">
                </div>

                <div>
                    <label for="ship_voucher" class="block font-bold text-slate-700 mb-1">رقم السند / البوليصة</label>
                    <input id="ship_voucher" type="text" name="voucher_number" value="{{ old('voucher_number', 'SHP-' . date('Ymd-His')) }}" class="w-full p-2.5 rounded-xl border border-slate-300 font-mono">
                </div>

                <div class="sm:col-span-2">
                    <label for="ship_tx" class="block font-bold text-slate-700 mb-1">فاتورة الشراء المرتبطة (لتحميل التكلفة على أصنافها)</label>
                    <select id="ship_tx" name="transaction_id" class="w-full p-2.5 rounded-xl border border-slate-300">
                        <option value="">— شحن عام للمشتريات (يوزع تناسبياً على المشتريات) —</option>
                        @foreach($purchases as $p)
                            <option value="{{ $p->id }}" @selected(old('transaction_id') == $p->id)>
                                {{ $p->invoice_number }} | {{ $p->date->format('Y-m-d') }} | {{ $p->contact->name ?? 'مورد' }} | ${{ number_format((float) $p->total_usd, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="ship_carrier" class="block font-bold text-slate-700 mb-1">شركة الشحن / السائق</label>
                    <input id="ship_carrier" type="text" name="carrier_name" value="{{ old('carrier_name') }}" placeholder="مثال: شركة القدموس / شحن بري" class="w-full p-2.5 rounded-xl border border-slate-300">
                </div>

                <div>
                    <label for="ship_type" class="block font-bold text-slate-700 mb-1">نوع مصروف الشحن</label>
                    <select id="ship_type" name="shipping_type" class="w-full p-2.5 rounded-xl border border-slate-300">
                        <option value="شحن مشتريات">شحن مشتريات</option>
                        <option value="شحن بري">شحن بري</option>
                        <option value="شحن بحري / جوي">شحن بحري / جوي</option>
                        <option value="جمارك وتخليص">جمارك وتخليص</option>
                        <option value="تحميل وتنزيل ونقل داخلي">تحميل وتنزيل ونقل داخلي</option>
                    </select>
                </div>

                <div>
                    <label for="ship_amount" class="block font-bold text-slate-700 mb-1">المبلغ</label>
                    <input id="ship_amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required placeholder="0.00" oninput="calcShipUsd()" class="w-full p-2.5 rounded-xl border border-slate-300 font-mono font-bold text-left">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="ship_currency" class="block font-bold text-slate-700 mb-1">العملة</label>
                        <select id="ship_currency" name="currency" onchange="calcShipUsd()" class="w-full p-2.5 rounded-xl border border-slate-300 font-bold">
                            <option value="USD">USD</option>
                            <option value="SYP">SYP</option>
                        </select>
                    </div>
                    <div>
                        <label for="ship_rate" class="block font-bold text-slate-700 mb-1">سعر الصرف</label>
                        <input id="ship_rate" type="number" step="1" min="1" name="exchange_rate" value="{{ old('exchange_rate', $shop->exchange_rate ?? 14500) }}" required oninput="calcShipUsd()" class="w-full p-2.5 rounded-xl border border-slate-300 font-mono text-left">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                <div class="sm:col-span-2">
                    <label for="ship_desc" class="block font-bold text-slate-700 mb-1">البيان والملاحظات</label>
                    <input id="ship_desc" type="text" name="description" value="{{ old('description') }}" placeholder="تفاصيل الشحنة أو مسار النقل..." class="w-full p-2.5 rounded-xl border border-slate-300">
                </div>
                <div class="p-2.5 rounded-xl bg-cyan-50 border border-cyan-200 flex items-center justify-between">
                    <span class="text-cyan-800 font-semibold">المعادل المضاف للتكلفة:</span>
                    <strong id="ship_usd_preview" class="font-mono text-sm font-bold text-cyan-950">$0.00</strong>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-brand-800 hover:bg-brand-900 text-white rounded-xl font-bold transition-colors">
                    حفظ وربط بتكلفة المشتريات
                </button>
            </div>
        </form>
    </div>

    <!-- Shipping Expenses Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-900">سجل مصاريف أجور الشحن</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                        <th class="py-3 px-4">التاريخ</th>
                        <th class="py-3 px-4">السند</th>
                        <th class="py-3 px-4">فاتورة الشراء المرتبطة</th>
                        <th class="py-3 px-4">الناقل / النوع</th>
                        <th class="py-3 px-4 text-left">أجرة الشحن</th>
                        <th class="py-3 px-4 text-left">المعادل ($)</th>
                        <th class="py-3 px-4 text-left">تكلفة الفاتورة بعد الشحن</th>
                        <th class="py-3 px-4 text-center">إجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $r)
                        @php
                            $tx = $r->transaction;
                            $txLanded = $tx ? ((float) $tx->total_usd + (float) $tx->shippingExpenses()->sum('amount_usd')) : null;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 px-4 font-mono text-slate-500">{{ $r->date->format('Y-m-d') }}</td>
                            <td class="py-3 px-4 font-mono font-semibold">{{ $r->voucher_number ?: 'SHP-' . $r->id }}</td>
                            <td class="py-3 px-4">
                                @if($tx)
                                    <a href="{{ route('transactions.show', $tx) }}" class="font-mono font-bold text-brand-800 hover:underline">
                                        {{ $tx->invoice_number }}
                                    </a>
                                    <span class="block text-[11px] text-slate-400">{{ $tx->contact->name ?? '' }} (صافي ${{ number_format((float) $tx->total_usd, 2) }})</span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[11px]">شحن عام للمشتريات</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-semibold text-slate-800">{{ $r->shipping_type }}</span>
                                @if($r->carrier_name)
                                    <span class="block text-[11px] text-slate-500">{{ $r->carrier_name }}</span>
                                @endif
                                @if($r->description)
                                    <span class="block text-[11px] text-slate-400">{{ $r->description }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-left font-mono font-bold">{{ number_format((float) $r->amount, $r->currency === 'SYP' ? 0 : 2) }} {{ $r->currency }}</td>
                            <td class="py-3 px-4 text-left font-mono font-bold text-cyan-800">${{ number_format((float) $r->amount_usd, 2) }}</td>
                            <td class="py-3 px-4 text-left font-mono font-bold text-brand-900">
                                @if($txLanded !== null)
                                    ${{ number_format($txLanded, 2) }}
                                @else
                                    <span class="text-slate-400 font-sans font-normal">موزّع تناسبياً</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <form method="POST" action="{{ route('shipping.destroy', $r) }}" onsubmit="return confirm('هل تريد حذف مصروف الشحن وإعادة احتساب تكلفة المشتريات؟')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-rose-500 hover:text-rose-700" title="حذف">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">لا توجد مصاريف أجور شحن مسجلة بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function calcShipUsd() {
        const amount = parseFloat(document.getElementById('ship_amount').value || 0);
        const cur = document.getElementById('ship_currency').value;
        const rate = parseFloat(document.getElementById('ship_rate').value || 14500);
        const usd = cur === 'USD' ? amount : (rate > 0 ? amount / rate : 0);
        document.getElementById('ship_usd_preview').textContent = '$' + usd.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    calcShipUsd();
</script>
@endsection
