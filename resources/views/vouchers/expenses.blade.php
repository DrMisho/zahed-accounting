@extends('layouts.app')
@section('title', 'المصاريف العامة')
@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm text-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h1 class="text-xl font-bold text-slate-900">المصاريف العامة والتشغيلية</h1>
                <p class="text-[11px] text-slate-500 mt-0.5">يُرحَّل كل مصروف تلقائياً إلى حساب المصروف المحدد في شجرة الحسابات (أو يُصنَّف تلقائياً حسب النوع) ويظهر أثره مباشرة في الأستاذ العام والأرباح والخسائر.</p>
            </div>
            <a href="{{ route('books.accounts') }}" class="px-3 py-1.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold self-start">شجرة الحسابات</a>
        </div>
        <form method="POST" action="{{ route('expenses.store') }}" class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
            @csrf
            <input type="date" name="date" value="{{ date('Y-m-d') }}" class="p-2.5 rounded-xl border border-slate-300 font-mono">
            <input name="voucher_number" placeholder="رقم السند" class="p-2.5 rounded-xl border border-slate-300">
            <input name="expense_type" required placeholder="نوع المصروف (إيجار، كهرباء، صيانة...)" class="p-2.5 rounded-xl border border-slate-300">
            <select name="account_id" class="p-2.5 rounded-xl border border-slate-300">
                <option value="">الحساب المحاسبي: تحديد تلقائي حسب النوع</option>
                @foreach(($expenseAccounts ?? []) as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                @endforeach
            </select>
            <input name="amount" type="number" step="0.01" required placeholder="المبلغ" class="p-2.5 rounded-xl border border-slate-300 font-mono">
            <select name="currency" class="p-2.5 rounded-xl border border-slate-300">
                <option value="USD">دولار (USD — العملة الأساسية)</option>
                <option value="SYP">ليرة سورية (SYP — ثانوية)</option>
            </select>
            <input name="exchange_rate" type="number" step="0.01" value="{{ $shop->exchange_rate }}" class="p-2.5 rounded-xl border border-slate-300 font-mono">
            <select name="cost_center_id" class="p-2.5 rounded-xl border border-slate-300">
                <option value="">مركز تكلفة (اختياري)</option>
                @foreach($centers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
            </select>
            <input name="description" placeholder="البيان / تفاصيل المصروف" class="p-2.5 rounded-xl border border-slate-300 sm:col-span-2 lg:col-span-3">
            <button type="submit" class="px-4 py-2.5 bg-brand-700 hover:bg-brand-800 text-white rounded-xl font-bold">حفظ وترحيل القيد</button>
        </form>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-x-auto">
        <table class="w-full text-xs text-right">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                <tr>
                    <th class="p-3">التاريخ</th>
                    <th class="p-3">نوع المصروف</th>
                    <th class="p-3">حساب الأستاذ</th>
                    <th class="p-3 text-left">المبلغ الأصلي</th>
                    <th class="p-3 text-left">المعادل (USD)</th>
                    <th class="p-3">مركز التكلفة</th>
                    <th class="p-3">البيان</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $r)
                    <tr class="hover:bg-slate-50/80">
                        <td class="p-3 font-mono text-slate-500 whitespace-nowrap">{{ $r->date->format('Y-m-d') }}</td>
                        <td class="p-3 font-semibold text-slate-800">{{ $r->expense_type }}</td>
                        <td class="p-3">
                            @if($r->account)
                                <a href="{{ route('books.ledger', ['account_id' => $r->account->id]) }}" class="font-mono text-brand-700 hover:underline font-semibold">{{ $r->account->code }}</a>
                                <span class="text-slate-600 mr-1">{{ $r->account->name }}</span>
                            @else
                                <span class="text-slate-400">تلقائي</span>
                            @endif
                        </td>
                        <td class="p-3 text-left font-mono whitespace-nowrap" dir="ltr">
                            {{ number_format((float) $r->amount, $r->currency === 'USD' ? 2 : 0) }} {{ $r->currency }}
                        </td>
                        <td class="p-3 text-left font-mono font-bold text-slate-900 whitespace-nowrap" dir="ltr">
                            ${{ number_format((float) $r->amount_usd, 2) }}
                        </td>
                        <td class="p-3 text-slate-600">{{ $r->costCenter->name ?? '—' }}</td>
                        <td class="p-3 text-slate-500">{{ $r->description ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400">لا توجد مصاريف مسجلة بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
