@extends('layouts.app')
@section('title', 'إغلاق الصندوق')
@section('content')
<div class="bg-white rounded-2xl p-6 border space-y-4">
    <h1 class="text-xl font-bold">إغلاق حركة الصندوق</h1>
    <p class="text-xs text-slate-500">ملخص مستقل حسب العملة. لا تجمع الدولار مع الليرة.</p>
    <div class="grid grid-cols-2 gap-4">
        <div class="p-4 border rounded-xl">
            <div class="text-xs text-slate-500">دولار</div>
            <div>وارد {{ number_format($cash['usd']['inflows'],2) }}</div>
            <div>صادر {{ number_format($cash['usd']['outflows'],2) }}</div>
            <div class="font-bold">صافي {{ number_format($cash['usd']['balance'],2) }}</div>
        </div>
        <div class="p-4 border rounded-xl">
            <div class="text-xs text-slate-500">ليرة سورية</div>
            <div>وارد {{ number_format($cash['syp']['inflows']) }}</div>
            <div>صادر {{ number_format($cash['syp']['outflows']) }}</div>
            <div class="font-bold">صافي {{ number_format($cash['syp']['balance']) }}</div>
        </div>
    </div>
    <p class="text-xs">المعادل الإجمالي التقريبي ${{ number_format($cash['total_usd_equivalent'],2) }} — لا يغني عن الجرد الفعلي.</p>
</div>
@endsection
