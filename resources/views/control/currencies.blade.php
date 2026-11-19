@extends('layouts.app')
@section('title', 'رقابة العملات')
@section('content')
<div class="bg-white rounded-2xl p-6 border">
    <h1 class="text-xl font-bold mb-2">رقابة العملات وأسعار الصرف</h1>
    <p class="text-xs text-slate-500 mb-4">ورقة رقابية فقط: لا تعدّل الأرصدة.</p>
    <table class="w-full text-xs">
        <thead class="bg-slate-50"><tr><th class="p-2">المصدر</th><th class="p-2">التاريخ</th><th class="p-2">النوع</th><th class="p-2">المبلغ</th><th class="p-2">العملة</th><th class="p-2">سعر الصرف</th><th class="p-2">الفحص</th></tr></thead>
        @foreach($rows as $r)
            <tr class="border-t text-center {{ $r['note'] !== 'سليم' ? 'bg-gold-50' : '' }}">
                <td class="p-2">{{ $r['source'] }}</td>
                <td class="p-2 font-mono">{{ $r['date'] }}</td>
                <td class="p-2">{{ $r['type'] }}</td>
                <td class="p-2 font-mono">{{ $r['amount'] }}</td>
                <td class="p-2">{{ $r['currency'] }}</td>
                <td class="p-2 font-mono">{{ $r['rate'] }}</td>
                <td class="p-2">{{ $r['note'] }}</td>
            </tr>
        @endforeach
    </table>
</div>
@endsection
