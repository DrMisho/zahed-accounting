@extends('layouts.app')
@section('title', 'كشف الموظف')
@section('content')
<div class="max-w-3xl mx-auto bg-white rounded-2xl p-6 border space-y-4">
    <h1 class="text-xl font-bold">كشف حساب الموظف — {{ $employee->name }}</h1>
    <div class="grid grid-cols-3 gap-3 text-xs">
        <div class="p-3 bg-slate-50 rounded-xl">رواتب مستحقة $ {{ number_format($due,2) }}</div>
        <div class="p-3 bg-slate-50 rounded-xl">دفعات راتب $ {{ number_format($salaryPaid,2) }}</div>
        <div class="p-3 bg-gold-50 rounded-xl">سلف مستقلة $ {{ number_format($advances,2) }}</div>
    </div>
    <p class="text-xs text-slate-500">الرصيد المستحق للموظف ${{ number_format($due - $salaryPaid, 2) }}. السلفة العادية لا تُخصم تلقائياً.</p>
    <a href="{{ route('employees.index') }}" class="text-xs text-slate-500">عودة</a>
</div>
@endsection
