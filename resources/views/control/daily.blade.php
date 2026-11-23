@extends('layouts.app')

@section('title', 'التقرير اليومي الشامل')

@section('content')
<div class="space-y-6">
    <!-- Header & Date Picker -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">التقرير اليومي الشامل لجميع الحركات</h1>
                <p class="text-xs text-slate-500 mt-1">
                    يشمل الفواتير، الصندوق، مسحوبات الشركاء، رواتب وحركات الموظفين، المصاريف العامة، أجور الشحن، وتحويلات العملة ليوم
                    <span class="font-mono font-bold text-slate-700">{{ $date }}</span>
                </p>
            </div>
            <div class="no-print flex items-center gap-2">
                <form method="GET" action="{{ route('control.daily') }}" class="flex items-center gap-2 text-xs">
                    <label for="daily-date" class="sr-only">تاريخ التقرير</label>
                    <input id="daily-date" type="date" name="date" value="{{ $date }}" class="p-2 rounded-xl border border-slate-300 font-mono">
                    <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl font-bold transition-colors">عرض</button>
                </form>
                <button type="button" onclick="window.print()" class="px-3.5 py-2 text-xs font-semibold border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-xl">
                    طباعة
                </button>
            </div>
        </div>

        <!-- KPI Summary Grid -->
        @php
            $counts = $report['category_counts'] ?? [];
            $partnerCount = $report['partner_withdrawals_count'] ?? ($counts['partner'] ?? 0);
            $partnerSyp = $report['partner_withdrawals_syp'] ?? 0;
        @endphp
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 text-xs">
            <div class="p-4 bg-brand-50 rounded-xl border border-brand-200/80">
                <span class="block text-[11px] text-brand-800 font-semibold">صافي المبيعات</span>
                <strong class="block mt-1 text-lg font-mono font-bold text-brand-950">${{ number_format($report['net_sales_usd'], 2) }}</strong>
                @if($report['sales_discount_usd'] > 0)
                    <span class="block mt-1 text-[11px] text-slate-500">حسم ممنوح: ${{ number_format($report['sales_discount_usd'], 2) }}</span>
                @endif
            </div>

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                <span class="block text-[11px] text-slate-600 font-semibold">صافي المشتريات</span>
                <strong class="block mt-1 text-lg font-mono font-bold text-slate-900">${{ number_format($report['net_purchases_usd'], 2) }}</strong>
                @if($report['purchase_discount_usd'] > 0)
                    <span class="block mt-1 text-[11px] text-slate-500">حسم مكتسب: ${{ number_format($report['purchase_discount_usd'], 2) }}</span>
                @endif
            </div>

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                <span class="block text-[11px] text-slate-500">المقبوضات (وارد نقدي)</span>
                <strong class="block mt-1 text-lg font-mono font-bold text-brand-700">${{ number_format($report['receipts_usd'], 2) }}</strong>
            </div>

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                <span class="block text-[11px] text-slate-500">المدفوعات (صادر نقدي)</span>
                <strong class="block mt-1 text-lg font-mono font-bold text-rose-700">${{ number_format($report['payments_usd'], 2) }}</strong>
            </div>

            <button type="button"
                    data-jump-filter="partner"
                    class="text-right p-4 bg-amber-50 hover:bg-amber-100/80 rounded-xl border border-amber-200 transition-colors">
                <span class="flex items-center justify-between gap-2 text-[11px] text-amber-800 font-semibold">
                    <span>مسحوبات الشركاء</span>
                    <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-900">{{ $partnerCount }} حركة</span>
                </span>
                <strong class="block mt-1 text-lg font-mono font-bold text-amber-950">${{ number_format($report['partner_withdrawals_usd'], 2) }}</strong>
                @if($partnerSyp > 0)
                    <span class="block mt-1 text-[11px] text-amber-800">شامل <span class="font-mono font-semibold" dir="ltr">{{ number_format($partnerSyp) }}</span> ل.س</span>
                @else
                    <span class="block mt-1 text-[11px] text-amber-700/80">شامل سندات الصندوق المباشرة</span>
                @endif
            </button>

            <div class="p-4 bg-teal-50 rounded-xl border border-teal-200">
                <span class="block text-[11px] text-teal-800 font-semibold">رواتب وحركات الموظفين</span>
                <strong class="block mt-1 text-lg font-mono font-bold text-teal-950">${{ number_format($report['employee_total_usd'], 2) }}</strong>
                <span class="block mt-1 text-[11px] text-teal-700">رواتب ${{ number_format($report['salaries_usd'], 2) }} · سلف/دفعات ${{ number_format($report['employee_movements_usd'], 2) }}</span>
            </div>

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                <span class="block text-[11px] text-slate-500">مصاريف عامة + أجور شحن</span>
                <strong class="block mt-1 text-lg font-mono font-bold text-slate-900">${{ number_format($report['expenses_usd'] + $report['shipping_expenses_usd'], 2) }}</strong>
                <span class="block mt-1 text-[11px] text-slate-500">عامة ${{ number_format($report['expenses_usd'], 2) }} · شحن ${{ number_format($report['shipping_expenses_usd'], 2) }}</span>
            </div>

            @php $dailyFx = (float) ($report['fx_diff_usd'] ?? 0); @endphp
            <a href="{{ route('reports.fx', ['from' => $date, 'to' => $date]) }}" class="block p-4 bg-gold-50 hover:bg-gold-100/70 rounded-xl border border-gold-200 transition-colors">
                <span class="block text-[11px] text-gold-800 font-semibold">تحويلات العملة وفروق الصرف ({{ $report['currency_exchanges_count'] }})</span>
                <strong class="block mt-1 text-lg font-mono font-bold text-brand-950" dir="ltr">${{ number_format($report['currency_exchanges_usd'], 2) }}</strong>
                <span class="block mt-1 text-[11px] font-semibold {{ $dailyFx >= 0 ? 'text-brand-700' : 'text-rose-600' }}">
                    فرق الصرف: <span class="font-mono inline-block" dir="ltr">{{ $dailyFx >= 0 ? '+' : '-' }}${{ number_format(abs($dailyFx), 2) }}</span>
                </span>
            </a>
        </div>

        <!-- Daily Cashbox Net Bar -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs pt-2">
            <div class="p-3.5 rounded-xl bg-brand-900 text-white flex items-center justify-between">
                <span>صافي حركة صندوق الدولار اليوم</span>
                <strong class="font-mono text-sm inline-block" dir="ltr">{{ $report['cash_usd'] >= 0 ? '+' : '-' }}${{ number_format(abs($report['cash_usd']), 2) }}</strong>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-800 text-white flex items-center justify-between">
                <span>صافي حركة صندوق الليرة اليوم</span>
                <strong class="text-sm"><span class="font-mono" dir="ltr">{{ $report['cash_syp'] >= 0 ? '+' : '' }}{{ number_format($report['cash_syp']) }}</span> ل.س</strong>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-100 text-slate-800 border border-slate-200 flex items-center justify-between">
                <span>إجمالي حركات اليوم المسجلة</span>
                <strong class="text-sm"><span class="font-mono">{{ $report['total_movements'] }}</span> حركة (<span class="font-mono">{{ $report['operations'] }}</span> فاتورة)</strong>
            </div>
        </div>
    </div>

    <!-- Unified All Movements Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <h2 class="text-base font-bold text-slate-900">كشف جميع حركات اليوم التفصيلي</h2>
            <div class="no-print flex flex-wrap items-center gap-1.5 text-xs" id="daily-filter-bar">
                <button type="button" data-filter="all" class="daily-filter-btn px-3 py-1.5 rounded-lg font-bold bg-brand-600 text-white">الكل ({{ $report['total_movements'] }})</button>
                <button type="button" data-filter="invoice" class="daily-filter-btn px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200">الفواتير ({{ $counts['invoice'] ?? 0 }})</button>
                <button type="button" data-filter="partner" class="daily-filter-btn px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200">مسحوبات الشركاء ({{ $counts['partner'] ?? 0 }})</button>
                <button type="button" data-filter="employee" class="daily-filter-btn px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200">الموظفون ({{ $counts['employee'] ?? 0 }})</button>
                <button type="button" data-filter="expense" class="daily-filter-btn px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200">المصاريف ({{ $counts['expense'] ?? 0 }})</button>
                <button type="button" data-filter="shipping" class="daily-filter-btn px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200">أجور الشحن ({{ $counts['shipping'] ?? 0 }})</button>
                <button type="button" data-filter="exchange" class="daily-filter-btn px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200">تحويل العملة ({{ $counts['exchange'] ?? 0 }})</button>
                <button type="button" data-filter="cashbox" class="daily-filter-btn px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200">الصندوق والسندات ({{ $counts['cashbox'] ?? 0 }})</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs" id="daily-movements-table">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                        <th class="py-3 px-4">القسم</th>
                        <th class="py-3 px-4">المرجع</th>
                        <th class="py-3 px-4">نوع الحركة</th>
                        <th class="py-3 px-4">الطرف / الشريك / الموظف</th>
                        <th class="py-3 px-4">البيان والتفاصيل</th>
                        <th class="py-3 px-4 text-left">المبلغ بالعملة</th>
                        <th class="py-3 px-4 text-left">المعادل ($)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($report['movements'] as $row)
                        @php
                            $badgeClass = match($row['category']) {
                                'invoice' => 'bg-brand-50 text-brand-800',
                                'partner' => 'bg-amber-50 text-amber-800',
                                'employee' => 'bg-teal-50 text-teal-800',
                                'expense' => 'bg-rose-50 text-rose-700',
                                'shipping' => 'bg-cyan-50 text-cyan-800',
                                'exchange' => 'bg-gold-50 text-gold-800',
                                default => 'bg-slate-100 text-slate-700',
                            };
                            $amountClass = $row['direction'] === 'in'
                                ? 'text-brand-700'
                                : ($row['direction'] === 'out' ? 'text-rose-700' : 'text-brand-800');
                        @endphp
                        <tr class="hover:bg-slate-50 daily-row" data-category="{{ $row['category'] }}">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded font-bold text-[11px] {{ $badgeClass }}">{{ $row['category_label'] }}</span>
                            </td>
                            <td class="py-3 px-4 font-mono font-semibold whitespace-nowrap">
                                <a href="{{ $row['url'] }}" class="text-brand-700 hover:underline">{{ $row['reference'] }}</a>
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-800">{{ $row['type_label'] }}</td>
                            <td class="py-3 px-4 font-medium text-slate-700">{{ $row['party'] }}</td>
                            <td class="py-3 px-4 text-slate-500">{{ $row['description'] }}</td>
                            <td class="py-3 px-4 text-left font-mono font-bold whitespace-nowrap {{ $amountClass }}">
                                {{ number_format($row['amount'], $row['currency'] === 'SYP' ? 0 : 2) }} {{ $row['currency'] }}
                            </td>
                            <td class="py-3 px-4 text-left font-mono font-bold whitespace-nowrap text-slate-900">
                                ${{ number_format($row['amount_usd'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400">لا توجد حركات مسجلة في هذا التاريخ.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    (function () {
        const buttons = document.querySelectorAll('.daily-filter-btn');
        const rows = document.querySelectorAll('.daily-row');

        function applyFilter(filter) {
            buttons.forEach(function (b) {
                const active = b.getAttribute('data-filter') === filter;
                b.classList.toggle('bg-brand-600', active);
                b.classList.toggle('text-white', active);
                b.classList.toggle('font-bold', active);
                b.classList.toggle('bg-slate-100', !active);
                b.classList.toggle('text-slate-700', !active);
            });
            rows.forEach(function (row) {
                const match = filter === 'all' || row.getAttribute('data-category') === filter;
                row.classList.toggle('hidden', !match);
            });
        }

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                applyFilter(btn.getAttribute('data-filter'));
            });
        });

        document.querySelectorAll('[data-jump-filter]').forEach(function (card) {
            card.addEventListener('click', function () {
                const targetFilter = card.getAttribute('data-jump-filter');
                if (targetFilter) {
                    applyFilter(targetFilter);
                    const tbl = document.getElementById('daily-movements-table');
                    if (tbl) tbl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        });
    })();
</script>
@endsection
