@extends('layouts.app')
@section('title', 'دليل الحسابات')
@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border">
        <h1 class="text-xl font-bold">دليل الحسابات</h1>
        <form method="POST" action="{{ route('books.accounts.store') }}" class="mt-4 grid grid-cols-2 md:grid-cols-6 gap-2 text-xs">
            @csrf
            <input name="code" required placeholder="رقم الحساب" class="p-2 rounded-xl border font-mono">
            <input name="name" required placeholder="اسم الحساب" class="p-2 rounded-xl border">
            <select name="type" class="p-2 rounded-xl border">
                <option value="asset">أصل</option><option value="liability">التزام</option>
                <option value="equity">ملكية</option><option value="revenue">إيراد</option><option value="expense">مصروف</option>
            </select>
            <input name="classification" placeholder="التصنيف" class="p-2 rounded-xl border">
            <input name="opening_debit" type="number" step="0.01" placeholder="افتتاح مدين" class="p-2 rounded-xl border">
            <button class="px-3 py-2 bg-brand-800 text-white rounded-xl font-bold">حفظ</button>
        </form>
    </div>
    <div class="bg-white rounded-2xl border overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50"><tr>
                <th class="p-3 text-right">رقم</th><th class="p-3 text-right">الاسم</th><th class="p-3">النوع</th>
                <th class="p-3">التصنيف</th><th class="p-3">افتتاح مدين</th><th class="p-3">افتتاح دائن</th><th></th>
            </tr></thead>
            <tbody>
            @foreach($accounts as $a)
                <tr class="border-t">
                    <td class="p-3 font-mono">{{ $a->code }}</td>
                    <td class="p-3 font-bold">{{ $a->name }}</td>
                    <td class="p-3">{{ $a->typeLabel() }}</td>
                    <td class="p-3">{{ $a->classification }}</td>
                    <td class="p-3 font-mono">{{ $a->opening_debit }}</td>
                    <td class="p-3 font-mono">{{ $a->opening_credit }}</td>
                    <td class="p-3">
                        <form method="POST" action="{{ route('books.accounts.destroy', $a) }}">@csrf @method('DELETE')
                            <button class="text-rose-600">حذف</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
