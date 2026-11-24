@extends('layouts.app')
@section('title', 'ربط الحسابات')
@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h1 class="text-xl font-bold text-slate-900">ربط أدوار الترحيل التلقائي بشجرة الحسابات</h1>
                <p class="text-xs text-slate-500 mt-1">الترحيل التلقائي مفعّل بشكل دائم بالعملة الأساسية (USD). أدوار غير مربوطة: {{ $status['missing_required'] }}</p>
            </div>
            <a href="{{ route('books.accounts') }}" class="px-3.5 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold self-start">شجرة الحسابات</a>
        </div>
        <form method="POST" action="{{ route('books.mappings.save') }}" class="mt-4 space-y-2">
            @csrf
            @foreach($status['rows'] as $row)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs items-center border-b border-slate-100 py-2.5">
                    <div>
                        <div class="font-bold text-slate-800">{{ $row['label'] }}</div>
                        <div class="text-slate-400 font-mono text-[11px]">{{ $row['role'] }} {{ $row['required'] ? '(إلزامي)' : '' }}</div>
                    </div>
                    <select name="role[{{ $row['role'] }}]" class="p-2 rounded-xl border border-slate-300 bg-white">
                        <option value="">— غير محدد —</option>
                        @foreach($accounts as $a)
                            <option value="{{ $a->id }}" @selected($row['account']?->id == $a->id)>{{ $a->code }} — {{ $a->name }}</option>
                        @endforeach
                    </select>
                    <span class="font-semibold {{ $row['ok'] ? 'text-brand-700' : 'text-amber-700' }}">{{ $row['ok'] ? 'مربوط وجاهز' : 'غير محدد' }}</span>
                </div>
            @endforeach
            <div class="flex gap-2 pt-3">
                <button type="submit" class="px-5 py-2 bg-brand-700 hover:bg-brand-800 text-white rounded-xl text-xs font-bold">حفظ الربط</button>
            </div>
        </form>
    </div>
</div>
@endsection
