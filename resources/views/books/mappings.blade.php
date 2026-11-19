@extends('layouts.app')
@section('title', 'ربط الحسابات')
@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border">
        <h1 class="text-xl font-bold">إعداد ربط الحسابات</h1>
        <p class="text-xs text-slate-500 mt-1">صفحة إعداد فقط. الترحيل الآلي {{ $shop->posting_enabled ? 'مفعّل' : 'غير مفعّل' }}. أدوار غير مربوطة: {{ $status['missing_required'] }}</p>
        <form method="POST" action="{{ route('books.mappings.save') }}" class="mt-4 space-y-2">
            @csrf
            @foreach($status['rows'] as $row)
                <div class="grid grid-cols-3 gap-2 text-xs items-center border-b py-2">
                    <div>
                        <div class="font-bold">{{ $row['label'] }}</div>
                        <div class="text-slate-400">{{ $row['role'] }} {{ $row['required'] ? '(إلزامي)' : '' }}</div>
                    </div>
                    <select name="role[{{ $row['role'] }}]" class="p-2 rounded-xl border">
                        <option value="">— غير محدد —</option>
                        @foreach($accounts as $a)
                            <option value="{{ $a->id }}" @selected($row['account']?->id == $a->id)>{{ $a->code }} — {{ $a->name }}</option>
                        @endforeach
                    </select>
                    <span class="{{ $row['ok'] ? 'text-brand-800' : 'text-gold-700' }}">{{ $row['ok'] ? 'مربوط' : 'لم يُحدد' }}</span>
                </div>
            @endforeach
            <div class="flex gap-2 pt-3">
                <button class="px-4 py-2 bg-brand-800 text-white rounded-xl text-xs font-bold">حفظ الربط</button>
                @if($status['ready'] && !$shop->posting_enabled)
                    <button name="enable_posting" value="1" class="px-4 py-2 bg-brand-900 text-white rounded-xl text-xs font-bold">تفعيل الترحيل الآلي</button>
                @endif
                @if($shop->posting_enabled)
                    <button name="disable_posting" value="1" class="px-4 py-2 border rounded-xl text-xs">إيقاف الترحيل</button>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection
