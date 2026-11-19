@extends('layouts.app')

@section('title', 'المخزون والأصناف')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">إدارة الأصناف وجرد المخزون (SRS 4.د)</h1>
            <p class="text-xs text-slate-500 mt-1">حساب متوسط التكلفة المرجح (WAC) والكميات المتوفرة وتنبيهات حد الطلب</p>
        </div>
        <button onclick="openItemModal(null)" class="px-4 py-2.5 text-xs font-bold rounded-xl bg-brand-800 hover:bg-brand-900 text-white shadow-xs transition-colors self-start sm:self-center">
            + تعريف صنف جديد
        </button>
    </div>

    <!-- Items Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                        <th class="py-3 px-4">اسم الصنف</th>
                        <th class="py-3 px-4 text-center">الوحدة</th>
                        <th class="py-3 px-4 text-center">الرصيد الافتتاحي</th>
                        <th class="py-3 px-4 text-center">كمية الشراء</th>
                        <th class="py-3 px-4 text-center">كمية البيع</th>
                        <th class="py-3 px-4 text-center">الكمية المتوفرة</th>
                        <th class="py-3 px-4 text-center">حد التنبيه</th>
                        <th class="py-3 px-4 text-left">متوسط التكلفة WAC ($)</th>
                        <th class="py-3 px-4 text-left">إجمالي القيمة ($)</th>
                        <th class="py-3 px-4 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($stockReports as $sr)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                @if($sr['is_low_stock'])
                                    <span class="text-gold-600 font-bold ml-1">⚠️</span>
                                @endif
                                {{ $sr['item']->item_name }}
                            </td>
                            <td class="py-3.5 px-4 text-center text-slate-500">{{ $sr['item']->unit }}</td>
                            <td class="py-3.5 px-4 text-center font-mono">{{ $sr['opening_qty'] }}</td>
                            <td class="py-3.5 px-4 text-center font-mono text-brand-800">+{{ $sr['purchased_qty'] }}</td>
                            <td class="py-3.5 px-4 text-center font-mono text-rose-600">-{{ $sr['sold_qty'] }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded font-mono font-bold text-xs {{ $sr['is_low_stock'] ? 'bg-gold-100 text-gold-800' : 'bg-brand-50 text-brand-900' }}">
                                    {{ $sr['current_stock'] }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono text-slate-400">{{ $sr['item']->alert_limit }}</td>
                            <td class="py-3.5 px-4 text-left font-mono">${{ number_format($sr['wac_usd'], 2) }}</td>
                            <td class="py-3.5 px-4 text-left font-mono font-bold text-brand-800">${{ number_format($sr['stock_value_usd'], 2) }}</td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @php
    $itemData = [
        'id' => $sr['item']->id,
        'item_name' => $sr['item']->item_name,
        'code' => $sr['item']->code,
        'category' => $sr['item']->category,
        'unit' => $sr['item']->unit,
        'alert_limit' => $sr['item']->alert_limit,
        'opening_qty' => $sr['item']->opening_qty,
        'opening_cost_usd' => $sr['item']->opening_cost_usd,
    ];
@endphp

<button type="button"
        class="px-2 py-1 text-brand-800 hover:bg-brand-50 rounded-lg font-bold"
        data-item='@json($itemData)'
        onclick="openItemModal(JSON.parse(this.dataset.item))">
    تعديل
</button>
                                <form method="POST" action="{{ route('items.destroy', $sr['item']) }}" onsubmit="return confirm('هل تريد حذف هذا الصنف؟')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-rose-500 hover:text-rose-700">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-slate-400">لا توجد أصناف معرفة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add/Edit Item -->
<div id="modal-item" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex justify-between items-center pb-3 border-b">
            <h2 id="item-modal-title" class="text-base font-bold text-slate-900">تعريف صنف جديد بالمخزن</h2>
            <button onclick="document.getElementById('modal-item').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 font-bold">✕</button>
        </div>
        <form id="item-form" method="POST" action="{{ route('items.store') }}" class="mt-4 space-y-3">
            @csrf
            <input type="hidden" id="item-method" value="POST">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">اسم الصنف</label>
                <input type="text" name="item_name" id="item_name" required placeholder="مثال: شاشة سامسونج 55 بوصة" class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold mb-1">كود الصنف</label>
                    <input type="text" name="code" id="item_code" class="w-full text-xs p-2.5 rounded-xl border font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1">التصنيف</label>
                    <input type="text" name="category" id="item_category" class="w-full text-xs p-2.5 rounded-xl border">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">الوحدة</label>
                    <select name="unit" id="item_unit" class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
                        <option value="قطعة">قطعة</option>
                        <option value="كرتونة">كرتونة</option>
                        <option value="كغ">كيلوغرام</option>
                        <option value="متر">متر</option>
                        <option value="علبة">علبة</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">حد التنبيه</label>
                    <input type="number" name="alert_limit" id="item_alert_limit" value="5" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300 font-mono text-left">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">الكمية الافتتاحية</label>
                    <input type="number" step="0.01" name="opening_qty" id="item_opening_qty" value="0" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300 font-mono text-left">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">تكلفة الوحدة بالدولار ($)</label>
                    <input type="number" step="0.01" name="opening_cost_usd" id="item_opening_cost_usd" value="0" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300 font-mono text-left">
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-item').classList.add('hidden')" class="px-4 py-2 text-xs text-slate-600">إلغاء</button>
                <button type="submit" id="item-submit" class="px-5 py-2 text-xs font-bold text-white bg-brand-800 rounded-xl">حفظ الصنف</button>
            </div>
        </form>
    </div>
</div>
<script>
    function openItemModal(item) {
        const form = document.getElementById('item-form');
        const method = document.getElementById('item-method');
        document.getElementById('modal-item').classList.remove('hidden');
        if (item) {
            document.getElementById('item-modal-title').textContent = 'تعديل الصنف';
            document.getElementById('item-submit').textContent = 'حفظ التعديل';
            form.action = '{{ url('items') }}/' + item.id;
            method.setAttribute('name', '_method');
            method.value = 'PUT';
            document.getElementById('item_name').value = item.item_name || '';
            document.getElementById('item_code').value = item.code || '';
            document.getElementById('item_category').value = item.category || '';
            document.getElementById('item_unit').value = item.unit || 'قطعة';
            document.getElementById('item_alert_limit').value = item.alert_limit ?? 0;
            document.getElementById('item_opening_qty').value = item.opening_qty ?? 0;
            document.getElementById('item_opening_cost_usd').value = item.opening_cost_usd ?? 0;
        } else {
            document.getElementById('item-modal-title').textContent = 'تعريف صنف جديد بالمخزن';
            document.getElementById('item-submit').textContent = 'حفظ الصنف';
            form.action = '{{ route('items.store') }}';
            method.value = 'POST';
            method.removeAttribute('name');
            form.reset();
            document.getElementById('item_alert_limit').value = 5;
        }
    }
</script>
@endsection
