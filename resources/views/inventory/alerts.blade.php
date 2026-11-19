@extends('layouts.app')
@section('title', 'تنبيهات المخزون')
@section('content')
<div class="bg-white rounded-2xl p-6 border">
    <h1 class="text-xl font-bold mb-4">تنبيهات المخزون</h1>
    <table class="w-full text-xs">
        <thead class="bg-slate-50"><tr><th class="p-2">الصنف</th><th class="p-2">الرصيد</th><th class="p-2">حد التنبيه</th><th class="p-2">الحالة</th></tr></thead>
        @foreach($rows as $r)
            <tr class="border-t text-center {{ $r['is_low_stock'] ? 'bg-gold-50' : '' }}">
                <td class="p-2">{{ $r['item']->item_name }}</td>
                <td class="p-2 font-mono">{{ $r['current_stock'] }}</td>
                <td class="p-2 font-mono">{{ $r['item']->alert_limit }}</td>
                <td class="p-2">{{ $r['is_low_stock'] ? 'يحتاج متابعة' : 'سليم' }}</td>
            </tr>
        @endforeach
    </table>
</div>
@endsection
