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
                        50: '#f5f3ff',
                        100: '#ede9fe',
                        200: '#ddd6fe',
                        300: '#c4b5fd',
                        400: '#a78bfa',
                        500: '#7c3aed',
                        600: '#6d28d9',
                        700: '#5b21b6',
                        800: '#4c1d95',
                        900: '#3b0764',
                        950: '#2e1065',
                    },
                    gold: {
                        50: '#fbf8eb',
                        100: '#f4ecd0',
                        200: '#eadb91',
                        300: '#dcc45a',
                        400: '#d4af37',
                        500: '#c9a227',
                        600: '#b8860b',
                        700: '#8a6509',
                        800: '#6b4f0c',
                        900: '#5a430f',
                    },
                },
            }
        }
    }
</script>
<style>
    html { -webkit-text-size-adjust: 100%; }
    body { overflow-x: hidden; }
    main { min-width: 0; }
    main .overflow-x-auto { -webkit-overflow-scrolling: touch; }
    details > summary { list-style: none; }
    details > summary::-webkit-details-marker { display: none; }
    .btn-primary { background-color: #3b0764; color: #fff; }
    .btn-primary:hover { background-color: #2e1065; }
    .print-letterhead, .print-dev-credit { display: none; }
    @media print {
        nav, .site-footer, .no-print, .flash-alerts { display: none !important; }
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
        .print-letterhead .shop-name { font-size: 13px; font-weight: 700; color: #3b0764; margin-top: 4px; }
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
