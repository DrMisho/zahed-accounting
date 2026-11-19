@extends('layouts.app')
@section('title', 'مخزون المواقع')
@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border text-xs space-y-3">
        <h1 class="text-xl font-bold">مخزون المواقع</h1>
        <form method="POST" action="{{ route('inventory.locations.store') }}" class="flex gap-2">
            @csrf
            <input name="name" required placeholder="اسم موقع جديد" class="p-2 rounded-xl border flex-1">
            <button class="px-3 py-2 bg-brand-900 text-white rounded-xl">إضافة موقع</button>
        </form>
        <form method="POST" action="{{ route('inventory.openings.store') }}" class="grid grid-cols-2 md:grid-cols-5 gap-2">
            @csrf
            <select name="location_id" required class="p-2 rounded-xl border">
                @foreach($locations as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
            </select>
            <select name="item_id" required class="p-2 rounded-xl border">
                @foreach($items as $i)<option value="{{ $i->id }}">{{ $i->item_name }}</option>@endforeach
            </select>
            <input name="opening_qty" type="number" step="0.01" value="0" class="p-2 rounded-xl border" placeholder="افتتاح">
            <input name="opening_cost_usd" type="number" step="0.01" value="0" class="p-2 rounded-xl border">
            <button class="px-3 py-2 bg-brand-800 text-white rounded-xl">حفظ افتتاح</button>
        </form>
    </div>
    <div class="bg-white rounded-2xl border overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50"><tr>
                <th class="p-2">الموقع</th><th class="p-2">الصنف</th><th class="p-2">افتتاح</th><th class="p-2">شراء</th>
                <th class="p-2">بيع</th><th class="p-2">مناقلة وارد</th><th class="p-2">مناقلة صادر</th><th class="p-2">الرصيد</th>
            </tr></thead>
            @foreach($rows as $r)
                <tr class="border-t text-center {{ $r['negative'] ? 'bg-rose-50' : '' }}">
                    <td class="p-2">{{ $r['location']->name }}</td>
                    <td class="p-2">{{ $r['item']->item_name }}</td>
                    <td class="p-2 font-mono">{{ $r['opening_qty'] }}</td>
                    <td class="p-2 font-mono">{{ $r['purchased'] }}</td>
                    <td class="p-2 font-mono">{{ $r['sold'] }}</td>
                    <td class="p-2 font-mono">{{ $r['transfer_in'] }}</td>
                    <td class="p-2 font-mono">{{ $r['transfer_out'] }}</td>
                    <td class="p-2 font-mono font-bold">{{ $r['current'] }}</td>
                </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection
