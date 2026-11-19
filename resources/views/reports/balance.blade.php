@extends('layouts.app')
@section('title', 'الميزانية العمومية')
@section('content')
<div class="max-w-3xl mx-auto bg-white rounded-2xl p-8 border space-y-4">
    <h1 class="text-xl font-bold">المركز المالي الإداري</h1>
    <p class="text-xs text-slate-500">تجميع بالدولار مع عرض الليرة منفصلة. ليس ميزانية قانونية بقيد مزدوج ما لم يُفعّل الترحيل.</p>
    <table class="w-full text-sm">
        <tr class="border-b"><td class="py-2">النقدية في الصندوق</td><td class="text-left font-mono">${{ number_format($bs['cash_usd'],2) }}</td><td class="text-left font-mono">{{ number_format($bs['cash_syp']) }} ل.س</td></tr>
        <tr class="border-b"><td class="py-2">ذمم العملاء</td><td class="text-left font-mono">${{ number_format($bs['ar_usd'],2) }}</td><td class="text-left font-mono">{{ number_format($bs['ar_syp']) }} ل.س</td></tr>
        <tr class="border-b"><td class="py-2">المخزون التقديري</td><td class="text-left font-mono">${{ number_format($bs['inventory_usd'],2) }}</td><td></td></tr>
        <tr class="border-b"><td class="py-2">ذمم الموردين</td><td class="text-left font-mono">${{ number_format($bs['ap_usd'],2) }}</td><td class="text-left font-mono">{{ number_format($bs['ap_syp']) }} ل.س</td></tr>
        <tr class="border-b"><td class="py-2">مسحوبات الشركاء النهائية</td><td class="text-left font-mono">${{ number_format($bs['withdrawals_usd'],2) }}</td><td></td></tr>
        <tr><td class="py-2 font-bold">صافي الربح التقديري</td><td class="text-left font-mono font-bold">${{ number_format($bs['profit_usd'],2) }}</td><td></td></tr>
    </table>
</div>
@endsection
