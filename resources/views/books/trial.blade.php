@extends('layouts.app')
@section('title', 'ميزان المراجعة')
@section('content')
<div class="bg-white rounded-2xl p-6 border space-y-4">
    <h1 class="text-xl font-bold">ميزان المراجعة</h1>
    <p class="text-xs {{ $trial['balanced'] ? 'text-brand-800' : 'text-rose-700' }}">
        الحالة: {{ $trial['balanced'] ? 'متوازن' : 'غير متوازن' }} · الفرق {{ number_format($trial['difference'], 2) }}
    </p>
    <table class="w-full text-xs">
        <thead class="bg-slate-50"><tr>
            <th class="p-2 text-right">رقم</th><th class="p-2 text-right">الحساب</th><th class="p-2">افتتاحي</th>
            <th class="p-2">مدين</th><th class="p-2">دائن</th><th class="p-2">رصيد مدين</th><th class="p-2">رصيد دائن</th>
        </tr></thead>
        <tbody>
        @foreach($trial['rows'] as $r)
            <tr class="border-t">
                <td class="p-2 font-mono">{{ $r['account']->code }}</td>
                <td class="p-2">{{ $r['account']->name }}</td>
                <td class="p-2 font-mono">{{ number_format($r['opening'],2) }}</td>
                <td class="p-2 font-mono">{{ number_format($r['debit'],2) }}</td>
                <td class="p-2 font-mono">{{ number_format($r['credit'],2) }}</td>
                <td class="p-2 font-mono">{{ number_format($r['debit_balance'],2) }}</td>
                <td class="p-2 font-mono">{{ number_format($r['credit_balance'],2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr class="font-bold border-t">
            <td colspan="5" class="p-2">الإجمالي</td>
            <td class="p-2 font-mono">{{ number_format($trial['total_debit'],2) }}</td>
            <td class="p-2 font-mono">{{ number_format($trial['total_credit'],2) }}</td>
        </tr></tfoot>
    </table>
</div>
@endsection
