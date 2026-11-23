@php
    $isSuperAdmin = Auth::user()->hasRole('super_admin');
    $isImpersonating = session()->has('impersonated_shop_id');
    $isShopView = Auth::user()->hasRole('shop_manager') || $isImpersonating;

    $directLinks = [];
    $sidebarGroups = [];

    if ($isSuperAdmin && !$isImpersonating) {
        $sidebarGroups[] = [
            'id' => 'platform',
            'title' => 'إدارة المنصة',
            'icon' => 'shops',
            'items' => [
                ['admin.shops', 'إدارة المحلات', 'shops', [], 'admin.*'],
                ['settings.edit', 'إعدادات النظام', 'settings', [], 'settings.*'],
            ],
        ];
    } elseif ($isShopView) {
        if ($isSuperAdmin) {
            $directLinks[] = ['admin.shops', 'إدارة المحلات', 'shops', [], 'admin.*'];
        }
        $directLinks[] = ['dashboard', 'لوحة التحكم', 'dashboard', [], 'dashboard'];

        $sidebarGroups[] = [
            'id' => 'daily',
            'title' => 'اليومية',
            'icon' => 'invoices',
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

        $sidebarGroups[] = [
            'id' => 'inventory',
            'title' => 'المخزون',
            'icon' => 'items',
            'items' => [
                ['items.index', 'دليل الأصناف', 'items', [], 'items.*'],
                ['inventory.sheet', 'كشف المخزون', 'inventory-sheet', [], 'inventory.sheet'],
                ['inventory.movement', 'حركة الأصناف', 'inventory-movement', [], 'inventory.movement'],
                ['inventory.alerts', 'تنبيهات المخزون', 'alerts', [], 'inventory.alerts'],
                ['inventory.locations', 'مخزون المواقع', 'locations', [], 'inventory.locations'],
                ['inventory.transfers', 'مناقلات المخزون', 'transfers', [], 'inventory.transfers'],
            ],
        ];

        $sidebarGroups[] = [
            'id' => 'receivables',
            'title' => 'الذمم والكشوف',
            'icon' => 'customers',
            'items' => [
                ['contacts.index', 'العملاء', 'customers', ['type' => 'customer'], 'contacts.index'],
                ['contacts.index', 'الموردون', 'suppliers', ['type' => 'supplier'], 'contacts.index'],
                ['reports.customers', 'كشف العملاء', 'customers', [], 'reports.customers'],
                ['reports.suppliers', 'كشف الموردين', 'suppliers', [], 'reports.suppliers'],
                ['reports.sales', 'كشف المبيعات', 'sales-statement', [], 'reports.sales'],
                ['reports.purchases', 'كشف المشتريات', 'purchases-statement', [], 'reports.purchases'],
                ['control.collection', 'متابعة التحصيل', 'collection', [], 'control.collection'],
            ],
        ];

        $sidebarGroups[] = [
            'id' => 'people',
            'title' => 'الموظفون والشركاء',
            'icon' => 'employees',
            'items' => [
                ['employees.index', 'الموظفون', 'employees', [], 'employees.*'],
                ['salaries.index', 'رواتب الموظفين', 'salaries', [], 'salaries.*'],
                ['employee-movements.index', 'حركة الموظفين', 'employee-movements', [], 'employee-movements.*'],
                ['partners.index', 'مسحوبات الشركاء', 'partners', [], 'partners.*'],
            ],
        ];

        $sidebarGroups[] = [
            'id' => 'accounting',
            'title' => 'المحاسبة والتقارير',
            'icon' => 'accounts',
            'items' => [
                ['reports.final-accounts', 'الحسابات الختامية', 'final-accounts', [], 'reports.final-accounts'],
                ['reports.trading', 'حساب المتاجرة', 'trading', [], 'reports.trading'],
                ['reports.pnl', 'الأرباح والخسائر', 'pnl', [], 'reports.pnl'],
                ['reports.balance', 'الميزانية العمومية', 'balance', [], 'reports.balance'],
                ['reports.fx', 'فروق صرف العملة', 'fx-diff', [], 'reports.fx'],
                ['books.accounts', 'دليل الحسابات', 'accounts', [], 'books.accounts'],
                ['books.ledger', 'الأستاذ العام', 'ledger', [], 'books.ledger'],
                ['books.trial', 'ميزان المراجعة', 'trial', [], 'books.trial'],
                ['reports.profitability', 'تحليل التكلفة والربحية', 'profitability', [], 'reports.profitability'],
                ['books.cost-centers', 'مراكز التكلفة', 'cost-centers', [], 'books.cost-centers'],
            ],
        ];

        $sidebarGroups[] = [
            'id' => 'control',
            'title' => 'الرقابة والإعدادات',
            'icon' => 'kpis',
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

    $isItemActive = function (array $item): bool {
        [$routeName, , , $params, $pattern] = $item;
        $matched = request()->routeIs($pattern);
        if ($matched && !empty($params)) {
            foreach ($params as $k => $v) {
                $queryVal = request()->query($k);
                if ($routeName === 'vouchers.index' && $queryVal === null) {
                    $queryVal = 'receipt';
                }
                if ($queryVal !== $v) {
                    return false;
                }
            }
        }
        return $matched;
    };

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
                    <span class="text-[12px] font-bold text-white leading-snug">زاهد للحلول المحاسبية والمالية</span>
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

        <!-- Signed-in User Box -->
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

    <!-- Scrollable Navigation Menu with Collapsible Parent Groups -->
    <nav class="sidebar-scroll flex-1 overflow-y-auto px-2.5 py-3 space-y-1.5">
        @if(!empty($directLinks))
            <div class="space-y-0.5 pb-2 border-b border-white/[0.08] mb-2">
                @foreach($directLinks as $item)
                    @php
                        [$routeName, $label, $icon, $params] = $item;
                        $routeMatched = $isItemActive($item);
                    @endphp
                    <a href="{{ route($routeName, $params) }}"
                       title="{{ $label }}"
                       class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] transition-colors whitespace-nowrap {{ $routeMatched ? 'bg-brand-600 text-white font-semibold shadow-sm shadow-black/20' : 'text-slate-200 hover:bg-white/[0.06] hover:text-white' }}">
                        @include('layouts.partials.sidebar-icon', ['name' => $icon, 'class' => 'w-[18px] h-[18px] shrink-0 ' . ($routeMatched ? 'text-white' : 'text-slate-400')])
                        <span class="sidebar-hide-collapsed truncate">{{ $label }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        @foreach($sidebarGroups as $group)
            @php
                $groupHasActive = false;
                foreach ($group['items'] as $gItem) {
                    if ($isItemActive($gItem)) {
                        $groupHasActive = true;
                        break;
                    }
                }
                $isGroupOpen = $groupHasActive || count($sidebarGroups) === 1;
                $panelId = 'sidebar-group-' . $group['id'];
            @endphp
            <div class="sidebar-group" data-group-id="{{ $group['id'] }}">
                <button type="button"
                        title="{{ $group['title'] }}"
                        aria-expanded="{{ $isGroupOpen ? 'true' : 'false' }}"
                        aria-controls="{{ $panelId }}"
                        data-sidebar-group-toggle="{{ $panelId }}"
                        class="sidebar-group-toggle w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-colors whitespace-nowrap {{ $groupHasActive ? 'bg-white/[0.08] text-white' : 'text-slate-200 hover:bg-white/[0.06] hover:text-white' }}">
                    <span class="flex items-center gap-3 min-w-0">
                        @include('layouts.partials.sidebar-icon', ['name' => $group['icon'], 'class' => 'w-[18px] h-[18px] shrink-0 ' . ($groupHasActive ? 'text-brand-400' : 'text-slate-400')])
                        <span class="sidebar-hide-collapsed truncate">{{ $group['title'] }}</span>
                    </span>
                    <svg class="sidebar-hide-collapsed sidebar-group-chevron w-4 h-4 shrink-0 text-slate-400 transition-transform duration-200 {{ $isGroupOpen ? 'rotate-180 text-white' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>

                <div id="{{ $panelId }}" class="sidebar-group-panel {{ $isGroupOpen ? '' : 'hidden' }} mt-1 mr-4 pr-2.5 border-r border-white/10 space-y-0.5">
                    @foreach($group['items'] as $item)
                        @php
                            [$routeName, $label, $icon, $params] = $item;
                            $routeMatched = $isItemActive($item);
                        @endphp
                        <a href="{{ route($routeName, $params) }}"
                           title="{{ $label }}"
                           class="sidebar-link flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[12.5px] transition-colors whitespace-nowrap {{ $routeMatched ? 'bg-brand-600 text-white font-semibold shadow-sm shadow-black/20' : 'text-slate-300 hover:bg-white/[0.06] hover:text-white' }}">
                            @include('layouts.partials.sidebar-icon', ['name' => $icon, 'class' => 'w-4 h-4 shrink-0 ' . ($routeMatched ? 'text-white' : 'text-slate-400')])
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
        function initSidebar() {
            const html = document.documentElement;
            const sidebar = document.getElementById('app-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            const collapseBtn = document.getElementById('sidebar-collapse-btn');

            function setDesktopCollapsed(collapsed) {
                html.classList.toggle('sidebar-collapsed', collapsed);
                if (collapseBtn) {
                    collapseBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                }
                try {
                    localStorage.setItem('zahed_sidebar_collapsed', collapsed ? '1' : '0');
                } catch (e) {}
            }

            if (collapseBtn) {
                collapseBtn.setAttribute('aria-expanded', html.classList.contains('sidebar-collapsed') ? 'false' : 'true');
                collapseBtn.addEventListener('click', function () {
                    setDesktopCollapsed(!html.classList.contains('sidebar-collapsed'));
                });
            }

            function openMobileDrawer() {
                if (!sidebar || !backdrop) return;
                sidebar.classList.remove('translate-x-full');
                sidebar.classList.add('translate-x-0');
                backdrop.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                const mobileOpen = document.getElementById('sidebar-mobile-open');
                if (mobileOpen) mobileOpen.setAttribute('aria-expanded', 'true');
            }

            function closeMobileDrawer() {
                if (!sidebar || !backdrop) return;
                sidebar.classList.add('translate-x-full');
                sidebar.classList.remove('translate-x-0');
                backdrop.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                const mobileOpen = document.getElementById('sidebar-mobile-open');
                if (mobileOpen) mobileOpen.setAttribute('aria-expanded', 'false');
            }

            document.addEventListener('click', function (event) {
                const target = event.target;
                if (!(target instanceof Element)) return;

                if (target.closest('#sidebar-mobile-open')) {
                    openMobileDrawer();
                    return;
                }

                if (target.closest('#sidebar-mobile-close') || target.closest('#sidebar-backdrop')) {
                    closeMobileDrawer();
                    return;
                }

                const toggleBtn = target.closest('[data-sidebar-group-toggle]');
                if (toggleBtn) {
                    const panelId = toggleBtn.getAttribute('data-sidebar-group-toggle');
                    const panel = panelId ? document.getElementById(panelId) : null;
                    if (!panel) return;

                    const chevron = toggleBtn.querySelector('.sidebar-group-chevron');
                    const isDesktopCollapsed = window.innerWidth >= 1024 && html.classList.contains('sidebar-collapsed');

                    if (isDesktopCollapsed) {
                        setDesktopCollapsed(false);
                        panel.classList.remove('hidden');
                        toggleBtn.setAttribute('aria-expanded', 'true');
                        if (chevron) chevron.classList.add('rotate-180', 'text-white');
                        return;
                    }

                    const willOpen = panel.classList.contains('hidden');
                    panel.classList.toggle('hidden', !willOpen);
                    toggleBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                    if (chevron) {
                        chevron.classList.toggle('rotate-180', willOpen);
                        chevron.classList.toggle('text-white', willOpen);
                    }
                    return;
                }

                if (window.innerWidth < 1024 && target.closest('#app-sidebar a.sidebar-link')) {
                    closeMobileDrawer();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && sidebar && !sidebar.classList.contains('translate-x-full') && window.innerWidth < 1024) {
                    closeMobileDrawer();
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSidebar);
        } else {
            initSidebar();
        }
    })();
</script>
