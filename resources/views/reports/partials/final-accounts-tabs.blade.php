@php
    $tabs = [
        ['reports.final-accounts', 'الحسابات الختامية الشاملة'],
        ['reports.trading', 'حساب المتاجرة'],
        ['reports.pnl', 'حساب الأرباح والخسائر'],
        ['reports.balance', 'الميزانية العمومية'],
        ['reports.fx', 'فروق صرف العملة'],
    ];
@endphp
<div class="no-print flex flex-wrap items-center gap-1.5 p-1.5 bg-white rounded-2xl border border-slate-200 shadow-sm text-xs">
    @foreach($tabs as [$route, $label])
        @php $active = request()->routeIs($route); @endphp
        <a href="{{ route($route) }}"
           class="px-3.5 py-2 rounded-xl font-semibold transition-colors {{ $active ? 'bg-brand-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
