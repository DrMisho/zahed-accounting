@extends('layouts.app')
@section('title', 'متابعة التحصيل')
@section('content')
<div class="bg-white rounded-2xl p-6 border">
    <h1 class="text-xl font-bold mb-4">متابعة التحصيل</h1>
    <table class="w-full text-xs">
        <thead class="bg-slate-50"><tr><th class="p-2">العميل</th><th class="p-2">رصيد $</th><th class="p-2">رصيد ل.س</th><th class="p-2">الأولوية</th><th class="p-2">كشف</th></tr></thead>
        @forelse($list as $s)
            <tr class="border-t text-center">
                <td class="p-2">{{ $s['contact']->name }}</td>
                <td class="p-2 font-mono">${{ number_format($s['final_balance_usd'],2) }}</td>
                <td class="p-2 font-mono">{{ number_format($s['final_balance_syp']) }}</td>
                <td class="p-2">{{ $s['priority'] }}</td>
                <td class="p-2"><a class="text-brand-800" href="{{ route('contacts.statement', $s['contact']) }}">كشف</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="p-6 text-center text-slate-400">لا أرصدة مطلوبة للتحصيل</td></tr>
        @endforelse
    </table>
</div>
@endsection
