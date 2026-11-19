@extends('layouts.app')
@section('title', 'مسحوبات الشركاء')
@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border text-xs">
        <h1 class="text-xl font-bold">مسحوبات الشركاء</h1>
        <form method="POST" action="{{ route('partners.store') }}" class="mt-3 flex gap-2">
            @csrf
            <input name="name" required placeholder="اسم شريك جديد" class="p-2 rounded-xl border flex-1">
            <button class="px-3 py-2 bg-brand-900 text-white rounded-xl">إضافة شريك</button>
        </form>
        <form method="POST" action="{{ route('withdrawals.store') }}" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-2">
            @csrf
            <select name="partner_id" required class="p-2 rounded-xl border">
                <option value="">الشريك</option>
                @foreach($partners as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
            </select>
            <input type="date" name="date" value="{{ date('Y-m-d') }}" class="p-2 rounded-xl border">
            <input name="amount" type="number" step="0.01" required placeholder="المبلغ" class="p-2 rounded-xl border">
            <select name="currency" class="p-2 rounded-xl border"><option>USD</option><option>SYP</option></select>
            <input name="exchange_rate" type="number" value="{{ $shop->exchange_rate }}" class="p-2 rounded-xl border">
            <select name="nature" class="p-2 rounded-xl border">
                <option>مسحوب شخصي نهائي</option>
                <option>سلفة شريك</option>
            </select>
            <select name="classification_status" class="p-2 rounded-xl border">
                <option>مصنف</option>
                <option>غير مصنف</option>
            </select>
            <input name="description" placeholder="البيان" class="p-2 rounded-xl border">
            <button class="px-3 py-2 bg-brand-800 text-white rounded-xl font-bold">حفظ المسحوب</button>
        </form>
    </div>
    <div class="bg-white rounded-2xl border overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50"><tr><th class="p-2">التاريخ</th><th class="p-2">الشريك</th><th class="p-2">المبلغ</th><th class="p-2">الطبيعة</th><th class="p-2">التصنيف</th></tr></thead>
            @foreach($rows as $r)
                <tr class="border-t text-center">
                    <td class="p-2 font-mono">{{ $r->date->format('Y-m-d') }}</td>
                    <td class="p-2">{{ $r->partner->name }}</td>
                    <td class="p-2 font-mono">{{ $r->amount }} {{ $r->currency }}</td>
                    <td class="p-2">{{ $r->nature }}</td>
                    <td class="p-2">{{ $r->classification_status }}</td>
                </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection
