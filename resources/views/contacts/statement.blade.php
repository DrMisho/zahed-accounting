@extends('layouts.app')

@section('title', 'كشف حساب ' . $statement['contact']->name)

@section('content')
<div class="max-w-3xl mx-auto bg-white rounded-3xl p-8 border border-slate-200 shadow-sm space-y-6">
    <div class="flex items-center justify-between border-b pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">{{ $shop->name }}</h1>
            <p class="text-xs text-slate-500">
                كشف حساب تفصيلي: {{ $statement['contact']->name }} ({{ $statement['contact']->type === 'customer' ? 'عميل' : 'مورد' }})
            </p>
        </div>
        <div class="text-left font-mono text-xs text-slate-400">
            تاريخ التوليد: {{ date('Y-m-d') }}
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                    <th class="py-3 px-3">التاريخ</th>
                    <th class="py-3 px-3">البيان والحركة</th>
                    <th class="py-3 px-3 text-left">مدين</th>
                    <th class="py-3 px-3 text-left">دائن</th>
                    <th class="py-3 px-3 text-left">عملة</th>
                    <th class="py-3 px-3 text-left">رصيد $</th>
                    <th class="py-3 px-3 text-left">رصيد ل.س</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($statement['rows'] as $row)
                    <tr class="hover:bg-slate-50">
                        <td class="py-2.5 px-3 font-mono text-slate-500">{{ $row['date'] }}</td>
                        <td class="py-2.5 px-3">{{ $row['description'] }}</td>
                        <td class="py-2.5 px-3 text-left font-mono text-brand-800">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '—' }}</td>
                        <td class="py-2.5 px-3 text-left font-mono text-rose-600">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '—' }}</td>
                        <td class="py-2.5 px-3 text-left font-mono">{{ $row['currency'] }}</td>
                        <td class="py-2.5 px-3 text-left font-mono font-bold">${{ number_format($row['balance_usd'], 2) }}</td>
                        <td class="py-2.5 px-3 text-left font-mono">{{ number_format($row['balance_syp']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-400">لا توجد حركات آجلة أو دفعات مسجلة لهذا الحساب.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4 bg-slate-50 rounded-2xl flex justify-between items-center text-sm">
        <span class="font-bold text-slate-700">الرصيد النهائي المستحق:</span>
        <span class="font-mono font-extrabold text-lg text-slate-900">${{ number_format($statement['final_balance_usd'], 2) }} · {{ number_format($statement['final_balance_syp']) }} ل.س</span>
    </div>

    <div class="no-print flex items-center justify-between pt-2">
        <button onclick="window.print()" class="no-print px-5 py-2 text-xs font-bold text-white bg-brand-900 hover:bg-brand-800 rounded-xl">
            طباعة كشف الحساب 🖨️
        </button>
        <a href="{{ route('contacts.index', ['type' => $statement['contact']->type]) }}" class="text-xs text-slate-500 hover:text-slate-800">
            العودة للقائمة
        </a>
    </div>
</div>
@endsection
