@extends('layouts.app')
@section('title', 'الإعدادات')
@section('content')
<div class="max-w-lg mx-auto space-y-6">
    @if($shop && Auth::user()->hasRole('shop_manager'))
    <div class="bg-white rounded-2xl p-6 border">
        <h1 class="text-xl font-bold mb-4">إعدادات المنشأة</h1>
        <form method="POST" action="{{ route('settings.update') }}" class="space-y-3 text-xs">
            @csrf
            <label class="block">اسم المنشأة<input name="name" value="{{ $shop->name }}" class="mt-1 w-full p-2.5 rounded-xl border"></label>
            <label class="block">صاحب العمل<input name="owner_name" value="{{ $shop->owner_name }}" class="mt-1 w-full p-2.5 rounded-xl border"></label>
            <label class="block">هاتف المنشأة<input name="phone" value="{{ $shop->phone }}" class="mt-1 w-full p-2.5 rounded-xl border" dir="ltr"></label>
            <label class="block">سعر صرف الدولار<input name="exchange_rate" type="number" value="{{ $shop->exchange_rate }}" class="mt-1 w-full p-2.5 rounded-xl border font-mono"></label>
            <label class="block">حد تنبيه المخزون الافتراضي<input name="default_alert_limit" type="number" value="{{ $shop->default_alert_limit }}" class="mt-1 w-full p-2.5 rounded-xl border"></label>
            <label class="block">ملاحظات<textarea name="company_notes" class="mt-1 w-full p-2.5 rounded-xl border">{{ $shop->company_notes }}</textarea></label>

            <div class="p-3 rounded-xl border {{ $shop->admin_one_time_access ? 'border-brand-300 bg-brand-50' : 'border-slate-200' }}">
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" name="admin_one_time_access" value="1" class="mt-1 rounded border-slate-300 text-brand-800" {{ $shop->admin_one_time_access ? 'checked' : '' }}>
                    <span>
                        <strong class="block text-brand-900">منح الدعم الفني الدخول لمرة واحدة</strong>
                        <span class="text-slate-500">عند التفعيل يظهر للأدمن زر دخول كمدير لهذه الجلسة فقط، ثم يُلغى التخويل تلقائياً.</span>
                    </span>
                </label>
            </div>

            <h2 class="text-sm font-bold pt-3">حساب الدخول</h2>
            <label class="block">رقم الموبايل (لتسجيل الدخول)<input name="account_phone" value="{{ $user->phone }}" required class="mt-1 w-full p-2.5 rounded-xl border font-mono" dir="ltr"></label>
            <label class="block">كلمة المرور الحالية<input type="password" name="current_password" class="mt-1 w-full p-2.5 rounded-xl border"></label>
            <label class="block">كلمة المرور الجديدة<input type="password" name="password" class="mt-1 w-full p-2.5 rounded-xl border"></label>
            <label class="block">تأكيد كلمة المرور الجديدة<input type="password" name="password_confirmation" class="mt-1 w-full p-2.5 rounded-xl border"></label>
            <button class="px-5 py-2 bg-brand-800 text-white rounded-xl font-bold">حفظ</button>
        </form>
    </div>
    @else
    <div class="bg-white rounded-2xl p-6 border">
        <h1 class="text-xl font-bold mb-4">إعدادات الحساب</h1>
        <form method="POST" action="{{ route('settings.update') }}" class="space-y-3 text-xs">
            @csrf
            <label class="block">رقم الموبايل (لتسجيل الدخول)<input name="account_phone" value="{{ $user->phone }}" required class="mt-1 w-full p-2.5 rounded-xl border font-mono" dir="ltr"></label>
            <label class="block">كلمة المرور الحالية<input type="password" name="current_password" class="mt-1 w-full p-2.5 rounded-xl border"></label>
            <label class="block">كلمة المرور الجديدة<input type="password" name="password" class="mt-1 w-full p-2.5 rounded-xl border"></label>
            <label class="block">تأكيد كلمة المرور الجديدة<input type="password" name="password_confirmation" class="mt-1 w-full p-2.5 rounded-xl border"></label>
            <button class="px-5 py-2 bg-brand-800 text-white rounded-xl font-bold">حفظ</button>
        </form>
    </div>
    @endif
</div>
@endsection
