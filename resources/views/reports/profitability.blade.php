@extends('layouts.app')
@section('title', 'تحليل التكلفة والربحية')
@section('content')
<div class="bg-white rounded-2xl p-6 border">
    <h1 class="text-xl font-bold mb-4">تحليل التكلفة والربحية</h1>
    <table class="w-full text-xs">
        <thead class="bg-slate-50"><tr>
            <th class="p-2">الصنف</th><th class="p-2">افتتاح</th><th class="p-2">مشتريات</th><th class="p-2">أجور شحن $</th><th class="p-2">حسم مكتسب $</th><th class="p-2">WAC</th>
            <th class="p-2">صافي مبيعات $</th><th class="p-2">COGS</th><th class="p-2">مجمل ربح</th><th class="p-2">قيمة المخزون</th>
        </tr></thead>
        @foreach($rows as $r)
            @php $gp = $r['net_sales_usd'] - $r['cogs_usd']; @endphp
            <tr class="border-t text-center">
                <td class="p-2">{{ $r['item']->item_name }}</td>
                <td class="p-2 font-mono">{{ $r['opening_qty'] }}</td>
                <td class="p-2 font-mono">{{ $r['purchased_qty'] }}</td>
                <td class="p-2 font-mono text-cyan-800">${{ number_format($r['shipping_cost_usd'] ?? 0, 2) }}</td>
                <td class="p-2 font-mono text-amber-700">${{ number_format($r['purchase_discount_usd'] ?? 0, 2) }}</td>
                <td class="p-2 font-mono">${{ $r['wac_usd'] }}</td>
                <td class="p-2 font-mono">${{ $r['net_sales_usd'] }}</td>
                <td class="p-2 font-mono">${{ number_format($r['cogs_usd'],2) }}</td>
                <td class="p-2 font-mono font-bold">${{ number_format($gp,2) }}</td>
                <td class="p-2 font-mono">${{ $r['stock_value_usd'] }}</td>
            </tr>
        @endforeach
    </table>
</div>
@endsection
