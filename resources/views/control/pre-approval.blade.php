@extends('layouts.app')
@section('title', 'فحص ما قبل الاعتماد')
@section('content')
<div class="bg-white rounded-2xl p-6 border space-y-4">
    <h1 class="text-xl font-bold">فحص ما قبل الاعتماد</h1>
    <p class="text-sm {{ str_contains($report['status'], 'لا توجد') ? 'text-brand-800' : 'text-gold-700' }}">{{ $report['status'] }}</p>
    <p class="text-xs text-slate-500">رقابي فقط ولا يغيّر الأرصدة. الترحيل الآلي {{ $report['mapping']['ready'] ? 'قابل للتفعيل بعد المراجعة' : 'غير مكتمل الربط' }}.</p>
    <table class="w-full text-xs">
        <thead class="bg-slate-50"><tr><th class="p-2 text-right">الفحص</th><th class="p-2">النتيجة</th><th class="p-2">الحالة</th></tr></thead>
        @foreach($report['checks'] as $c)
            <tr class="border-t">
                <td class="p-2">{{ $c[0] }}</td>
                <td class="p-2 font-mono">{{ is_numeric($c[1]) ? number_format($c[1], is_float($c[1])?2:0) : $c[1] }}</td>
                <td class="p-2">{{ $c[2] }}</td>
            </tr>
        @endforeach
    </table>
</div>
@endsection
