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
                        50: '#ECFDF5',
                        100: '#D1FAE5',
                        200: '#A7F3D0',
                        300: '#6EE7B7',
                        400: '#34D399',
                        500: '#10B981',
                        600: '#059669',
                        700: '#047857',
                        800: '#065F46',
                        900: '#064E3B',
                        950: '#04241D',
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
    body { overflow-x: hidden; background-color: #F3F6F4; }
    main { min-width: 0; }
    main .overflow-x-auto { -webkit-overflow-scrolling: touch; }
    .font-mono { font-variant-numeric: tabular-nums; }
    details > summary { list-style: none; }
    details > summary::-webkit-details-marker { display: none; }
    .btn-primary { background-color: #059669; color: #fff; }
    .btn-primary:hover { background-color: #047857; }

    /* Sidebar desktop width & smooth collapse */
    #app-sidebar {
        width: 260px;
        max-width: 85vw;
        transition: width 200ms cubic-bezier(0.16, 1, 0.3, 1), transform 220ms cubic-bezier(0.16, 1, 0.3, 1);
    }
    .sidebar-scroll::-webkit-scrollbar { width: 5px; }
    .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
    .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(167, 243, 208, 0.22); border-radius: 9999px; }
    .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(167, 243, 208, 0.4); }

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
        .print-letterhead .shop-name { font-size: 13px; font-weight: 700; color: #064E3B; margin-top: 4px; }
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
