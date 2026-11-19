@extends('layouts.app')
@section('title', 'مراكز التكلفة')
@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border">
        <h1 class="text-xl font-bold">مراكز التكلفة</h1>
        <form method="POST" action="{{ route('books.cost-centers.store') }}" class="mt-4 grid grid-cols-2 md:grid-cols-5 gap-2 text-xs">
            @csrf
            <input name="code" required placeholder="الكود" class="p-2 rounded-xl border">
            <input name="name" required placeholder="الاسم" class="p-2 rounded-xl border">
            <input name="type" placeholder="النوع" class="p-2 rounded-xl border">
            <input name="cost_amount" type="number" step="0.01" placeholder="تكلفة" class="p-2 rounded-xl border">
            <button class="px-3 py-2 bg-brand-800 text-white rounded-xl font-bold">حفظ</button>
        </form>
    </div>
    <div class="bg-white rounded-2xl border overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50"><tr><th class="p-3 text-right">كود</th><th class="p-3 text-right">الاسم</th><th class="p-3">النوع</th><th class="p-3">التكلفة</th><th class="p-3">ملاحظات</th></tr></thead>
            <tbody>
            @foreach($centers as $c)
                <tr class="border-t"><td class="p-3 font-mono">{{ $c->code }}</td><td class="p-3">{{ $c->name }}</td><td class="p-3">{{ $c->type }}</td><td class="p-3 font-mono">{{ $c->cost_amount }}</td><td class="p-3">{{ $c->notes }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
