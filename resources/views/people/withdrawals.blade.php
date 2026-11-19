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
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-2.5">التاريخ</th>
                    <th class="p-2.5">الشريك / المصدر</th>
                    <th class="p-2.5">المبلغ</th>
                    <th class="p-2.5">المعادل ($)</th>
                    <th class="p-2.5">الطبيعة</th>
                    <th class="p-2.5">التصنيف / الملاحظات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($rows as $r)
                    <tr class="text-center hover:bg-slate-50">
                        <td class="p-2.5 font-mono">{{ $r->date->format('Y-m-d') }}</td>
                        <td class="p-2.5 font-medium">{{ $r->partner->name ?? 'شريك' }}</td>
                        <td class="p-2.5 font-mono font-bold">{{ number_format((float) $r->amount, $r->currency === 'SYP' ? 0 : 2) }} {{ $r->currency }}</td>
                        <td class="p-2.5 font-mono font-bold text-brand-800">${{ number_format((float) $r->amount_usd, 2) }}</td>
                        <td class="p-2.5">{{ $r->nature }}</td>
                        <td class="p-2.5">{{ $r->classification_status }}</td>
                    </tr>
                @endforeach
                @foreach(($manualWithdrawals ?? collect()) as $cm)
                    <tr class="text-center hover:bg-slate-50 bg-amber-50/40">
                        <td class="p-2.5 font-mono">{{ $cm->date->format('Y-m-d') }}</td>
                        <td class="p-2.5 font-medium">
                            {{ $cm->contact->name ?? $cm->category ?? 'شريك' }}
                            <span class="mr-1 px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-bold">سند صندوق مباشر</span>
                        </td>
                        <td class="p-2.5 font-mono font-bold">{{ number_format((float) $cm->amount, $cm->currency === 'SYP' ? 0 : 2) }} {{ $cm->currency }}</td>
                        <td class="p-2.5 font-mono font-bold text-brand-800">${{ number_format((float) $cm->amount_usd, 2) }}</td>
                        <td class="p-2.5">مسحوب شريك (الصندوق)</td>
                        <td class="p-2.5 text-slate-600">{{ $cm->notes ?: '—' }}</td>
                    </tr>
                @endforeach
                @if($rows->isEmpty() && ($manualWithdrawals ?? collect())->isEmpty())
                    <tr>
                        <td colspan="6" class="p-6 text-center text-slate-400">لا توجد مسحوبات شركاء مسجلة بعد.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
