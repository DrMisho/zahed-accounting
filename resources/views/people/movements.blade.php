@extends('layouts.app')
@section('title', 'حركة الموظفين')
@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border text-xs">
        <h1 class="text-xl font-bold">حركة الموظفين</h1>
        <form method="POST" action="{{ route('employee-movements.store') }}" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-2">
            @csrf
            <select name="employee_id" required class="p-2 rounded-xl border">
                <option value="">الموظف</option>
                @foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach
            </select>
            <select name="type" class="p-2 rounded-xl border">
                <option value="advance">سلفة عادية</option>
                <option value="salary_payment">دفعة راتب</option>
                <option value="settlement">تسوية</option>
            </select>
            <input type="date" name="date" value="{{ date('Y-m-d') }}" class="p-2 rounded-xl border">
            <input name="month" placeholder="الشهر" class="p-2 rounded-xl border">
            <input name="amount" type="number" step="0.01" required placeholder="المبلغ" class="p-2 rounded-xl border">
            <select name="currency" class="p-2 rounded-xl border"><option>USD</option><option>SYP</option></select>
            <input name="exchange_rate" type="number" value="{{ $shop->exchange_rate }}" class="p-2 rounded-xl border">
            <input name="voucher_number" placeholder="رقم السند" class="p-2 rounded-xl border">
            <button class="px-3 py-2 bg-brand-800 text-white rounded-xl font-bold">حفظ</button>
        </form>
    </div>
    <div class="bg-white rounded-2xl border overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50"><tr><th class="p-2">التاريخ</th><th class="p-2">الموظف</th><th class="p-2">النوع</th><th class="p-2">المبلغ</th><th class="p-2">الأثر</th></tr></thead>
            @foreach($rows as $r)
                <tr class="border-t text-center">
                    <td class="p-2 font-mono">{{ $r->date->format('Y-m-d') }}</td>
                    <td class="p-2">{{ $r->employee->name }}</td>
                    <td class="p-2">{{ $r->typeLabel() }}</td>
                    <td class="p-2 font-mono">{{ $r->amount }} {{ $r->currency }}</td>
                    <td class="p-2">{{ $r->type === 'advance' ? 'ذمة مستقلة عن الراتب' : ($r->type === 'salary_payment' ? 'يخصم من الاستحقاق' : 'تسوية') }}</td>
                </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection
