@extends('layouts.app')
@section('title', 'الموظفون')
@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border">
        <h1 class="text-xl font-bold">الموظفون</h1>
        <form method="POST" action="{{ route('employees.store') }}" class="mt-4 grid grid-cols-3 gap-2 text-xs">
            @csrf
            <input name="name" required placeholder="الاسم" class="p-2 rounded-xl border">
            <input name="phone" placeholder="الهاتف" class="p-2 rounded-xl border">
            <button class="px-3 py-2 bg-brand-800 text-white rounded-xl font-bold">حفظ</button>
        </form>
    </div>
    <div class="bg-white rounded-2xl border">
        <table class="w-full text-xs">
            <thead class="bg-slate-50"><tr><th class="p-3 text-right">الاسم</th><th class="p-3">الهاتف</th><th class="p-3">كشف</th></tr></thead>
            <tbody>
            @foreach($employees as $e)
                <tr class="border-t">
                    <td class="p-3 font-bold">{{ $e->name }}</td>
                    <td class="p-3">{{ $e->phone }}</td>
                    <td class="p-3"><a class="text-brand-800" href="{{ route('employees.statement', $e) }}">كشف الحساب</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
