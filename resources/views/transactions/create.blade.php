@extends('layouts.app')

@section('title', 'تسجيل فاتورة جديدة')

@section('content')
<div class="max-w-4xl mx-auto bg-white rounded-3xl p-6 sm:p-8 border border-slate-200">
    <div class="border-b pb-4 border-slate-100 flex items-center justify-between">
        <h1 class="text-lg font-bold text-slate-900">فاتورة متعددة البنود</h1>
        <a href="{{ route('transactions.index') }}" class="text-xs text-slate-500">العودة</a>
    </div>

    <form method="POST" action="{{ route('transactions.store') }}" class="mt-6 space-y-4">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">نوع العملية</label>
                <select name="type" class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
                    <option value="sale">بيع</option>
                    <option value="purchase">شراء</option>
                    <option value="sale_return">مرتجع مبيعات</option>
                    <option value="purchase_return">مرتجع مشتريات</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">نوع البيع/السداد</label>
                <select name="operation_subtype" id="operation_subtype" class="w-full text-xs p-2.5 rounded-xl border border-slate-300" onchange="togglePaid()">
                    <option value="cash">نقدي</option>
                    <option value="credit">آجل</option>
                    <option value="installment">بدفعات (مقبوض جزئي)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">الموقع</label>
                <select name="location_id" class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
                    <option value="">—</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected($loc->is_default)>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">رقم الفاتورة</label>
                <input type="text" name="invoice_number" value="INV-{{ date('Ymd-His') }}" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300 font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">التاريخ</label>
                <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">العميل / المورد</label>
                <select name="contact_id" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
                    <option value="">— اختر —</option>
                    @foreach($contacts as $contact)
                        <option value="{{ $contact->id }}">{{ $contact->name }} ({{ $contact->type === 'customer' ? 'عميل' : 'مورد' }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="border border-slate-200 rounded-2xl overflow-hidden">
            <table class="w-full text-xs" id="lines-table">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-2 text-right">الصنف</th>
                        <th class="p-2">كمية</th>
                        <th class="p-2">سعر</th>
                        <th class="p-2">إجمالي</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="p-2">
                            <select name="lines[0][item_id]" required class="w-full p-2 rounded-lg border">
                                <option value="">—</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->item_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="p-2"><input type="number" step="0.01" name="lines[0][qty]" value="1" class="w-24 p-2 rounded-lg border font-mono" oninput="calcLines()"></td>
                        <td class="p-2"><input type="number" step="0.01" name="lines[0][unit_price]" value="0" class="w-28 p-2 rounded-lg border font-mono" oninput="calcLines()"></td>
                        <td class="p-2 font-mono line-total">0</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" onclick="addLine()" class="m-2 px-3 py-1.5 text-xs rounded-lg bg-slate-100">+ بند</button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold mb-1">العملة</label>
                <select name="currency" id="currency" class="w-full text-xs p-2.5 rounded-xl border" onchange="calcLines()">
                    <option value="USD">USD</option>
                    <option value="SYP">ل.س</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold mb-1">سعر الصرف</label>
                <input type="number" step="1" name="exchange_rate" id="exchange_rate" value="{{ $shop->exchange_rate ?? 14500 }}" class="w-full text-xs p-2.5 rounded-xl border font-mono" oninput="calcLines()">
            </div>
            <div id="paid-wrap" class="hidden">
                <label class="block text-xs font-bold mb-1">المبلغ المقبوض/المدفوع</label>
                <input type="number" step="0.01" name="paid_amount" id="paid_amount" value="0" class="w-full text-xs p-2.5 rounded-xl border font-mono">
            </div>
        </div>

        <textarea name="notes" rows="2" class="w-full text-xs p-2.5 rounded-xl border" placeholder="ملاحظات"></textarea>

        <div class="p-4 bg-slate-50 rounded-xl text-xs flex justify-between">
            <span>الإجمالي</span>
            <span id="display_total" class="font-mono font-bold">0</span>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('transactions.index') }}" class="px-4 py-2 text-xs">إلغاء</a>
            <button class="px-5 py-2 text-xs font-bold text-white bg-brand-800 rounded-xl">حفظ الفاتورة</button>
        </div>
    </form>
</div>
<script>
let lineIdx = 1;
const itemOptions = `@foreach($items as $item)<option value="{{ $item->id }}">{{ $item->item_name }}</option>@endforeach`;
function addLine() {
    const tbody = document.querySelector('#lines-table tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class="p-2"><select name="lines[${lineIdx}][item_id]" required class="w-full p-2 rounded-lg border"><option value="">—</option>${itemOptions}</select></td>
        <td class="p-2"><input type="number" step="0.01" name="lines[${lineIdx}][qty]" value="1" class="w-24 p-2 rounded-lg border font-mono" oninput="calcLines()"></td>
        <td class="p-2"><input type="number" step="0.01" name="lines[${lineIdx}][unit_price]" value="0" class="w-28 p-2 rounded-lg border font-mono" oninput="calcLines()"></td>
        <td class="p-2 font-mono line-total">0</td>
        <td class="p-2"><button type="button" class="text-rose-600" onclick="this.closest('tr').remove();calcLines()">حذف</button></td>`;
    tbody.appendChild(tr);
    lineIdx++;
}
function calcLines() {
    let total = 0;
    document.querySelectorAll('#lines-table tbody tr').forEach(tr => {
        const qty = parseFloat(tr.querySelector('[name*="[qty]"]')?.value || 0);
        const price = parseFloat(tr.querySelector('[name*="[unit_price]"]')?.value || 0);
        const line = qty * price;
        total += line;
        const cell = tr.querySelector('.line-total');
        if (cell) cell.textContent = line.toLocaleString();
    });
    const currency = document.getElementById('currency').value;
    document.getElementById('display_total').innerText = total.toLocaleString() + ' ' + currency;
}
function togglePaid() {
    document.getElementById('paid-wrap').classList.toggle('hidden', document.getElementById('operation_subtype').value !== 'installment');
}
</script>
@endsection
