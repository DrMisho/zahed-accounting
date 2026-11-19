<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'زاهد للحلول المحاسبية والمالية') }} - @yield('title', 'لوحة التحكم')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-mark.png') }}?v=2">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    @include('layouts.partials.theme')
    @stack('styles')
</head>
@php
    $routeName = request()->route()?->getName() ?? '';
    $sectionLabel = match (true) {
        str_starts_with($routeName, 'transactions.'),
        str_starts_with($routeName, 'cashbox.'),
        str_starts_with($routeName, 'currency-exchanges.'),
        str_starts_with($routeName, 'vouchers.'),
        str_starts_with($routeName, 'expenses.'),
        str_starts_with($routeName, 'shipping.') => 'اليومية',
        str_starts_with($routeName, 'items.'),
        str_starts_with($routeName, 'inventory.') => 'المخزون',
        str_starts_with($routeName, 'contacts.'),
        $routeName === 'control.collection' => 'الذمم',
        str_starts_with($routeName, 'employees.'),
        str_starts_with($routeName, 'salaries.'),
        str_starts_with($routeName, 'employee-movements.'),
        str_starts_with($routeName, 'partners.') => 'الموظفون والشركاء',
        str_starts_with($routeName, 'books.'),
        str_starts_with($routeName, 'reports.') => 'المحاسبة والتقارير',
        str_starts_with($routeName, 'control.'),
        str_starts_with($routeName, 'settings.') => 'الرقابة والإعدادات',
        str_starts_with($routeName, 'admin.') => 'إدارة المنصة',
        default => null,
    };
@endphp
<body class="font-sans antialiased bg-[#F4F6FA] text-slate-900 min-h-screen flex overflow-x-hidden">
    <!-- Permanent Collapsible Sidebar -->
    @include('layouts.navigation')

    <!-- Main Workspace Area -->
    <div class="flex-1 min-w-0 flex flex-col min-h-screen">
        @sectionMissing('custom_print_chrome')
            <!-- Print-Only Letterhead -->
            <div class="print-letterhead">
                <img src="{{ asset('images/logo-mark.png') }}?v=2" alt="زاهد للحلول المحاسبية والمالية">
                <div class="shop-name">
                    {{ current_shop()->name ?? config('app.name', 'زاهد للحلول المحاسبية والمالية') }}
                </div>
            </div>
        @endif

        <!-- Minimal Top Breadcrumb Header -->
        <header class="top-breadcrumb-bar sticky top-0 z-20 px-4 sm:px-6 pt-3 pb-2 bg-[#F4F6FA]/90 backdrop-blur-sm">
            <div class="bg-white border border-slate-200/90 rounded-xl px-4 sm:px-5 h-14 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <!-- Mobile Menu Button -->
                    <button type="button"
                            id="sidebar-mobile-open"
                            aria-label="فتح القائمة الجانبية"
                            aria-controls="app-sidebar"
                            aria-expanded="false"
                            class="lg:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-900 shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <!-- Breadcrumbs -->
                    <nav aria-label="مسار التنقل" class="flex items-center gap-2 text-sm min-w-0 overflow-hidden">
                        @hasSection('breadcrumbs')
                            @yield('breadcrumbs')
                        @else
                            @if($routeName === 'dashboard' || $routeName === 'admin.shops')
                                <span class="font-semibold text-slate-800 truncate">@yield('title', 'لوحة التحكم')</span>
                            @else
                                <a href="{{ Auth::user()->hasRole('super_admin') && !session()->has('impersonated_shop_id') ? route('admin.shops') : route('dashboard') }}"
                                   class="text-slate-400 hover:text-brand-600 transition-colors shrink-0">
                                    الرئيسية
                                </a>
                                @if($sectionLabel)
                                    <span class="text-slate-300 shrink-0" aria-hidden="true">/</span>
                                    <span class="text-slate-500 hidden sm:inline shrink-0">{{ $sectionLabel }}</span>
                                @endif
                                <span class="text-slate-300 shrink-0" aria-hidden="true">/</span>
                                <span class="font-semibold text-slate-800 truncate">@yield('title', 'لوحة التحكم')</span>
                            @endif
                        @endif
                    </nav>
                </div>

                @include('layouts.partials.header-calculator')
            </div>
        </header>

        <!-- Flash Alerts -->
        <div class="flash-alerts px-4 sm:px-6 mt-2 w-full">
            @if (session('success'))
                <div class="p-3.5 mb-3 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('info'))
                <div class="p-3.5 mb-3 text-sm text-brand-800 rounded-xl bg-brand-50 border border-brand-200">
                    {{ session('info') }}
                </div>
            @endif

            @if (session('status'))
                <div class="p-3.5 mb-3 text-sm text-brand-800 rounded-xl bg-brand-50 border border-brand-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-3.5 mb-3 text-sm text-rose-800 rounded-xl bg-rose-50 border border-rose-200">
                    <ul class="list-disc pr-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Main Content -->
        <main class="flex-1 w-full px-4 sm:px-6 py-4 min-w-0">
            @yield('content')
        </main>

        <!-- Clean Subtle Footer -->
        <footer class="site-footer bg-white border-t border-slate-200/80 py-4 px-4 sm:px-6 mt-auto text-xs text-slate-500">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-right">
                <div class="flex items-center gap-2.5">
                    <strong class="text-slate-700">زاهد للحلول المحاسبية والمالية</strong>
                    <span class="text-slate-300" aria-hidden="true">·</span>
                    <span class="font-mono text-slate-500" dir="ltr">0984393644</span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" aria-label="فيسبوك" title="فيسبوك"
                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:bg-brand-50 hover:text-brand-600 hover:border-brand-200 transition-colors">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 9H16V6h-2.5C11.57 6 10 7.57 10 9.5V11H8v3h2v7h3v-7h2.1l.9-3H13v-1.5c0-.28.22-.5.5-.5z"/></svg>
                    </a>
                    <a href="https://wa.me/963984393644" target="_blank" rel="noopener noreferrer" aria-label="واتساب" title="واتساب"
                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 transition-colors">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 11.5A8.5 8.5 0 0 1 7.6 18.7L4 20l1.4-3.5A8.5 8.5 0 1 1 20 11.5zm-8.5 6.7c1.4 0 2.7-.4 3.8-1.1l.3-.2 2.2.6-.6-2.1.2-.3a6.2 6.2 0 1 0-2.2 2.7l-.3.2c-1 .6-2.2.9-3.4.9zm3.5-4.6c-.2-.1-1.2-.6-1.4-.7-.2-.1-.3-.1-.5.1l-.5.6c-.1.1-.3.2-.5.1s-1-.4-1.9-1.2c-.7-.6-1.2-1.4-1.3-1.6-.1-.2 0-.4.1-.5l.3-.4.2-.3c.1-.1.1-.2 0-.4l-.7-1.6c-.2-.4-.4-.4-.5-.4h-.4c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9s.8 2.2.9 2.3c.1.2 1.6 2.5 3.9 3.4 2.3.9 2.3.6 2.7.6.4 0 1.3-.5 1.5-1 .2-.5.2-.9.1-1 0-.1-.2-.1-.4-.2z"/></svg>
                    </a>
                    <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" aria-label="إنستغرام" title="إنستغرام"
                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:bg-brand-50 hover:text-brand-600 hover:border-brand-200 transition-colors">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm10 2H7a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2zm-5 3.2A3.8 3.8 0 1 1 8.2 12 3.8 3.8 0 0 1 12 8.2zm0 2A1.8 1.8 0 1 0 13.8 12 1.8 1.8 0 0 0 12 10.2zM17.2 6.8a1 1 0 1 1-1 1 1 1 0 0 1 1-1z"/></svg>
                    </a>
                </div>
                <div class="text-[11px] text-slate-400">
                    @include('layouts.partials.developer-credit')
                </div>
            </div>
        </footer>
    </div>

    @sectionMissing('custom_print_chrome')
        <div class="print-dev-credit">@include('layouts.partials.developer-credit')</div>
    @endif
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
