@php
    $isShopView = Auth::user()->hasRole('shop_manager') || session()->has('impersonated_shop_id');
    $navGroups = $isShopView ? [
        ['اليومية', request()->routeIs('transactions.*','cashbox.*','vouchers.*','expenses.*'), [
            ['transactions.index','الفواتير'],
            ['cashbox.index','حركة الصندوق'],
            ['vouchers.index','سندات القبض', ['type'=>'receipt']],
            ['vouchers.index','سند الصرف', ['type'=>'payment']],
            ['expenses.index','المصاريف العامة'],
        ]],
        ['المخزون', request()->routeIs('items.*','inventory.*'), [
            ['items.index','دليل الأصناف'],
            ['inventory.sheet','كشف المخزون'],
            ['inventory.movement','حركة الأصناف'],
            ['inventory.alerts','تنبيهات المخزون'],
            ['inventory.locations','مخزون المواقع'],
            ['inventory.transfers','مناقلات المخزون'],
        ]],
        ['الذمم', request()->routeIs('contacts.*','control.collection'), [
            ['contacts.index','العملاء', ['type'=>'customer']],
            ['contacts.index','الموردون', ['type'=>'supplier']],
            ['control.collection','متابعة التحصيل'],
        ]],
        ['الموظفون', request()->routeIs('employees.*','salaries.*','employee-movements.*','partners.*'), [
            ['employees.index','الموظفون'],
            ['salaries.index','رواتب الموظفين'],
            ['employee-movements.index','حركة الموظفين'],
            ['partners.index','مسحوبات الشركاء'],
        ]],
        ['المحاسبة', request()->routeIs('books.*','reports.*'), [
            ['books.accounts','دليل الحسابات'],
            ['books.ledger','الأستاذ العام'],
            ['books.trial','ميزان المراجعة'],
            ['reports.pnl','قائمة الدخل'],
            ['reports.balance','الميزانية العمومية'],
            ['reports.profitability','تحليل التكلفة والربحية'],
            ['books.cost-centers','مراكز التكلفة'],
        ]],
        ['الرقابة', request()->routeIs('control.*','settings.*'), [
            ['control.daily','التقرير اليومي'],
            ['control.owner','تقرير صاحب العمل'],
            ['control.kpis','المؤشرات الإدارية'],
            ['control.cash-close','إغلاق الصندوق'],
            ['control.match','مطابقة القبض والصرف'],
            ['settings.edit','إعدادات النظام'],
        ]],
    ] : [];
@endphp

<nav class="bg-white border-b-2 border-gold-400 sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14 sm:h-16 gap-2 min-w-0">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 min-w-0 shrink">
                <span class="shrink-0 inline-flex items-center justify-center w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-brand-950 p-0.5 overflow-hidden">
                    @include('layouts.partials.logo-mark', ['class' => 'w-full h-full object-contain'])
                </span>
                <div class="flex flex-col min-w-0">
                    <span class="text-base sm:text-lg font-bold text-brand-900 leading-none truncate">زاهد للحلول المالية والمحاسبية</span>
                    <span class="text-[10px] sm:text-[11px] text-slate-500 mt-1 truncate max-w-[42vw] sm:max-w-[220px]">
                        @if(Auth::user()->hasRole('super_admin') && !session()->has('impersonated_shop_id'))
                            لوحة السوبر أدمن
                        @else
                            {{ current_shop()->name ?? 'متجر تجاري' }}
                        @endif
                    </span>
                </div>
            </a>

            <div class="hidden lg:flex items-center gap-0.5 text-sm font-medium flex-1 overflow-visible">
                @if(Auth::user()->hasRole('super_admin'))
                    <a href="{{ route('admin.shops') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('admin.*') ? 'bg-brand-900 text-gold-300 font-bold' : 'text-slate-600 hover:bg-brand-50' }}">المحلات</a>
                    <a href="{{ route('settings.edit') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('settings.*') ? 'bg-brand-900 text-gold-300 font-bold' : 'text-slate-600 hover:bg-brand-50' }}">الإعدادات</a>
                    @if(session()->has('impersonated_shop_id'))
                        <form method="POST" action="{{ url('/admin/leave-impersonation') }}" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-brand-900 text-white">إنهاء تصفح المحل</button>
                        </form>
                    @endif
                @endif

                @if($isShopView)
                    <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-brand-900 text-gold-300 font-bold' : 'text-slate-600 hover:bg-brand-50' }}">الرئيسية</a>
                    @foreach($navGroups as $group)
                        @include('layouts.nav-group', ['label' => $group[0], 'active' => $group[1], 'links' => $group[2]])
                    @endforeach
                @endif
            </div>

            <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
                @if(current_shop())
                    <div class="hidden md:flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gold-50 text-gold-800 border border-gold-200 text-xs font-semibold">
                        <span>1$ = </span>
                        <span class="font-mono font-bold">{{ number_format(current_shop()->exchange_rate) }}</span>
                        <span>ل.س</span>
                    </div>
                @endif
                <span class="hidden sm:inline font-bold text-slate-800 text-xs max-w-[120px] truncate">{{ Auth::user()->name }}</span>
                <form method="POST" action="{{ url('/logout') }}">
                    @csrf
                    <button type="submit" class="px-2 sm:px-3 py-1.5 rounded-lg text-xs text-rose-600 hover:bg-rose-50 border border-rose-200">خروج</button>
                </form>
                <button type="button" id="nav-toggle" class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 text-slate-700" aria-label="فتح القائمة" aria-expanded="false" aria-controls="mobile-nav">
                    <svg id="nav-icon-open" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg id="nav-icon-close" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </div>

    <div id="mobile-nav" class="hidden lg:hidden border-t border-slate-200 bg-white max-h-[min(75vh,32rem)] overflow-y-auto">
        <div class="px-3 py-3 space-y-1 text-sm">
            @if(current_shop())
                <div class="md:hidden mb-2 px-3 py-2 rounded-lg bg-gold-50 text-gold-800 border border-gold-200 text-xs font-semibold">
                    سعر الصرف: 1$ = <span class="font-mono font-bold">{{ number_format(current_shop()->exchange_rate) }}</span> ل.س
                </div>
            @endif
            <div class="sm:hidden px-3 py-1 text-xs text-slate-500">{{ Auth::user()->name }}</div>

            @if(Auth::user()->hasRole('super_admin'))
                <a href="{{ route('admin.shops') }}" class="block px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.*') ? 'bg-brand-900 text-gold-300 font-bold' : 'text-slate-700' }}">إدارة المحلات</a>
                <a href="{{ route('settings.edit') }}" class="block px-3 py-2.5 rounded-lg {{ request()->routeIs('settings.*') ? 'bg-brand-900 text-gold-300 font-bold' : 'text-slate-700' }}">الإعدادات</a>
                @if(session()->has('impersonated_shop_id'))
                    <form method="POST" action="{{ url('/admin/leave-impersonation') }}">
                        @csrf
                        <button type="submit" class="w-full text-right px-3 py-2.5 rounded-lg text-white bg-brand-900 font-bold">إنهاء تصفح المحل</button>
                    </form>
                @endif
            @endif

            @if($isShopView)
                <a href="{{ route('dashboard') }}" class="block px-3 py-2.5 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-brand-900 text-gold-300 font-bold' : 'text-slate-700' }}">الرئيسية</a>
                @foreach($navGroups as $group)
                    <details class="rounded-lg" @if($group[1]) open @endif>
                        <summary class="cursor-pointer px-3 py-2.5 font-bold text-slate-800 {{ $group[1] ? 'bg-brand-50 text-brand-900 rounded-lg' : '' }}">{{ $group[0] }}</summary>
                        <div class="pr-3 pb-2 space-y-0.5">
                            @foreach($group[2] as $link)
                                <a href="{{ route($link[0], $link[2] ?? []) }}" class="block px-3 py-2 rounded-lg text-slate-600 hover:bg-gold-50 hover:text-brand-900">{{ $link[1] }}</a>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            @endif
        </div>
    </div>
</nav>
<script>
    (function () {
        const toggle = document.getElementById('nav-toggle');
        const menu = document.getElementById('mobile-nav');
        const openIcon = document.getElementById('nav-icon-open');
        const closeIcon = document.getElementById('nav-icon-close');
        if (!toggle || !menu) return;
        toggle.addEventListener('click', function () {
            const open = menu.classList.toggle('hidden') === false;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            openIcon.classList.toggle('hidden', open);
            closeIcon.classList.toggle('hidden', !open);
        });
    })();
</script>
