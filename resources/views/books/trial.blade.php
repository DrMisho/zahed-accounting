@extends('layouts.app')

@section('title', 'ميزان المراجعة')

@section('content')
<div class="space-y-5">
    <div class="bg-white rounded-2xl p-6 border border-slate-200 space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">ميزان المراجعة بالمجاميع والأرصدة (Trial Balance)</h1>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $shop->name }} · مستخرج مباشرة من دفتر الأستاذ العام بالعملة الأساسية <strong class="text-brand-800">الدولار الأمريكي (USD)</strong>
                </p>
            </div>
            <div class="no-print flex flex-wrap items-center gap-2 text-xs">
                <a href="{{ route('reports.final-accounts') }}" class="px-3.5 py-2 border border-brand-300 bg-brand-50 text-brand-900 hover:bg-brand-100 font-semibold rounded-xl whitespace-nowrap">
                    الحسابات الختامية
                </a>
                <a href="{{ route('books.accounts') }}" class="px-3.5 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold rounded-xl whitespace-nowrap">
                    شجرة الحسابات
                </a>
                <button type="button" onclick="window.print()" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl whitespace-nowrap">
                    طباعة
                </button>
            </div>
        </div>

        <!-- Balance Status & Summary -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div class="p-4 rounded-xl border {{ $trial['balanced'] ? 'bg-brand-50 border-brand-200' : 'bg-rose-50 border-rose-200' }}">
                <span class="block text-[11px] font-semibold {{ $trial['balanced'] ? 'text-brand-800' : 'text-rose-800' }}">حالة توازن الميزان</span>
                <strong class="block mt-1 text-base font-extrabold {{ $trial['balanced'] ? 'text-brand-950' : 'text-rose-700' }}">
                    {{ $trial['balanced'] ? 'متوازن 100% (USD)' : 'يوجد فرق توازن' }}
                </strong>
                @if(!$trial['balanced'])
                    <span class="block mt-0.5 font-mono text-rose-700" dir="ltr">الفرق: ${{ number_format(abs($trial['difference']), 2) }}</span>
                @endif
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-500">مجاميع الحركات (مدين / دائن)</span>
                <strong class="block mt-1 text-base font-mono font-bold text-slate-900" dir="ltr">${{ number_format($trial['period_debit'], 2) }}</strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-500">إجمالي الأرصدة المدينة ($)</span>
                <strong class="block mt-1 text-base font-mono font-bold text-slate-900" dir="ltr">${{ number_format($trial['total_debit'], 2) }}</strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] text-slate-500">إجمالي الأرصدة الدائنة ($)</span>
                <strong class="block mt-1 text-base font-mono font-bold text-slate-900" dir="ltr">${{ number_format($trial['total_credit'], 2) }}</strong>
            </div>
        </div>

        <!-- Date & Hide-Zero Filter -->
        <form method="GET" action="{{ route('books.trial') }}" class="no-print flex flex-wrap items-end gap-3 text-xs pt-1">
            <div>
                <label for="tb-from" class="block text-[11px] font-semibold text-slate-600 mb-1">من تاريخ</label>
                <input id="tb-from" type="date" name="from" value="{{ $from }}" class="p-2 rounded-xl border border-slate-300 font-mono">
            </div>
            <div>
                <label for="tb-to" class="block text-[11px] font-semibold text-slate-600 mb-1">إلى تاريخ</label>
                <input id="tb-to" type="date" name="to" value="{{ $to }}" class="p-2 rounded-xl border border-slate-300 font-mono">
            </div>
            <label class="inline-flex items-center gap-2 py-2 px-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                <input type="hidden" name="hide_zero" value="0">
                <input type="checkbox" name="hide_zero" value="1" @checked($hideZero) class="rounded text-brand-600">
                <span class="font-semibold text-slate-700">إخفاء الحسابات الصفرية</span>
            </label>
            <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl">عرض</button>
            @if($from || $to)
                <a href="{{ route('books.trial') }}" class="px-3 py-2 border border-slate-300 text-slate-600 hover:bg-slate-50 rounded-xl">مسح</a>
            @endif
        </form>
    </div>

    <!-- Trial Balance Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                        <th class="py-3 px-4">الرمز</th>
                        <th class="py-3 px-4">الحساب</th>
                        <th class="py-3 px-3">القائمة</th>
                        <th class="py-3 px-3 text-left">افتتاحي ($)</th>
                        <th class="py-3 px-3 text-left">مجموع مدين ($)</th>
                        <th class="py-3 px-3 text-left">مجموع دائن ($)</th>
                        <th class="py-3 px-4 text-left bg-brand-50/40">رصيد مدين ($)</th>
                        <th class="py-3 px-4 text-left bg-brand-50/40">رصيد دائن ($)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($trial['rows'] as $r)
                        <tr class="hover:bg-slate-50/70">
                            <td class="py-2.5 px-4 font-mono text-slate-600 whitespace-nowrap">{{ $r['account']->code }}</td>
                            <td class="py-2.5 px-4 font-semibold text-slate-900">
                                <a href="{{ route('books.ledger', ['account_id' => $r['account']->id]) }}" class="hover:text-brand-700 hover:underline">
                                    {{ $r['account']->name }}
                                </a>
                            </td>
                            <td class="py-2.5 px-3 text-slate-500 whitespace-nowrap">{{ $r['account']->statementLabel() }}</td>
                            <td class="py-2.5 px-3 text-left font-mono text-slate-600 whitespace-nowrap" dir="ltr">
                                {{ abs($r['opening']) >= 0.01 ? (($r['opening'] < 0 ? '-' : '') . '$' . number_format(abs($r['opening']), 2)) : '—' }}
                            </td>
                            <td class="py-2.5 px-3 text-left font-mono text-slate-700 whitespace-nowrap" dir="ltr">
                                {{ $r['debit'] > 0 ? '$' . number_format($r['debit'], 2) : '—' }}
                            </td>
                            <td class="py-2.5 px-3 text-left font-mono text-slate-700 whitespace-nowrap" dir="ltr">
                                {{ $r['credit'] > 0 ? '$' . number_format($r['credit'], 2) : '—' }}
                            </td>
                            <td class="py-2.5 px-4 text-left font-mono font-bold text-slate-900 bg-brand-50/20 whitespace-nowrap" dir="ltr">
                                {{ $r['debit_balance'] > 0 ? '$' . number_format($r['debit_balance'], 2) : '—' }}
                            </td>
                            <td class="py-2.5 px-4 text-left font-mono font-bold text-slate-900 bg-brand-50/20 whitespace-nowrap" dir="ltr">
                                {{ $r['credit_balance'] > 0 ? '$' . number_format($r['credit_balance'], 2) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-400">لا توجد أرصدة مرحلة بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 border-t-2 border-slate-300 font-extrabold text-slate-950">
                        <td colspan="4" class="py-3.5 px-4">الإجمالي العام (العملة الأساسية USD)</td>
                        <td class="py-3.5 px-3 text-left font-mono" dir="ltr">${{ number_format($trial['period_debit'], 2) }}</td>
                        <td class="py-3.5 px-3 text-left font-mono" dir="ltr">${{ number_format($trial['period_credit'], 2) }}</td>
                        <td class="py-3.5 px-4 text-left font-mono text-brand-950" dir="ltr">${{ number_format($trial['total_debit'], 2) }}</td>
                        <td class="py-3.5 px-4 text-left font-mono text-brand-950" dir="ltr">${{ number_format($trial['total_credit'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
