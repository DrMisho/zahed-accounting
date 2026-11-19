@extends('layouts.app')

@section('title', 'فاتورة ' . $transaction->invoice_number)

@section('content')
<div class="max-w-xl mx-auto bg-white rounded-3xl p-8 border border-slate-200 shadow-lg space-y-6">
    <div class="flex items-center justify-between border-b pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">{{ $transaction->shop->name }}</h1>
            <p class="text-xs text-slate-500">فاتورة تجارية نظامية - زاهد للحلول المالية والمحاسبية</p>
        </div>
        <div class="text-left font-mono">
            <div class="text-base font-bold text-brand-800">{{ $transaction->invoice_number }}</div>
            <div class="text-xs text-slate-400">{{ $transaction->date->format('Y-m-d') }}</div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 text-xs text-slate-700">
        <div><strong>الطرف:</strong> {{ $transaction->contact->name }}</div>
        <div><strong>طريقة الدفع:</strong> {{ $transaction->payment_type === 'cash' ? 'نقدي (صندوق)' : 'آجل (ذمة)' }}</div>
        <div><strong>نوع الفاتورة:</strong> {{ $transaction->type === 'sale' ? 'فاتورة بيع' : 'فاتورة شراء' }}</div>
        <div><strong>سعر الصرف:</strong> <span class="font-mono">{{ number_format($transaction->exchange_rate) }} ل.س</span></div>
    </div>

    <table class="w-full text-right border-t border-b border-slate-200 text-xs">
        <thead>
            <tr class="py-2 text-slate-400">
                <th class="py-2">الصنف</th>
                <th class="py-2 text-center">الكمية</th>
                <th class="py-2 text-left">السعر</th>
                <th class="py-2 text-left">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transaction->lines as $line)
            <tr>
                <td class="py-3 font-semibold">{{ $line->item->item_name ?? '—' }}</td>
                <td class="py-3 text-center font-mono">{{ $line->qty }}</td>
                <td class="py-3 text-left font-mono">{{ number_format($line->unit_price) }}</td>
                <td class="py-3 text-left font-mono font-bold">{{ number_format($line->total) }} {{ $transaction->currency }}</td>
            </tr>
            @empty
            <tr>
                <td class="py-3 font-semibold">{{ $transaction->item->item_name }}</td>
                <td class="py-3 text-center font-mono">{{ $transaction->qty }}</td>
                <td class="py-3 text-left font-mono">{{ number_format($transaction->unit_price) }}</td>
                <td class="py-3 text-left font-mono font-bold">{{ number_format($transaction->total) }} {{ $transaction->currency }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="bg-slate-50 p-4 rounded-2xl space-y-1 text-xs">
        <div class="flex justify-between">
            <span>المجموع بالعملة:</span>
            <span class="font-mono font-bold text-sm">{{ number_format($transaction->total) }} {{ $transaction->currency }}</span>
        </div>
        <div class="flex justify-between border-t pt-2 font-bold text-brand-800 text-sm">
            <span>المعادل بالدولار (USD):</span>
            <span class="font-mono text-base">${{ number_format($transaction->total_usd, 2) }}</span>
        </div>
    </div>

    @if($transaction->notes)
        <div class="text-xs text-slate-500 italic">ملاحظات: {{ $transaction->notes }}</div>
    @endif

    <div class="no-print flex items-center justify-between pt-4 border-t border-slate-100">
        <button onclick="window.print()" class="no-print px-5 py-2 text-xs font-bold text-white bg-brand-900 hover:bg-brand-800 rounded-xl">
            طباعة الفاتورة 🖨️
        </button>
        <a href="{{ route('transactions.index') }}" class="text-xs text-slate-500 hover:text-slate-800">
            العودة للقائمة
        </a>
    </div>
</div>
@endsection
