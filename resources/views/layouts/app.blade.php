<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'زاهد للحلول المالية والمحاسبية') }} - @yield('title', 'لوحة التحكم')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-mark.png') }}?v=2">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    @include('layouts.partials.theme')
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900 min-h-screen flex flex-col overflow-x-hidden">
    <div class="print-letterhead">
        <img src="{{ asset('images/logo.png') }}?v=2" alt="زاهد للحلول المالية والمحاسبية">
        <div class="shop-name">
            {{ current_shop()->name ?? config('app.name', 'زاهد للحلول المالية والمحاسبية') }}
        </div>
    </div>

    @include('layouts.navigation')

    <!-- Flash Alerts -->
    <div class="flash-alerts max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
        @if (session('success'))
            <div class="p-4 mb-4 text-sm text-brand-900 rounded-xl bg-brand-50 border border-gold-200">
                {{ session('success') }}
            </div>
        @endif

        @if (session('info'))
            <div class="p-4 mb-4 text-sm text-blue-800 rounded-xl bg-blue-50 border border-blue-200">
                {{ session('info') }}
            </div>
        @endif

        @if (session('status'))
            <div class="p-4 mb-4 text-sm text-blue-800 rounded-xl bg-blue-50 border border-blue-200">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 mb-4 text-sm text-rose-800 rounded-xl bg-rose-50 border border-rose-200">
                <ul class="list-disc pr-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-6 min-w-0">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="site-footer bg-brand-950 border-t-2 border-gold-500 py-6 mt-auto text-xs text-gold-100">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-right">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/logo-mark.png') }}?v=2" alt="" class="h-9 w-9 object-contain">
                <div class="leading-tight">
                    <strong class="block text-gold-300">زاهد للحلول المالية والمحاسبية</strong>
                    <span class="block mt-1 text-[11px] font-mono text-gold-200" dir="ltr">0984393644</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" aria-label="فيسبوك" title="فيسبوك"
                   class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-gold-400/50 text-gold-300 hover:bg-gold-500 hover:text-brand-950 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 9H16V6h-2.5C11.57 6 10 7.57 10 9.5V11H8v3h2v7h3v-7h2.1l.9-3H13v-1.5c0-.28.22-.5.5-.5z"/></svg>
                </a>
                <a href="https://wa.me/963984393644" target="_blank" rel="noopener noreferrer" aria-label="واتساب" title="واتساب"
                   class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-gold-400/50 text-gold-300 hover:bg-gold-500 hover:text-brand-950 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 11.5A8.5 8.5 0 0 1 7.6 18.7L4 20l1.4-3.5A8.5 8.5 0 1 1 20 11.5zm-8.5 6.7c1.4 0 2.7-.4 3.8-1.1l.3-.2 2.2.6-.6-2.1.2-.3a6.2 6.2 0 1 0-2.2 2.7l-.3.2c-1 .6-2.2.9-3.4.9zm3.5-4.6c-.2-.1-1.2-.6-1.4-.7-.2-.1-.3-.1-.5.1l-.5.6c-.1.1-.3.2-.5.1s-1-.4-1.9-1.2c-.7-.6-1.2-1.4-1.3-1.6-.1-.2 0-.4.1-.5l.3-.4.2-.3c.1-.1.1-.2 0-.4l-.7-1.6c-.2-.4-.4-.4-.5-.4h-.4c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9s.8 2.2.9 2.3c.1.2 1.6 2.5 3.9 3.4 2.3.9 2.3.6 2.7.6.4 0 1.3-.5 1.5-1 .2-.5.2-.9.1-1 0-.1-.2-.1-.4-.2z"/></svg>
                </a>
                <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" aria-label="إنستغرام" title="إنستغرام"
                   class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-gold-400/50 text-gold-300 hover:bg-gold-500 hover:text-brand-950 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm10 2H7a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2zm-5 3.2A3.8 3.8 0 1 1 8.2 12 3.8 3.8 0 0 1 12 8.2zm0 2A1.8 1.8 0 1 0 13.8 12 1.8 1.8 0 0 0 12 10.2zM17.2 6.8a1 1 0 1 1-1 1 1 1 0 0 1 1-1z"/></svg>
                </a>
            </div>
            <div class="text-gold-200 leading-relaxed">
                <div>نظام محاسبي متعدد المحلات والعملات</div>
                <div class="mt-1 text-[11px] text-gold-300 font-semibold">@include('layouts.partials.developer-credit')</div>
            </div>
        </div>
    </footer>
    <div class="print-dev-credit">@include('layouts.partials.developer-credit')</div>
    <script>
        document.querySelectorAll('main table').forEach(function (table) {
            if (table.closest('.overflow-x-auto')) return;
            var wrap = document.createElement('div');
            wrap.className = 'overflow-x-auto';
            table.parentNode.insertBefore(wrap, table);
            wrap.appendChild(table);
        });
    </script>
</body>
</html>
