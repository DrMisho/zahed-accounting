<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['IBM Plex Sans Arabic', 'Cairo', 'sans-serif'],
                    mono: ['JetBrains Mono', 'monospace'],
                },
                colors: {
                    brand: {
                        50: '#EFF6FF',
                        100: '#DBEAFE',
                        200: '#BFDBFE',
                        300: '#93C5FD',
                        400: '#60A5FA',
                        500: '#3B82F6',
                        600: '#2563EB',
                        700: '#1D4ED8',
                        800: '#1D4ED8',
                        900: '#0F2544',
                        950: '#0B1A33',
                    },
                    gold: {
                        50: '#FFFBEB',
                        100: '#FEF3C7',
                        200: '#FDE68A',
                        300: '#FCD34D',
                        400: '#F59E0B',
                        500: '#D97706',
                        600: '#B45309',
                        700: '#92400E',
                        800: '#78350F',
                        900: '#451A03',
                    },
                },
            }
        }
    }
</script>
<style>
    html { -webkit-text-size-adjust: 100%; }
    body { overflow-x: hidden; background-color: #F4F6FA; }
    main { min-width: 0; }
    main .overflow-x-auto { -webkit-overflow-scrolling: touch; }
    .font-mono { font-variant-numeric: tabular-nums; }
    details > summary { list-style: none; }
    details > summary::-webkit-details-marker { display: none; }
    .btn-primary { background-color: #1D4ED8; color: #fff; }
    .btn-primary:hover { background-color: #1E40AF; }

    /* Sidebar desktop width & smooth collapse */
    #app-sidebar {
        width: 260px;
        max-width: 85vw;
        transition: width 200ms cubic-bezier(0.16, 1, 0.3, 1), transform 220ms cubic-bezier(0.16, 1, 0.3, 1);
    }
    .sidebar-scroll::-webkit-scrollbar { width: 5px; }
    .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
    .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.22); border-radius: 9999px; }
    .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.4); }

    @media (min-width: 1024px) {
        #app-sidebar {
            max-width: none;
        }
        html.sidebar-collapsed #app-sidebar {
            width: 76px;
        }
        html.sidebar-collapsed #app-sidebar .sidebar-hide-collapsed,
        html.sidebar-collapsed #app-sidebar .sidebar-group-panel {
            display: none !important;
        }
        html.sidebar-collapsed #app-sidebar .sidebar-divider-collapsed {
            display: block !important;
        }
        html.sidebar-collapsed #app-sidebar .sidebar-header-inner {
            justify-content: center;
            flex-direction: column;
            gap: 0.5rem;
        }
        html.sidebar-collapsed #app-sidebar .sidebar-link,
        html.sidebar-collapsed #app-sidebar .sidebar-group-toggle,
        html.sidebar-collapsed #app-sidebar .sidebar-logout-btn {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }
        html.sidebar-collapsed #sidebar-collapse-icon {
            transform: rotate(180deg);
        }
    }

    .print-letterhead, .print-dev-credit { display: none; }
    @media print {
        aside, nav, header, .top-breadcrumb-bar, .site-footer, .no-print, .flash-alerts, #sidebar-backdrop { display: none !important; }
        body { background: #fff !important; overflow: visible !important; padding-bottom: 22px !important; }
        main { max-width: none !important; padding: 0 0 22px !important; }
        .print-letterhead {
            display: block !important;
            text-align: center;
            margin: 0 0 18px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .print-letterhead img { height: 72px; width: auto; margin: 0 auto; }
        .print-letterhead .shop-name { font-size: 13px; font-weight: 700; color: #0F2544; margin-top: 4px; }
        .print-dev-credit {
            display: block !important;
            position: fixed;
            bottom: 6px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #64748b;
            font-weight: 500;
        }
        @page { margin: 12mm 12mm 18mm 12mm; }
        a[href]::after { content: none !important; }
    }
</style>
<script>
    (function () {
        try {
            if (window.localStorage && localStorage.getItem('zahed_sidebar_collapsed') === '1') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    })();
</script>
