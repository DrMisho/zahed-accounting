@extends('layouts.app')
@section('title', 'مطابقة القبض والصرف')
@section('content')
<div class="bg-white rounded-2xl p-6 border space-y-4">
    <h1 class="text-xl font-bold">مطابقة القبض والصرف</h1>
    <p class="text-xs text-slate-500">كشف مصادر الحركة. لا تجمع عملات مختلفة.</p>
    <table class="w-full text-xs">
        <thead class="bg-slate-50"><tr><th class="p-2">المصدر</th><th class="p-2">العدد</th><th class="p-2">ملاحظة</th></tr></thead>
        <tr class="border-t"><td class="p-2">العمليات اليومية (فواتير)</td><td class="p-2">{{ $match['daily_ops'] }}</td><td class="p-2">تغذي المخزون والذمم مباشرة</td></tr>
        <tr class="border-t"><td class="p-2">حركات الصندوق</td><td class="p-2">{{ $match['cash_count'] }}</td><td class="p-2">تشمل أثر الفواتير النقدية والصفحات التفصيلية</td></tr>
        <tr class="border-t"><td class="p-2">سندات قبض</td><td class="p-2">{{ $match['receipts']->count() }}</td><td class="p-2">غير مربوطة بالصندوق: {{ $match['unlinked_receipts'] }}</td></tr>
        <tr class="border-t"><td class="p-2">سندات صرف</td><td class="p-2">{{ $match['payments']->count() }}</td><td class="p-2">غير مربوطة بالصندوق: {{ $match['unlinked_payments'] }}</td></tr>
    </table>
</div>
@endsection
