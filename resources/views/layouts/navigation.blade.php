@php
    $isSuperAdmin = Auth::user()->hasRole('super_admin');
    $isImpersonating = session()->has('impersonated_shop_id');
    $isShopView = Auth::user()->hasRole('shop_manager') || $isImpersonating;

    $sidebarSections = [];

    if ($isSuperAdmin && !$isImpersonating) {
        $sidebarSections[] = [
            'title' => 'إدارة المنصة',
            'items' => [
                ['admin.shops', 'إدارة المحلات', 'shops', [], 'admin.*'],
                ['settings.edit', 'إعدادات النظام', 'settings', [], 'settings.*'],
            ],
        ];
    } elseif ($isShopView) {
        if ($isSuperAdmin) {
            $sidebarSections[] = [
                'title' => 'المنصة',
                'items' => [
                    ['admin.shops', 'إدارة المحلات', 'shops', [], 'admin.*'],
                ],
            ];
        }

        $sidebarSections[] = [
            'title' => 'الرئيسية',
            'items' => [
                ['dashboard', 'لوحة التحكم', 'dashboard', [], 'dashboard'],
            ],
        ];

        $sidebarSections[] = [
            'title' => 'اليومية',
            'items' => [
                ['transactions.index', 'الفواتير', 'invoices', [], 'transactions.*'],
                ['cashbox.index', 'حركة الصندوق', 'cashbox', [], 'cashbox.*'],
                ['currency-exchanges.index', 'تحويل العملة', 'exchange', [], 'currency-exchanges.*'],
                ['vouchers.index', 'سندات القبض', 'receipt', ['type' => 'receipt'], 'vouchers.index'],
                ['vouchers.index', 'سندات الصرف', 'payment', ['type' => 'payment'], 'vouchers.index'],
                ['expenses.index', 'المصاريف العامة', 'expenses', [], 'expenses.*'],
                ['shipping.index', 'مصاريف أجور الشحن', 'shipping', [], 'shipping.*'],
            ],
        ];

        $sidebarSections[] = [
            'title' => 'المخزون',
            'items' => [
                ['items.index', 'دليل الأصناف', 'items', [], 'items.*'],
                ['inventory.sheet', 'كشف المخزون', 'inventory-sheet', [], 'inventory.sheet'],
                ['inventory.movement', 'حركة الأصناف', 'inventory-movement', [], 'inventory.movement'],
                ['inventory.alerts', 'تنبيهات المخزون', 'alerts', [], 'inventory.alerts'],
                ['inventory.locations', 'مخزون المواقع', 'locations', [], 'inventory.locations'],
                ['inventory.transfers', 'مناقلات المخزون', 'transfers', [], 'inventory.transfers'],
            ],
        ];

        $sidebarSections[] = [
            'title' => 'الذمم',
            'items' => [
                ['contacts.index', 'العملاء', 'customers', ['type' => 'customer'], 'contacts.index'],
                ['contacts.index', 'الموردون', 'suppliers', ['type' => 'supplier'], 'contacts.index'],
                ['control.collection', 'متابعة التحصيل', 'collection', [], 'control.collection'],
            ],
        ];

        $sidebarSections[] = [
            'title' => 'الموظفون والشركاء',
            'items' => [
                ['employees.index', 'الموظفون', 'employees', [], 'employees.*'],
                ['salaries.index', 'رواتب الموظفين', 'salaries', [], 'salaries.*'],
                ['employee-movements.index', 'حركة الموظفين', 'employee-movements', [], 'employee-movements.*'],
                ['partners.index', 'مسحوبات الشركاء', 'partners', [], 'partners.*'],
            ],
        ];

        $sidebarSections[] = [
            'title' => 'المحاسبة والتقارير',
            'items' => [
                ['books.accounts', 'دليل الحسابات', 'accounts', [], 'books.accounts'],
                ['books.ledger', 'الأستاذ العام', 'ledger', [], 'books.ledger'],
                ['books.trial', 'ميزان المراجعة', 'trial', [], 'books.trial'],
                ['reports.pnl', 'قائمة الدخل', 'pnl', [], 'reports.pnl'],
                ['reports.balance', 'الميزانية العمومية', 'balance', [], 'reports.balance'],
                ['reports.profitability', 'تحليل التكلفة والربحية', 'profitability', [], 'reports.profitability'],
                ['books.cost-centers', 'مراكز التكلفة', 'cost-centers', [], 'books.cost-centers'],
            ],
        ];

        $sidebarSections[] = [
            'title' => 'الرقابة والإعدادات',
            'items' => [
                ['control.daily', 'التقرير اليومي', 'daily', [], 'control.daily'],
                ['control.owner', 'تقرير صاحب العمل', 'owner', [], 'control.owner'],
                ['control.kpis', 'المؤشرات الإدارية', 'kpis', [], 'control.kpis'],
                ['control.cash-close', 'إغلاق الصندوق', 'cash-close', [], 'control.cash-close'],
                ['control.match', 'مطابقة القبض والصرف', 'match', [], 'control.match'],
                ['settings.edit', 'إعدادات النظام', 'settings', [], 'settings.*'],
            ],
        ];
    }

    $roleTitle = $isSuperAdmin && !$isImpersonating
        ? 'مدير المنصة'
        : (current_shop()->name ?? 'مدير المتجر');
@endphp

<!-- Mobile Drawer Backdrop -->
<div id="sidebar-backdrop" class="hidden fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-[1px] lg:hidden" aria-hidden="true"></div>

<!-- Permanent Collapsible Sidebar -->
<aside id="app-sidebar"
       aria-label="القائمة الجانبية"
       class="fixed inset-y-0 right-0 z-50 lg:sticky lg:top-0 lg:h-screen flex flex-col bg-brand-950 text-slate-300 border-l border-white/10 shrink-0 translate-x-full lg:translate-x-0 select-none">

    <!-- Top Brand Logo + Collapse Toggle -->
    <div class="px-3.5 pt-4 pb-3 border-b border-white/[0.08]">
        <div class="sidebar-header-inner flex items-center justify-between gap-2">
            <a href="{{ $isShopView ? route('dashboard') : route('admin.shops') }}" class="flex items-center gap-2.5 min-w-0">
                <span class="shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white p-1 shadow-sm overflow-hidden">
                    @include('layouts.partials.logo-mark', ['class' => 'w-full h-full object-contain'])
                </span>
                <div class="sidebar-hide-collapsed flex flex-col min-w-0">
                    <span class="text-sm font-bold text-white leading-tight truncate">زاهد المحاسبي</span>
                    <span class="text-[11px] text-slate-400 mt-0.5 truncate">{{ $roleTitle }}</span>
                </div>
            </a>

            <!-- Desktop Collapse Button -->
            <button type="button"
                    id="sidebar-collapse-btn"
                    title="تصغير أو توسيع القائمة"
                    aria-label="تصغير أو توسيع القائمة الجانبية"
                    aria-expanded="true"
                    class="hidden lg:inline-flex items-center justify-center w-7 h-7 rounded-lg bg-white/[0.06] hover:bg-white/[0.12] border border-white/10 text-slate-300 hover:text-white transition-colors shrink-0">
                <svg id="sidebar-collapse-icon" class="w-4 h-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/>
                </svg>
            </button>

            <!-- Mobile Close Button -->
            <button type="button"
                    id="sidebar-mobile-close"
                    aria-label="إغلاق القائمة"
                    class="lg:hidden inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white/[0.06] hover:bg-white/[0.12] text-slate-300 hover:text-white">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Signed-in User Box (matching reference design) -->
        <div class="sidebar-hide-collapsed mt-3.5 px-3 py-2.5 rounded-xl bg-white/[0.04] border border-white/[0.08]">
            <div class="text-[10px] font-medium text-slate-400">مسجّل الدخول باسم</div>
            <div class="text-xs font-bold text-brand-300 mt-0.5 truncate">{{ Auth::user()->name }}</div>
            @if($isSuperAdmin && $isImpersonating)
                <form method="POST" action="{{ url('/admin/leave-impersonation') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="w-full py-1.5 px-2.5 rounded-lg text-[11px] font-semibold bg-brand-600 hover:bg-brand-500 text-white transition-colors">
                        إنهاء تصفح المحل
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Scrollable Navigation Menu -->
    <nav class="sidebar-scroll flex-1 overflow-y-auto px-2.5 py-3 space-y-4">
        @foreach($sidebarSections as $sectionIndex => $section)
            <div>
                @if($sectionIndex > 0)
                    <div class="sidebar-divider-collapsed hidden border-t border-white/10 mx-2 mb-2.5"></div>
                @endif
                <div class="sidebar-hide-collapsed px-3 pb-1.5 text-[11px] font-semibold text-slate-500 tracking-wide">
                    {{ $section['title'] }}
                </div>
                <div class="space-y-0.5">
                    @foreach($section['items'] as $item)
                        @php
                            [$routeName, $label, $icon, $params, $pattern] = $item;
                            $routeMatched = request()->routeIs($pattern);
                            if ($routeMatched && !empty($params)) {
                                foreach ($params as $k => $v) {
                                    $queryVal = request()->query($k);
                                    if ($routeName === 'vouchers.index' && $queryVal === null) {
                                        $queryVal = 'receipt';
                                    }
                                    if ($queryVal !== $v) {
                                        $routeMatched = false;
                                        break;
                                    }
                                }
                            }
                        @endphp
                        <a href="{{ route($routeName, $params) }}"
                           title="{{ $label }}"
                           class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] transition-colors whitespace-nowrap {{ $routeMatched ? 'bg-brand-600 text-white font-semibold shadow-sm shadow-black/20' : 'text-slate-300 hover:bg-white/[0.06] hover:text-white' }}">
                            @include('layouts.partials.sidebar-icon', ['name' => $icon, 'class' => 'w-[18px] h-[18px] shrink-0 ' . ($routeMatched ? 'text-white' : 'text-slate-400')])
                            <span class="sidebar-hide-collapsed truncate">{{ $label }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <!-- Bottom Sign Out inside Sidebar -->
    <div class="p-3 border-t border-white/[0.08] mt-auto">
        <form method="POST" action="{{ url('/logout') }}">
            @csrf
            <button type="submit"
                    title="تسجيل الخروج"
                    class="sidebar-logout-btn w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium text-slate-300 hover:bg-rose-500/15 hover:text-rose-300 transition-colors whitespace-nowrap">
                @include('layouts.partials.sidebar-icon', ['name' => 'logout', 'class' => 'w-[18px] h-[18px] shrink-0'])
                <span class="sidebar-hide-collapsed">تسجيل الخروج</span>
            </button>
        </form>
    </div>
</aside>

<script>
    (function () {
        const html = document.documentElement;
        const collapseBtn = document.getElementById('sidebar-collapse-btn');
        const sidebar = document.getElementById('app-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        const mobileOpen = document.getElementById('sidebar-mobile-open');
        const mobileClose = document.getElementById('sidebar-mobile-close');

        if (collapseBtn) {
            collapseBtn.setAttribute('aria-expanded', html.classList.contains('sidebar-collapsed') ? 'false' : 'true');
            collapseBtn.addEventListener('click', function () {
                const collapsed = html.classList.toggle('sidebar-collapsed');
                collapseBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                try {
                    localStorage.setItem('zahed_sidebar_collapsed', collapsed ? '1' : '0');
                } catch (e) {}
            });
        }

        function openMobileDrawer() {
            if (!sidebar || !backdrop) return;
            sidebar.classList.remove('translate-x-full');
            backdrop.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeMobileDrawer() {
            if (!sidebar || !backdrop) return;
            sidebar.classList.add('translate-x-full');
            backdrop.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        if (mobileOpen) mobileOpen.addEventListener('click', openMobileDrawer);
        if (mobileClose) mobileClose.addEventListener('click', closeMobileDrawer);
        if (backdrop) backdrop.addEventListener('click', closeMobileDrawer);
    })();
</script>
