@extends('layouts.app')
@section('title', $type === 'receipt' ? 'سندات القبض' : 'سندات الصرف')
@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border text-xs">
        <h1 class="text-xl font-bold">{{ $type === 'receipt' ? 'سندات القبض' : 'سندات الصرف' }}</h1>
        <p class="text-slate-500 mt-1">افتراضياً توثيق فقط. فعّل «يؤثر على الذمة والصندوق» إذا كان هذا هو التسجيل الوحيد للأثر.</p>
        <form method="POST" action="{{ route('vouchers.store') }}" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-2">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <input name="number" required value="{{ $type === 'receipt' ? 'RCP' : 'PAY' }}-{{ date('YmdHis') }}" class="p-2 rounded-xl border">
            <input type="date" name="date" value="{{ date('Y-m-d') }}" class="p-2 rounded-xl border">
            <select name="contact_id" class="p-2 rounded-xl border">
                <option value="">{{ $type === 'receipt' ? 'العميل' : 'المورد / المستفيد' }}</option>
                @foreach($contacts as $c)
                    @if(($type==='receipt' && $c->type==='customer') || ($type==='payment' && $c->type==='supplier') || true)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endif
                @endforeach
            </select>
            <input name="beneficiary" placeholder="المستفيد" class="p-2 rounded-xl border">
            <input name="amount" type="number" step="0.01" required placeholder="المبلغ" class="p-2 rounded-xl border">
            <select name="currency" class="p-2 rounded-xl border"><option>USD</option><option>SYP</option></select>
            <input name="exchange_rate" type="number" value="{{ $shop->exchange_rate }}" class="p-2 rounded-xl border">
            <input name="payment_method" value="نقدي" class="p-2 rounded-xl border">
            <input name="original_ref" placeholder="مرجع الفاتورة الأصلية" class="p-2 rounded-xl border">
            <input name="description" placeholder="البيان" class="p-2 rounded-xl border col-span-2">
            <label class="flex items-center gap-2 col-span-2">
                <input type="checkbox" name="affects_balance" value="1">
                يؤثر على رصيد الذمة والصندوق (لا تستخدمه إذا سُجّل الأثر في الفاتورة)
            </label>
            <button class="px-3 py-2 bg-brand-800 text-white rounded-xl font-bold">حفظ السند</button>
        </form>
    </div>
    <div class="bg-white rounded-2xl border overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50"><tr>
                <th class="p-2">رقم</th><th class="p-2">التاريخ</th><th class="p-2">الجهة</th><th class="p-2">المبلغ</th><th class="p-2">الأثر</th><th class="p-2">إجراءات</th>
            </tr></thead>
            @foreach($rows as $r)
                <tr class="border-t text-center">
                    <td class="p-2 font-mono">{{ $r->number }}</td>
                    <td class="p-2 font-mono">{{ $r->date->format('Y-m-d') }}</td>
                    <td class="p-2">{{ $r->contact->name ?? $r->beneficiary }}</td>
                    <td class="p-2 font-mono">{{ $r->amount }} {{ $r->currency }}</td>
                    <td class="p-2">{{ $r->affects_balance ? 'يؤثر' : 'توثيق فقط' }}</td>
                    <td class="p-2 whitespace-nowrap">
                        <button type="button" class="text-brand-800 font-bold" onclick='openVoucherModal(@json([
                            "id" => $r->id,
                            "number" => $r->number,
                            "date" => $r->date->format("Y-m-d"),
                            "contact_id" => $r->contact_id,
                            "beneficiary" => $r->beneficiary,
                            "amount" => $r->amount,
                            "currency" => $r->currency,
                            "exchange_rate" => $r->exchange_rate,
                            "payment_method" => $r->payment_method,
                            "original_ref" => $r->original_ref,
                            "description" => $r->description,
                            "affects_balance" => (bool) $r->affects_balance,
                        ]))'>تعديل</button>
                        ·
                        <a class="text-brand-800" href="{{ route('vouchers.show', $r) }}">عرض</a>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
</div>

<div id="modal-voucher" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border">
        <div class="flex justify-between items-center pb-3 border-b">
            <h2 class="text-base font-bold">تعديل السند</h2>
            <button type="button" onclick="document.getElementById('modal-voucher').classList.add('hidden')" class="font-bold text-slate-400">✕</button>
        </div>
        <form id="voucher-edit-form" method="POST" class="mt-4 grid grid-cols-2 gap-2 text-xs">
            @csrf
            @method('PUT')
            <input type="hidden" name="type" value="{{ $type }}">
            <input name="number" id="v_number" required class="p-2 rounded-xl border">
            <input type="date" name="date" id="v_date" class="p-2 rounded-xl border">
            <select name="contact_id" id="v_contact_id" class="p-2 rounded-xl border">
                <option value="">{{ $type === 'receipt' ? 'العميل' : 'المورد / المستفيد' }}</option>
                @foreach($contacts as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
            <input name="beneficiary" id="v_beneficiary" placeholder="المستفيد" class="p-2 rounded-xl border">
            <input name="amount" id="v_amount" type="number" step="0.01" required placeholder="المبلغ" class="p-2 rounded-xl border">
            <select name="currency" id="v_currency" class="p-2 rounded-xl border"><option>USD</option><option>SYP</option></select>
            <input name="exchange_rate" id="v_exchange_rate" type="number" class="p-2 rounded-xl border">
            <input name="payment_method" id="v_payment_method" class="p-2 rounded-xl border">
            <input name="original_ref" id="v_original_ref" placeholder="مرجع الفاتورة الأصلية" class="p-2 rounded-xl border col-span-2">
            <input name="description" id="v_description" placeholder="البيان" class="p-2 rounded-xl border col-span-2">
            <label class="flex items-center gap-2 col-span-2">
                <input type="checkbox" name="affects_balance" id="v_affects_balance" value="1">
                يؤثر على رصيد الذمة والصندوق
            </label>
            <div class="col-span-2 flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-voucher').classList.add('hidden')" class="px-4 py-2">إلغاء</button>
                <button class="px-4 py-2 bg-brand-800 text-white rounded-xl font-bold">حفظ التعديل</button>
            </div>
        </form>
    </div>
</div>
<script>
    function openVoucherModal(row) {
        const form = document.getElementById('voucher-edit-form');
        form.action = '{{ url('vouchers') }}/' + row.id;
        document.getElementById('v_number').value = row.number || '';
        document.getElementById('v_date').value = row.date || '';
        document.getElementById('v_contact_id').value = row.contact_id || '';
        document.getElementById('v_beneficiary').value = row.beneficiary || '';
        document.getElementById('v_amount').value = row.amount || '';
        document.getElementById('v_currency').value = row.currency || 'USD';
        document.getElementById('v_exchange_rate').value = row.exchange_rate || '';
        document.getElementById('v_payment_method').value = row.payment_method || '';
        document.getElementById('v_original_ref').value = row.original_ref || '';
        document.getElementById('v_description').value = row.description || '';
        document.getElementById('v_affects_balance').checked = !!row.affects_balance;
        document.getElementById('modal-voucher').classList.remove('hidden');
    }
</script>
@endsection
