@extends('layouts.app')
@section('title', 'مناقلات المخزون')
@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border text-xs">
        <h1 class="text-xl font-bold">مناقلات المخزون</h1>
        <p class="text-slate-500 mt-1">المناقلة حركة داخلية: لا بيع ولا شراء ولا أثر على الصندوق أو الذمم.</p>
        <form method="POST" action="{{ route('inventory.transfers.store') }}" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-2">
            @csrf
            <input type="date" name="date" value="{{ date('Y-m-d') }}" class="p-2 rounded-xl border">
            <input name="number" required value="TR-{{ date('YmdHis') }}" class="p-2 rounded-xl border">
            <select name="item_id" required class="p-2 rounded-xl border">
                @foreach($items as $i)<option value="{{ $i->id }}">{{ $i->item_name }}</option>@endforeach
            </select>
            <input name="qty" type="number" step="0.01" required placeholder="الكمية" class="p-2 rounded-xl border">
            <select name="from_location_id" required class="p-2 rounded-xl border">
                @foreach($locations as $l)<option value="{{ $l->id }}">من: {{ $l->name }}</option>@endforeach
            </select>
            <select name="to_location_id" required class="p-2 rounded-xl border">
                @foreach($locations as $l)<option value="{{ $l->id }}">إلى: {{ $l->name }}</option>@endforeach
            </select>
            <input name="notes" placeholder="البيان" class="p-2 rounded-xl border">
            <button class="px-3 py-2 bg-brand-800 text-white rounded-xl font-bold">حفظ المناقلة</button>
        </form>
    </div>
    <div class="bg-white rounded-2xl border overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50"><tr><th class="p-2">التاريخ</th><th class="p-2">رقم</th><th class="p-2">الصنف</th><th class="p-2">كمية</th><th class="p-2">من</th><th class="p-2">إلى</th></tr></thead>
            @foreach($transfers as $t)
                <tr class="border-t text-center">
                    <td class="p-2 font-mono">{{ $t->date->format('Y-m-d') }}</td>
                    <td class="p-2">{{ $t->number }}</td>
                    <td class="p-2">{{ $t->item->item_name }}</td>
                    <td class="p-2 font-mono">{{ $t->qty }}</td>
                    <td class="p-2">{{ $t->fromLocation->name }}</td>
                    <td class="p-2">{{ $t->toLocation->name }}</td>
                </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection
