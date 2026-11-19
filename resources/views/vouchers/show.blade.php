@extends('layouts.app')
@section('title', 'سند '.$voucher->number)
@section('content')
<div class="max-w-lg mx-auto bg-white rounded-2xl p-8 border space-y-4">
    <div class="flex justify-between">
        <div>
            <h1 class="text-xl font-bold">{{ $shop->name }}</h1>
            <p class="text-xs text-slate-500">{{ $voucher->type === 'receipt' ? 'سند قبض' : 'سند صرف' }}</p>
        </div>
        <div class="font-mono font-bold">{{ $voucher->number }}</div>
    </div>
    <div class="text-sm space-y-1">
        <div>التاريخ: {{ $voucher->date->format('Y-m-d') }}</div>
        <div>الجهة: {{ $voucher->contact->name ?? $voucher->beneficiary }}</div>
        <div>المبلغ: <strong>{{ number_format($voucher->amount,2) }} {{ $voucher->currency }}</strong></div>
        <div>المعادل: ${{ number_format($voucher->amount_usd,2) }}</div>
        <div>طريقة السداد: {{ $voucher->payment_method }}</div>
        <div>المرجع: {{ $voucher->original_ref ?: '—' }}</div>
        <div>البيان: {{ $voucher->description }}</div>
        <div>الأثر: {{ $voucher->affects_balance ? 'يؤثر على الذمة والصندوق' : 'توثيق رقابي فقط' }}</div>
    </div>
    <div class="no-print flex gap-2">
        <a href="{{ route('vouchers.index', ['type' => $voucher->type]) }}" class="px-4 py-2 bg-brand-900 text-white rounded-xl text-xs">تعديل من القائمة</a>
        <button onclick="window.print()" class="px-4 py-2 bg-brand-900 text-white rounded-xl text-xs">طباعة</button>
    </div>
</div>
@endsection
