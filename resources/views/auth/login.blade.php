<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تسجيل الدخول - زاهد للحلول المحاسبية والمالية</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-mark.png') }}?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('layouts.partials.theme')
</head>
<body class="font-sans antialiased bg-brand-950 text-slate-100 min-h-screen flex items-center justify-center p-3 sm:p-4 overflow-x-hidden">
    <div class="max-w-md w-full min-w-0 bg-white text-slate-900 rounded-2xl p-6 sm:p-8 border border-slate-200/90 shadow-2xl">
        <div class="text-center mb-6">
            @include('layouts.partials.logo-mark', ['class' => 'h-20 w-20 mx-auto object-contain'])
            <h1 class="text-lg font-extrabold text-brand-950 mt-3">زاهد للحلول المحاسبية والمالية</h1>
            <p class="text-xs text-slate-500 mt-1">تسجيل الدخول إلى النظام المحاسبي</p>
        </div>

        @if (session('info'))
            <div class="mb-4 p-3 text-xs text-brand-900 rounded-xl bg-brand-50 border border-brand-200">{{ session('info') }}</div>
        @endif

        <form method="POST" action="{{ url('/login') }}" class="space-y-4" autocomplete="on">
            @csrf
            <div>
                <label for="phone" class="block text-xs font-semibold text-slate-700 mb-1.5">رقم الموبايل</label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required autofocus dir="ltr" inputmode="numeric"
                    class="w-full text-sm p-3 rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none font-mono transition-colors">
                @error('phone')
                    <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">كلمة المرور</label>
                <input id="password" type="password" name="password" required dir="ltr"
                    class="w-full text-sm p-3 rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none transition-colors">
                @error('password')
                    <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                @enderror
            </div>
            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-slate-600">تذكرني على هذا الجهاز</span>
                </label>
            </div>
            <button type="submit" class="w-full py-3 px-4 rounded-xl font-bold text-white bg-brand-600 hover:bg-brand-700 text-sm shadow-sm transition-colors">
                تسجيل الدخول للنظام
            </button>
        </form>
    </div>
</body>
</html>
