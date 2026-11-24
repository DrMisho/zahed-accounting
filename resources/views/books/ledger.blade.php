@extends('layouts.app')

@section('title', 'الأستاذ العام والقيود اليومية')

@section('content')
<div class="space-y-5">
    <!-- Filter & Header Card -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">
                    دفتر الأستاذ العام والقيود اليومية
                    @if($selectedAccount)
                        <span class="text-brand-700 font-mono text-base mr-1">({{ $selectedAccount->code }} — {{ $selectedAccount->name }})</span>
                    @endif
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    جميع القيود متوازنة ومُرحّلة بالعملة الأساسية <strong class="text-brand-800">الدولار الأمريكي (USD)</strong> مع حفظ المبلغ الأصلي بالعملة الثانوية (ل.س) وسعر الصرف
                </p>
            </div>
            <div class="no-print flex flex-wrap items-center gap-2 text-xs">
                <button type="button" onclick="toggleManualJournalForm()" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl transition-colors whitespace-nowrap">
                    + قيد يومية يدوي
                </button>
                <a href="{{ route('books.trial') }}" class="px-3.5 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold rounded-xl whitespace-nowrap">
                    ميزان المراجعة
                </a>
                <button type="button" onclick="window.print()" class="px-3.5 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold rounded-xl whitespace-nowrap">
                    طباعة
                </button>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('books.ledger') }}" class="no-print grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5 text-xs">
            <div class="lg:col-span-2">
                <label for="ledger-acc" class="block text-[11px] font-semibold text-slate-600 mb-1">الحساب (رئيسي أو فرعي)</label>
                <select id="ledger-acc" name="account_id" class="w-full p-2.5 rounded-xl border border-slate-300 bg-white">
                    <option value="">كافة حسابات الشجرة</option>
                    @foreach($accounts as $a)
                        <option value="{{ $a->id }}" @selected($accountId == $a->id)>
                            {{ str_repeat('— ', max(0, ($a->level ?? 1) - 1)) }}{{ $a->code }} · {{ $a->name }}{{ !$a->is_postable ? ' (تجميعي)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="ledger-from" class="block text-[11px] font-semibold text-slate-600 mb-1">من تاريخ</label>
                <input id="ledger-from" type="date" name="from" value="{{ $from }}" class="w-full p-2.5 rounded-xl border border-slate-300 font-mono">
            </div>
            <div>
                <label for="ledger-to" class="block text-[11px] font-semibold text-slate-600 mb-1">إلى تاريخ</label>
                <input id="ledger-to" type="date" name="to" value="{{ $to }}" class="w-full p-2.5 rounded-xl border border-slate-300 font-mono">
            </div>
            <div class="flex items-end gap-1.5">
                <button type="submit" class="flex-1 py-2.5 px-4 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl">عرض</button>
                <a href="{{ route('books.ledger') }}" class="py-2.5 px-3 border border-slate-300 text-slate-600 hover:bg-slate-50 rounded-xl">مسح</a>
            </div>
        </form>

        <!-- Manual Balanced Multi-Currency Journal Entry Panel -->
        <form id="manual-journal-panel" method="POST" action="{{ route('books.ledger.store') }}" class="{{ $errors->any() ? '' : 'hidden' }} no-print mt-4 border-t border-slate-200 pt-5 space-y-4 text-xs">
            @csrf
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">إدخال قيد يومية يدوي متوازن (USD أساسي / SYP ثانوي)</h2>
                    <p class="text-[11px] text-slate-500">يمكن إدخال أي سطر بالدولار أو بالليرة السورية، ويقوم النظام بتحويله تلقائياً والتحقق من توازنه بالدولار قبل الحفظ.</p>
                </div>
                <button type="button" onclick="addJournalLineRow()" class="px-3 py-1.5 rounded-lg border border-brand-300 bg-brand-50 text-brand-800 hover:bg-brand-100 font-bold self-start">
                    + إضافة سطر
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-2.5">
                <div>
                    <label for="mj-date" class="block text-[11px] font-semibold text-slate-600 mb-1">التاريخ</label>
                    <input id="mj-date" type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required class="w-full p-2 rounded-xl border border-slate-300 font-mono">
                </div>
                <div>
                    <label for="mj-doc" class="block text-[11px] font-semibold text-slate-600 mb-1">رقم المستند</label>
                    <input id="mj-doc" name="document_number" value="{{ old('document_number', 'JV-' . date('Ymd-His')) }}" required class="w-full p-2 rounded-xl border border-slate-300 font-mono">
                </div>
                <div>
                    <label for="mj-rate" class="block text-[11px] font-semibold text-slate-600 mb-1">سعر الصرف (لأسطر الليرة)</label>
                    <input id="mj-rate" type="number" step="1" min="1" name="exchange_rate" value="{{ old('exchange_rate', (float) $shop->exchange_rate) }}" oninput="recalcJournalBalance()" class="w-full p-2 rounded-xl border border-slate-300 font-mono">
                </div>
                <div>
                    <label for="mj-desc" class="block text-[11px] font-semibold text-slate-600 mb-1">البيان العام للقيد</label>
                    <input id="mj-desc" name="description" value="{{ old('description') }}" placeholder="مثال: قيد تسوية أو إهلاك..." class="w-full p-2 rounded-xl border border-slate-300">
                </div>
            </div>

            <div id="journal-lines-container" class="space-y-2">
                @for($i = 0; $i < 4; $i++)
                    <div class="mj-row grid grid-cols-12 gap-2 items-center">
                        <div class="col-span-12 sm:col-span-4">
                            <select name="lines[{{ $i }}][account_id]" class="w-full p-2 rounded-xl border border-slate-300 bg-white">
                                <option value="">اختر حساباً فرعياً...</option>
                                @foreach($postableAccounts as $a)
                                    <option value="{{ $a->id }}">{{ $a->code }} · {{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-4 sm:col-span-2">
                            <select name="lines[{{ $i }}][currency]" onchange="recalcJournalBalance()" class="mj-cur w-full p-2 rounded-xl border border-slate-300 bg-white font-mono">
                                <option value="USD">USD ($)</option>
                                <option value="SYP">SYP (ل.س)</option>
                            </select>
                        </div>
                        <div class="col-span-4 sm:col-span-2">
                            <input name="lines[{{ $i }}][debit]" type="number" step="0.01" min="0" placeholder="مدين" oninput="recalcJournalBalance()" class="mj-debit w-full p-2 rounded-xl border border-slate-300 font-mono text-left">
                        </div>
                        <div class="col-span-4 sm:col-span-2">
                            <input name="lines[{{ $i }}][credit]" type="number" step="0.01" min="0" placeholder="دائن" oninput="recalcJournalBalance()" class="mj-credit w-full p-2 rounded-xl border border-slate-300 font-mono text-left">
                        </div>
                        <div class="col-span-12 sm:col-span-2">
                            <input name="lines[{{ $i }}][description]" placeholder="بيان السطر" class="w-full p-2 rounded-xl border border-slate-300">
                        </div>
                    </div>
                @endfor
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <div class="flex flex-wrap items-center gap-4 font-mono">
                    <span>مدين USD: <strong id="mj-sum-debit" class="text-slate-900" dir="ltr">$0.00</strong></span>
                    <span>دائن USD: <strong id="mj-sum-credit" class="text-slate-900" dir="ltr">$0.00</strong></span>
                    <span id="mj-diff-status" class="font-sans font-bold text-slate-500">أدخل طرفي القيد</span>
                </div>
                <button type="submit" id="mj-submit-btn" class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl transition-colors">
                    ترحيل القيد اليدوي
                </button>
            </div>
        </form>
    </div>

    <!-- Ledger Summary Strip -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
        <div class="bg-white rounded-xl p-4 border border-slate-200">
            <span class="block text-[11px] text-slate-500">مجموع الحركات المدينة (USD)</span>
            <strong class="block mt-1 text-lg font-mono font-bold text-slate-900" dir="ltr">${{ number_format($totalDebit, 2) }}</strong>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-200">
            <span class="block text-[11px] text-slate-500">مجموع الحركات الدائنة (USD)</span>
            <strong class="block mt-1 text-lg font-mono font-bold text-slate-900" dir="ltr">${{ number_format($totalCredit, 2) }}</strong>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-200">
            <span class="block text-[11px] text-slate-500">عدد أسطر الأستاذ المعروضة</span>
            <strong class="block mt-1 text-lg font-mono font-bold text-brand-800">{{ count($rows) }}</strong>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                        <th class="py-3 px-3">التاريخ</th>
                        <th class="py-3 px-3">المستند · المصدر</th>
                        <th class="py-3 px-3">الحساب</th>
                        <th class="py-3 px-3">البيان</th>
                        <th class="py-3 px-3 text-left">مدين ($)</th>
                        <th class="py-3 px-3 text-left">دائن ($)</th>
                        <th class="py-3 px-3 text-left">الرصيد ($)</th>
                        <th class="py-3 px-3 text-left">العملة الثانوية (ل.س)</th>
                        <th class="py-3 px-3 text-center no-print">إجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $r)
                        @php
                            $sypAmt = max((float) $r['foreign_debit'], (float) $r['foreign_credit']);
                            $isSyp = $r['currency'] === 'SYP' && $sypAmt > 0;
                        @endphp
                        <tr class="hover:bg-slate-50/70">
                            <td class="py-2.5 px-3 font-mono text-slate-500 whitespace-nowrap">{{ $r['date'] }}</td>
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <span class="font-mono font-semibold text-slate-800">{{ $r['document'] }}</span>
                                <span class="block text-[11px] text-slate-400">{{ $r['source_label'] }}</span>
                            </td>
                            <td class="py-2.5 px-3">
                                <a href="{{ route('books.ledger', ['account_id' => $r['account']->id]) }}" class="font-semibold text-slate-800 hover:text-brand-700 hover:underline">
                                    <span class="font-mono text-slate-500">{{ $r['account']->code }}</span> {{ $r['account']->name }}
                                </a>
                            </td>
                            <td class="py-2.5 px-3 text-slate-600">
                                {{ $r['description'] }}
                                @if($r['contact_name'])
                                    <span class="text-[11px] text-slate-400">· {{ $r['contact_name'] }}</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-left font-mono whitespace-nowrap {{ $r['debit'] > 0 ? 'text-slate-900 font-semibold' : 'text-slate-300' }}" dir="ltr">
                                {{ $r['debit'] > 0 ? '$' . number_format($r['debit'], 2) : '—' }}
                            </td>
                            <td class="py-2.5 px-3 text-left font-mono whitespace-nowrap {{ $r['credit'] > 0 ? 'text-slate-900 font-semibold' : 'text-slate-300' }}" dir="ltr">
                                {{ $r['credit'] > 0 ? '$' . number_format($r['credit'], 2) : '—' }}
                            </td>
                            <td class="py-2.5 px-3 text-left font-mono font-bold whitespace-nowrap {{ $r['balance'] < 0 ? 'text-rose-700' : 'text-brand-900' }}" dir="ltr">
                                {{ $r['balance'] < 0 ? '-' : '' }}${{ number_format(abs($r['balance']), 2) }}
                            </td>
                            <td class="py-2.5 px-3 text-left font-mono text-[11px] whitespace-nowrap text-slate-500" dir="ltr">
                                @if($isSyp)
                                    {{ number_format($sypAmt) }} SYP <span class="text-slate-400">(@ {{ number_format($r['exchange_rate']) }})</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-center whitespace-nowrap no-print">
                                @if(in_array($r['source_type'], ['manual', 'fx_revaluation'], true))
                                    <form method="POST" action="{{ route('books.ledger.destroy', $r['entry_id']) }}" onsubmit="return confirm('حذف هذا القيد بالكامل من دفتر الأستاذ؟')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:underline font-semibold">حذف</button>
                                    </form>
                                @else
                                    <span class="text-[11px] text-slate-400">تلقائي</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-slate-400">
                                لا توجد قيود مطابقة لمعايير التصفية. يمكنك إضافة قيد يدوي أو
                                <a href="{{ route('books.accounts') }}" class="text-brand-700 font-semibold underline">إعادة بناء القيود التلقائية من شجرة الحسابات</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    let mjRowIndex = 4;

    function toggleManualJournalForm() {
        document.getElementById('manual-journal-panel')?.classList.toggle('hidden');
    }

    function addJournalLineRow() {
        const container = document.getElementById('journal-lines-container');
        const firstRow = container.querySelector('.mj-row');
        if (!firstRow) return;
        const clone = firstRow.cloneNode(true);
        const idx = mjRowIndex++;
        clone.querySelectorAll('select, input').forEach(el => {
            el.name = el.name.replace(/\[\d+\]/, '[' + idx + ']');
            if (el.tagName === 'SELECT') el.selectedIndex = 0;
            else el.value = '';
        });
        container.appendChild(clone);
    }

    function recalcJournalBalance() {
        const rate = Math.max(1, parseFloat(document.getElementById('mj-rate')?.value || 14500));
        let sumD = 0;
        let sumC = 0;
        document.querySelectorAll('#journal-lines-container .mj-row').forEach(row => {
            const cur = row.querySelector('.mj-cur')?.value || 'USD';
            const d = parseFloat(row.querySelector('.mj-debit')?.value || 0);
            const c = parseFloat(row.querySelector('.mj-credit')?.value || 0);
            sumD += cur === 'SYP' ? (d / rate) : d;
            sumC += cur === 'SYP' ? (c / rate) : c;
        });

        document.getElementById('mj-sum-debit').textContent = '$' + sumD.toFixed(2);
        document.getElementById('mj-sum-credit').textContent = '$' + sumC.toFixed(2);

        const diff = Math.abs(sumD - sumC);
        const statusEl = document.getElementById('mj-diff-status');
        if (sumD === 0 && sumC === 0) {
            statusEl.textContent = 'أدخل طرفي القيد';
            statusEl.className = 'font-sans font-bold text-slate-500';
        } else if (diff <= 0.02) {
            statusEl.textContent = 'القيد متوازن (جاهز للترحيل)';
            statusEl.className = 'font-sans font-bold text-brand-700';
        } else {
            statusEl.textContent = 'غير متوازن — الفرق $' + diff.toFixed(2);
            statusEl.className = 'font-sans font-bold text-rose-600';
        }
    }
</script>
@endsection
