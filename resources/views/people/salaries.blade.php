@extends('layouts.app')
@section('title', 'رواتب الموظفين')
@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border text-xs">
        <h1 class="text-xl font-bold text-base">رواتب الموظفين</h1>
        <form method="POST" action="{{ route('salaries.store') }}" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-2">
            @csrf
            <select name="employee_id" required class="p-2 rounded-xl border">
                <option value="">الموظف</option>
                @foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach
            </select>
            <input type="date" name="date" value="{{ date('Y-m-d') }}" class="p-2 rounded-xl border">
            <input name="month" required placeholder="الشهر 2026-11" class="p-2 rounded-xl border">
            <input name="voucher_number" placeholder="رقم السند" class="p-2 rounded-xl border">
            <input name="basic" type="number" step="0.01" required placeholder="الأساسي" class="p-2 rounded-xl border">
            <input name="extra" type="number" step="0.01" value="0" placeholder="إضافي" class="p-2 rounded-xl border">
            <input name="deduction" type="number" step="0.01" value="0" placeholder="خصم" class="p-2 rounded-xl border">
            <select name="currency" class="p-2 rounded-xl border"><option>USD</option><option>SYP</option></select>
            <input name="exchange_rate" type="number" value="{{ $shop->exchange_rate }}" class="p-2 rounded-xl border">
            <input name="payment_method" value="نقدي" class="p-2 rounded-xl border">
            <button class="px-3 py-2 bg-brand-800 text-white rounded-xl font-bold">حفظ الراتب</button>
        </form>
        <p class="text-slate-500 mt-2">السلفة العادية لا تُخصم تلقائياً من الراتب. صافي الراتب = أساسي + إضافي − خصم.</p>
    </div>
    <div class="bg-white rounded-2xl border overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50"><tr>
                <th class="p-2">التاريخ</th><th class="p-2">الموظف</th><th class="p-2">الشهر</th><th class="p-2">صافي</th><th class="p-2">$</th>
            </tr></thead>
            @foreach($rows as $r)
                <tr class="border-t text-center">
                    <td class="p-2 font-mono">{{ $r->date->format('Y-m-d') }}</td>
                    <td class="p-2">{{ $r->employee->name }}</td>
                    <td class="p-2">{{ $r->month }}</td>
                    <td class="p-2 font-mono">{{ $r->net }} {{ $r->currency }}</td>
                    <td class="p-2 font-mono">${{ $r->amount_usd }}</td>
                </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection
