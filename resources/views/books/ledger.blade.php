@extends('layouts.app')
@section('title', 'الأستاذ العام')
@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border">
        <h1 class="text-xl font-bold">الأستاذ العام</h1>
        <form class="mt-3 flex gap-2 text-xs">
            <select name="account_id" class="p-2 rounded-xl border">
                <option value="">كل الحسابات</option>
                @foreach($accounts as $a)
                    <option value="{{ $a->id }}" @selected($accountId == $a->id)>{{ $a->code }} {{ $a->name }}</option>
                @endforeach
            </select>
            <button class="px-3 py-2 bg-brand-900 text-white rounded-xl">عرض</button>
        </form>
        <form method="POST" action="{{ route('books.ledger.store') }}" class="mt-4 border-t pt-4 space-y-2 text-xs">
            @csrf
            <div class="font-bold">قيد يدوي (يجب التوازن)</div>
            <div class="grid grid-cols-3 gap-2">
                <input type="date" name="date" value="{{ date('Y-m-d') }}" class="p-2 rounded-xl border">
                <input name="document_number" required placeholder="رقم المستند" class="p-2 rounded-xl border">
                <input name="description" placeholder="البيان" class="p-2 rounded-xl border">
            </div>
            @for($i=0;$i<4;$i++)
            <div class="grid grid-cols-4 gap-2">
                <select name="lines[{{ $i }}][account_id]" class="p-2 rounded-xl border">
                    <option value="">حساب</option>
                    @foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} {{ $a->name }}</option>@endforeach
                </select>
                <input name="lines[{{ $i }}][debit]" type="number" step="0.01" placeholder="مدين" class="p-2 rounded-xl border">
                <input name="lines[{{ $i }}][credit]" type="number" step="0.01" placeholder="دائن" class="p-2 rounded-xl border">
                <input name="lines[{{ $i }}][description]" placeholder="بيان السطر" class="p-2 rounded-xl border">
            </div>
            @endfor
            <button class="px-4 py-2 bg-brand-800 text-white rounded-xl font-bold">حفظ القيد</button>
        </form>
    </div>
    <div class="bg-white rounded-2xl border overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50"><tr>
                <th class="p-3 text-right">التاريخ</th><th class="p-3">المستند</th><th class="p-3">الحساب</th>
                <th class="p-3">البيان</th><th class="p-3">مدين</th><th class="p-3">دائن</th><th class="p-3">رصيد</th>
            </tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr class="border-t">
                    <td class="p-3 font-mono">{{ $r['date'] }}</td>
                    <td class="p-3">{{ $r['document'] }}</td>
                    <td class="p-3">{{ $r['account']->code }} {{ $r['account']->name }}</td>
                    <td class="p-3">{{ $r['description'] }}</td>
                    <td class="p-3 font-mono">{{ number_format($r['debit'],2) }}</td>
                    <td class="p-3 font-mono">{{ number_format($r['credit'],2) }}</td>
                    <td class="p-3 font-mono font-bold">{{ number_format($r['balance'],2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-6 text-center text-slate-400">لا قيود بعد. فعّل الترحيل أو أدخل قيداً يدوياً.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
