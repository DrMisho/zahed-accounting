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
                <label for="tx_type" class="block text-xs font-bold text-slate-700 mb-1">نوع العملية</label>
                <select id="tx_type" name="type" class="w-full text-xs p-2.5 rounded-xl border border-slate-300" onchange="updateDiscountLabels(); calcLines();">
                    <option value="sale">بيع</option>
                    <option value="purchase">شراء</option>
                    <option value="sale_return">مرتجع مبيعات</option>
                    <option value="purchase_return">مرتجع مشتريات</option>
                </select>
            </div>
            <div>
                <label for="operation_subtype" class="block text-xs font-bold text-slate-700 mb-1">نوع البيع/السداد</label>
                <select name="operation_subtype" id="operation_subtype" class="w-full text-xs p-2.5 rounded-xl border border-slate-300" onchange="togglePaid()">
                    <option value="cash">نقدي</option>
                    <option value="credit">آجل</option>
                    <option value="installment">بدفعات (مقبوض جزئي)</option>
                </select>
            </div>
            <div>
                <label for="location_id" class="block text-xs font-bold text-slate-700 mb-1">الموقع</label>
                <select id="location_id" name="location_id" class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
                    <option value="">—</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected($loc->is_default)>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="invoice_number" class="block text-xs font-bold text-slate-700 mb-1">رقم الفاتورة</label>
                <input id="invoice_number" type="text" name="invoice_number" value="INV-{{ date('Ymd-His') }}" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300 font-mono">
            </div>
            <div>
                <label for="tx_date" class="block text-xs font-bold text-slate-700 mb-1">التاريخ</label>
                <input id="tx_date" type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
            </div>
            <div>
                <label for="contact_id" class="block text-xs font-bold text-slate-700 mb-1">العميل / المورد</label>
                <select id="contact_id" name="contact_id" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
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
            <button type="button" onclick="addLine()" class="m-2 px-3 py-1.5 text-xs rounded-lg bg-slate-100 hover:bg-slate-200">+ بند</button>
        </div>

        <!-- Discount Row (حسم ممنوح للمبيعات / حسم مكتسب للمشتريات) -->
        <div class="p-4 rounded-2xl bg-gold-50/60 border border-gold-200 grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <div>
                <label for="discount_input" id="discount_field_label" class="block text-xs font-bold text-brand-900 mb-1">حسم ممنوح (قيمة بالعملة)</label>
                <input type="number" step="0.01" min="0" name="discount" id="discount_input" value="{{ old('discount', 0) }}" class="w-full text-xs p-2.5 rounded-xl border border-gold-300 bg-white font-mono font-bold text-left" oninput="syncDiscountPercent(); calcLines();">
            </div>
            <div>
                <label for="discount_percent" class="block text-xs font-bold text-slate-700 mb-1">أو نسبة الحسم (%)</label>
                <input type="number" step="0.1" min="0" max="100" id="discount_percent" value="0" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 bg-white font-mono text-left" oninput="applyDiscountPercent()">
            </div>
            <div class="text-[11px] text-slate-600 leading-relaxed" id="discount_helper_text">
                يُخصم الحسم الممنوح من إجمالي فاتورة المبيعات وصافي الذمة/المقبوض.
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="currency" class="block text-xs font-bold mb-1">العملة</label>
                <select name="currency" id="currency" class="w-full text-xs p-2.5 rounded-xl border" onchange="calcLines()">
                    <option value="USD">USD</option>
                    <option value="SYP">ل.س</option>
                </select>
            </div>
            <div>
                <label for="exchange_rate" class="block text-xs font-bold mb-1">سعر الصرف</label>
                <input type="number" step="1" name="exchange_rate" id="exchange_rate" value="{{ $shop->exchange_rate ?? 14500 }}" class="w-full text-xs p-2.5 rounded-xl border font-mono" oninput="calcLines()">
            </div>
            <div id="paid-wrap" class="hidden">
                <label for="paid_amount" class="block text-xs font-bold mb-1">المبلغ المقبوض/المدفوع</label>
                <input type="number" step="0.01" name="paid_amount" id="paid_amount" value="0" class="w-full text-xs p-2.5 rounded-xl border font-mono">
            </div>
        </div>

        <textarea name="notes" rows="2" class="w-full text-xs p-2.5 rounded-xl border" placeholder="ملاحظات"></textarea>

        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-2">
            <div class="flex justify-between text-slate-600">
                <span>المجموع الفرعي قبل الحسم</span>
                <span id="display_subtotal" class="font-mono font-semibold">0 USD</span>
            </div>
            <div class="flex justify-between text-amber-800">
                <span id="display_discount_label">(-) حسم ممنوح</span>
                <span id="display_discount" class="font-mono font-semibold">-0 USD</span>
            </div>
            <div class="flex justify-between pt-2 border-t border-slate-200 text-sm text-brand-950 font-bold">
                <span>صافي الفاتورة النهائي</span>
                <span id="display_total" class="font-mono">0 USD</span>
            </div>
            <div class="flex justify-between text-[11px] text-slate-500">
                <span>المعادل التقديري بالدولار (USD)</span>
                <span id="display_total_usd" class="font-mono font-bold text-brand-800">$0.00</span>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('transactions.index') }}" class="px-4 py-2 text-xs">إلغاء</a>
            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-brand-800 hover:bg-brand-900 rounded-xl">حفظ الفاتورة</button>
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

function getSubtotal() {
    let subtotal = 0;
    document.querySelectorAll('#lines-table tbody tr').forEach(tr => {
        const qty = parseFloat(tr.querySelector('[name*="[qty]"]')?.value || 0);
        const price = parseFloat(tr.querySelector('[name*="[unit_price]"]')?.value || 0);
        const line = qty * price;
        subtotal += line;
        const cell = tr.querySelector('.line-total');
        if (cell) cell.textContent = line.toLocaleString();
    });
    return subtotal;
}

function updateDiscountLabels() {
    const type = document.getElementById('tx_type').value;
    const isPurchase = type === 'purchase' || type === 'purchase_return';
    const label = isPurchase ? 'حسم مكتسب' : 'حسم ممنوح';
    document.getElementById('discount_field_label').textContent = label + ' (قيمة بالعملة)';
    document.getElementById('display_discount_label').textContent = '(-) ' + label;
    document.getElementById('discount_helper_text').textContent = isPurchase
        ? 'يُخصم الحسم المكتسب من إجمالي فاتورة الشراء ويُخفض تكلفة المشتريات ومتوسط التكلفة (WAC).'
        : 'يُخصم الحسم الممنوح من إجمالي فاتورة المبيعات وصافي الذمة/المقبوض.';
}

function applyDiscountPercent() {
    const subtotal = getSubtotal();
    const pct = Math.max(0, Math.min(100, parseFloat(document.getElementById('discount_percent').value || 0)));
    const amount = Math.round((subtotal * pct / 100) * 100) / 100;
    document.getElementById('discount_input').value = amount;
    calcLines();
}

function syncDiscountPercent() {
    const subtotal = getSubtotal();
    const disc = Math.max(0, parseFloat(document.getElementById('discount_input').value || 0));
    const pct = subtotal > 0 ? Math.min(100, Math.round((disc / subtotal) * 1000) / 10) : 0;
    document.getElementById('discount_percent').value = pct;
}

function calcLines() {
    const subtotal = getSubtotal();
    let discount = Math.max(0, parseFloat(document.getElementById('discount_input').value || 0));
    if (discount > subtotal && subtotal > 0) {
        discount = subtotal;
    }
    const netTotal = Math.max(0, subtotal - discount);
    const currency = document.getElementById('currency').value;
    const rate = parseFloat(document.getElementById('exchange_rate').value || 14500);
    const usd = currency === 'USD' ? netTotal : (rate > 0 ? netTotal / rate : 0);

    document.getElementById('display_subtotal').innerText = subtotal.toLocaleString() + ' ' + currency;
    document.getElementById('display_discount').innerText = '-' + discount.toLocaleString() + ' ' + currency;
    document.getElementById('display_total').innerText = netTotal.toLocaleString() + ' ' + currency;
    document.getElementById('display_total_usd').innerText = '$' + usd.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function togglePaid() {
    document.getElementById('paid-wrap').classList.toggle('hidden', document.getElementById('operation_subtype').value !== 'installment');
}

updateDiscountLabels();
calcLines();
</script>
@endsection
