@extends('layouts.app')
@section('title', 'حركة الأصناف')
@section('content')
<div class="bg-white rounded-2xl p-6 border space-y-4">
    <h1 class="text-xl font-bold">حركة الأصناف</h1>
    <form class="flex gap-2 text-xs">
        <select name="item_id" class="p-2 rounded-xl border">
            <option value="">كل الأصناف</option>
            @foreach($items as $i)<option value="{{ $i->id }}" @selected($itemId==$i->id)>{{ $i->item_name }}</option>@endforeach
        </select>
        <button class="px-3 py-2 bg-brand-900 text-white rounded-xl">عرض</button>
    </form>
    <table class="w-full text-xs">
        <thead class="bg-slate-50"><tr><th class="p-2">التاريخ</th><th class="p-2">النوع</th><th class="p-2">المستند</th><th class="p-2">الصنف</th><th class="p-2">كمية</th><th class="p-2">سعر</th><th class="p-2">إجمالي</th></tr></thead>
        @forelse($rows as $r)
            <tr class="border-t text-center">
                <td class="p-2 font-mono">{{ $r['date'] }}</td>
                <td class="p-2">{{ $r['type'] }}</td>
                <td class="p-2">{{ $r['document'] }}</td>
                <td class="p-2">{{ $r['item'] }}</td>
                <td class="p-2 font-mono">{{ $r['qty'] }}</td>
                <td class="p-2 font-mono">{{ $r['unit_price'] }}</td>
                <td class="p-2 font-mono">{{ $r['total'] }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="p-6 text-center text-slate-400">لا حركة</td></tr>
        @endforelse
    </table>
</div>
@endsection
