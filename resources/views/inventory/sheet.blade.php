@extends('layouts.app')
@section('title', 'كشف المخزون')
@section('content')
<div class="bg-white rounded-2xl p-6 border">
    <h1 class="text-xl font-bold mb-4">كشف المخزون</h1>
    <table class="w-full text-xs">
        <thead class="bg-slate-50"><tr>
            <th class="p-2">الصنف</th><th class="p-2">افتتاحي</th><th class="p-2">وارد</th><th class="p-2">مباع</th>
            <th class="p-2">مرتجع بيع</th><th class="p-2">مرتجع شراء</th><th class="p-2">الرصيد</th><th class="p-2">WAC $</th>
        </tr></thead>
        @foreach($rows as $r)
            <tr class="border-t text-center">
                <td class="p-2">{{ $r['item']->item_name }}</td>
                <td class="p-2 font-mono">{{ $r['opening_qty'] }}</td>
                <td class="p-2 font-mono">{{ $r['purchased_qty'] }}</td>
                <td class="p-2 font-mono">{{ $r['sold_qty'] }}</td>
                <td class="p-2 font-mono">{{ $r['sales_return_qty'] }}</td>
                <td class="p-2 font-mono">{{ $r['purchase_return_qty'] }}</td>
                <td class="p-2 font-mono font-bold">{{ $r['current_stock'] }}</td>
                <td class="p-2 font-mono">${{ $r['wac_usd'] }}</td>
            </tr>
        @endforeach
    </table>
</div>
@endsection
