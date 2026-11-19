@extends('layouts.app')
@section('title', 'خريطة الترحيل')
@section('content')
<div class="bg-white rounded-2xl p-6 border space-y-3 text-sm">
    <h1 class="text-xl font-bold">خريطة الترحيل المحاسبي</h1>
    <p class="text-xs text-slate-500">مرجع تصميمي. لا يُنشئ قيوداً إلا بعد تفعيل الترحيل من صفحة الربط. الترحيل الحالي: {{ $shop->posting_enabled ? 'مفعّل' : 'غير مفعّل' }}</p>
    <table class="w-full text-xs">
        <thead class="bg-slate-50"><tr>
            <th class="p-2 text-right">العملية</th><th class="p-2">مدين</th><th class="p-2">دائن</th><th class="p-2">أثر الصندوق</th>
        </tr></thead>
        <tbody>
            <tr class="border-t"><td class="p-2">بيع نقدي</td><td>الصندوق</td><td>المبيعات</td><td>نعم مرة واحدة</td></tr>
            <tr class="border-t"><td class="p-2">بيع آجل</td><td>ذمم العملاء</td><td>المبيعات</td><td>لا</td></tr>
            <tr class="border-t"><td class="p-2">بيع بدفعات</td><td>صندوق + ذمم</td><td>المبيعات</td><td>الجزء المقبوض فقط</td></tr>
            <tr class="border-t"><td class="p-2">تكلفة المبيعات</td><td>تكلفة المبيعات</td><td>المخزون</td><td>لا</td></tr>
            <tr class="border-t"><td class="p-2">شراء نقدي</td><td>المخزون</td><td>الصندوق</td><td>نعم</td></tr>
            <tr class="border-t"><td class="p-2">شراء آجل</td><td>المخزون</td><td>الموردون</td><td>لا</td></tr>
            <tr class="border-t"><td class="p-2">مصروف</td><td>المصاريف</td><td>الصندوق</td><td>نعم</td></tr>
            <tr class="border-t"><td class="p-2">راتب</td><td>الرواتب</td><td>الصندوق</td><td>نعم</td></tr>
            <tr class="border-t"><td class="p-2">مسحوبات</td><td>المسحوبات</td><td>الصندوق</td><td>نعم</td></tr>
            <tr class="border-t"><td class="p-2">سند قبض يؤثر على الذمة</td><td>الصندوق</td><td>العملاء</td><td>نعم</td></tr>
            <tr class="border-t"><td class="p-2">سند توثيق فقط</td><td>—</td><td>—</td><td>لا قيد ولا ذمة</td></tr>
        </tbody>
    </table>
</div>
@endsection
