@extends('layouts.app')

@section('title', 'العملاء والموردين')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">إدارة العملاء والموردين وكشوفات الحساب (SRS 4.ج)</h1>
            <p class="text-xs text-slate-500 mt-1">حساب أرصدة الذمم بدقة المعادلات: مبيعات آجل - مقبوضات للعملاء، مشتريات آجل - مدفوعات للموردين</p>
        </div>
        <button onclick="document.getElementById('modal-contact').classList.remove('hidden')" class="px-4 py-2.5 text-xs font-bold rounded-xl bg-brand-800 hover:bg-brand-900 text-white shadow-xs transition-colors self-start sm:self-center">
            + إضافة جهة جديدة
        </button>
    </div>

    <!-- Type Switcher -->
    <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl max-w-xs text-xs font-bold">
        <a href="{{ route('contacts.index', ['type' => 'customer']) }}" class="flex-1 text-center py-2 rounded-lg {{ $type === 'customer' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
            العملاء (Customers)
        </a>
        <a href="{{ route('contacts.index', ['type' => 'supplier']) }}" class="flex-1 text-center py-2 rounded-lg {{ $type === 'supplier' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
            الموردين (Suppliers)
        </a>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                        <th class="py-3 px-4">الاسم</th>
                        <th class="py-3 px-4">رقم الهاتف</th>
                        <th class="py-3 px-4">ملاحظات</th>
                        <th class="py-3 px-4">الكود</th>
                        <th class="py-3 px-4 text-left">الرصيد $</th>
                        <th class="py-3 px-4 text-left">الرصيد ل.س</th>
                        <th class="py-3 px-4 text-center">كشف الحساب</th>
                        <th class="py-3 px-4 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($contactList as $entry)
                        @php
                            $c = $entry['contact'];
                            $bal = $entry['balance_usd'];
                            $balSyp = $entry['balance_syp'] ?? 0;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="py-3.5 px-4 font-bold text-slate-900">{{ $c->name }}</td>
                            <td class="py-3.5 px-4 font-mono text-slate-600">{{ $c->phone ?? '—' }}</td>
                            <td class="py-3.5 px-4 text-slate-500">{{ $c->notes ?? '—' }}</td>
                            <td class="py-3.5 px-4 font-mono">{{ $c->code ?? '—' }}</td>
                            <td class="py-3.5 px-4 text-left font-mono font-bold text-sm">${{ number_format($bal, 2) }}</td>
                            <td class="py-3.5 px-4 text-left font-mono">{{ number_format($balSyp) }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <a href="{{ route('contacts.statement', $c) }}" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 rounded-lg text-slate-800 font-semibold text-xs">
                                    كشف الحساب 📄
                                </a>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <form method="POST" action="{{ route('contacts.destroy', $c) }}" onsubmit="return confirm('هل تريد حذف هذه الجهة؟')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-rose-500 hover:text-rose-700">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">لا توجد جهات مسجلة في هذا القسم.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add Contact -->
<div id="modal-contact" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex justify-between items-center pb-3 border-b">
            <h2 class="text-base font-bold text-slate-900">إضافة جهة اتصال جديدة</h2>
            <button onclick="document.getElementById('modal-contact').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 font-bold">✕</button>
        </div>
        <form method="POST" action="{{ route('contacts.store') }}" class="mt-4 space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">النوع</label>
                <select name="type" class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
                    <option value="customer" {{ $type === 'customer' ? 'selected' : '' }}>عميل (Customer)</option>
                    <option value="supplier" {{ $type === 'supplier' ? 'selected' : '' }}>مورد (Supplier)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">الاسم الكامل</label>
                <input type="text" name="name" required placeholder="مثال: المهندس مازن الرفاعي" class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold mb-1">كود</label>
                    <input type="text" name="code" class="w-full text-xs p-2.5 rounded-xl border">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1">هاتف</label>
                    <input type="text" name="phone" class="w-full text-xs p-2.5 rounded-xl border font-mono">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold mb-1">افتتاحي $</label>
                    <input type="number" step="0.01" name="opening_usd" value="0" class="w-full text-xs p-2.5 rounded-xl border font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1">افتتاحي ل.س</label>
                    <input type="number" step="0.01" name="opening_syp" value="0" class="w-full text-xs p-2.5 rounded-xl border font-mono">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ملاحظات</label>
                <textarea name="notes" rows="2" class="w-full text-xs p-2.5 rounded-xl border border-slate-300"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-contact').classList.add('hidden')" class="px-4 py-2 text-xs text-slate-600">إلغاء</button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-brand-800 rounded-xl">حفظ</button>
            </div>
        </form>
    </div>
</div>
@endsection
