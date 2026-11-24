@extends('layouts.app')

@section('title', 'شجرة الحسابات')

@section('content')
@php
    $summary = $tree['summary'];
    $rows = $tree['rows'];
@endphp
<div class="space-y-5">
    <!-- Header & Primary Actions -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 space-y-5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">شجرة الحسابات القياسية (Chart of Accounts)</h1>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $shop->name }} · العملة الأساسية: <strong class="text-brand-800">الدولار الأمريكي (USD)</strong> ·
                    العملة الثانوية: الليرة السورية (SYP · <span class="font-mono font-semibold text-slate-700" dir="ltr">{{ number_format((float) $shop->exchange_rate) }}</span> ل.س) ·
                    الترحيل التلقائي مفعّل دائماً
                </p>
            </div>
            <div class="no-print flex flex-wrap items-center gap-2 text-xs">
                <button type="button" onclick="openAccountModal()" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl transition-colors whitespace-nowrap">
                    + إضافة حساب جديد
                </button>
                <form method="POST" action="{{ route('books.rebuild') }}" onsubmit="return confirm('سيتم إعادة بناء وترحيل كافة الفواتير والسندات والمصاريف والرواتب وتحويلات العملة بالعملة الأساسية (USD) مع الحفاظ على القيود اليدوية. متابعة؟')" class="inline">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 border border-brand-300 bg-brand-50 hover:bg-brand-100 text-brand-900 font-semibold rounded-xl transition-colors whitespace-nowrap">
                        إعادة بناء القيود التلقائية
                    </button>
                </form>
                @if(!empty($glFxPreview['needs_revaluation']))
                    <form method="POST" action="{{ route('books.revalue-fx') }}" onsubmit="return confirm('سيتم ترحيل قيد إعادة تقييم أرصدة الليرة السورية وفق سعر الصرف المعتمد حالياً. متابعة؟')" class="inline">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-900 font-semibold rounded-xl transition-colors whitespace-nowrap">
                            قيد إعادة تقييم الصرف (<span class="font-mono inline-block" dir="ltr">{{ $glFxPreview['net_adjustment_usd'] >= 0 ? '+' : '-' }}${{ number_format(abs($glFxPreview['net_adjustment_usd']), 2) }}</span>)
                        </button>
                    </form>
                @endif
                <button type="button" onclick="window.print()" class="px-3.5 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold rounded-xl transition-colors whitespace-nowrap">
                    طباعة
                </button>
            </div>
        </div>

        <!-- Five Main Roots Rolled-Up Summary (Base USD) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 text-xs">
            <div class="p-4 rounded-xl bg-brand-50/70 border border-brand-200/80">
                <span class="block text-[11px] font-semibold text-brand-800">1 · إجمالي الأصول</span>
                <strong class="block mt-1 text-lg font-mono font-extrabold text-brand-950" dir="ltr">
                    {{ $summary['assets'] < 0 ? '-' : '' }}${{ number_format(abs($summary['assets']), 2) }}
                </strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] font-semibold text-slate-600">2 · إجمالي الالتزامات</span>
                <strong class="block mt-1 text-lg font-mono font-bold text-rose-700" dir="ltr">
                    {{ $summary['liabilities'] < 0 ? '-' : '' }}${{ number_format(abs($summary['liabilities']), 2) }}
                </strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] font-semibold text-slate-600">3 · حقوق الملكية</span>
                <strong class="block mt-1 text-lg font-mono font-bold text-slate-900" dir="ltr">
                    {{ $summary['equity'] < 0 ? '-' : '' }}${{ number_format(abs($summary['equity']), 2) }}
                </strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] font-semibold text-slate-600">4 · الإيرادات</span>
                <strong class="block mt-1 text-lg font-mono font-bold text-brand-700" dir="ltr">
                    {{ $summary['revenues'] < 0 ? '-' : '' }}${{ number_format(abs($summary['revenues']), 2) }}
                </strong>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="block text-[11px] font-semibold text-slate-600">5 · التكاليف والمصاريف</span>
                <strong class="block mt-1 text-lg font-mono font-bold text-slate-900" dir="ltr">
                    {{ $summary['expenses'] < 0 ? '-' : '' }}${{ number_format(abs($summary['expenses']), 2) }}
                </strong>
            </div>
        </div>

        <!-- Search, Tree Controls & Date Range Filter -->
        <div class="no-print flex flex-col md:flex-row md:items-center justify-between gap-3 pt-1 text-xs">
            <div class="flex flex-wrap items-center gap-2 flex-1">
                <input type="text" id="coa-search" placeholder="ابحث برقم أو اسم الحساب..." class="w-full sm:w-64 px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:border-brand-600">
                <button type="button" onclick="toggleAllCoa(true)" class="px-3 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold whitespace-nowrap">توسيع الكل</button>
                <button type="button" onclick="toggleAllCoa(false)" class="px-3 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold whitespace-nowrap">طي الفروع</button>
            </div>
            <form method="GET" action="{{ route('books.accounts') }}" class="flex flex-wrap items-center gap-2">
                <input type="date" name="from" value="{{ $from }}" class="px-2.5 py-2 rounded-xl border border-slate-300 font-mono" title="من تاريخ">
                <input type="date" name="to" value="{{ $to }}" class="px-2.5 py-2 rounded-xl border border-slate-300 font-mono" title="إلى تاريخ">
                <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold rounded-xl whitespace-nowrap">تصفية</button>
                @if($from || $to)
                    <a href="{{ route('books.accounts') }}" class="px-3 py-2 border border-slate-300 text-slate-600 hover:bg-slate-50 rounded-xl whitespace-nowrap">الكل</a>
                @endif
            </form>
        </div>
    </div>

    <!-- Collapsible Hierarchical Chart of Accounts Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs" id="coa-tree-table">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                        <th class="py-3 px-4 w-32">رمز الحساب</th>
                        <th class="py-3 px-4">اسم الحساب والشجرة الهرمية</th>
                        <th class="py-3 px-3">القائمة / الطبيعة</th>
                        <th class="py-3 px-3 text-center">العملة</th>
                        <th class="py-3 px-4 text-left">الرصيد التراكمي (USD)</th>
                        <th class="py-3 px-4 text-left">العملة الثانوية (SYP)</th>
                        <th class="py-3 px-4 text-center no-print">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($rows as $node)
                        @php
                            $acc = $node['account'];
                            $depth = $node['depth'];
                            $isGroup = !$acc->is_postable;
                            $balUsd = (float) $node['rollup_display_usd'];
                            $balSyp = (float) $node['rollup_syp'];
                            $rowBg = match($depth) {
                                0 => 'bg-brand-950/[0.04] font-extrabold text-slate-950',
                                1 => 'bg-slate-50/80 font-bold text-slate-900',
                                2 => 'font-semibold text-slate-800',
                                default => 'text-slate-700 hover:bg-slate-50/70',
                            };
                            $indentPx = $depth * 22;
                        @endphp
                        <tr class="coa-row {{ $rowBg }}"
                            data-id="{{ $acc->id }}"
                            data-parent="{{ $acc->parent_id ?? 0 }}"
                            data-depth="{{ $depth }}"
                            data-search="{{ mb_strtolower($acc->code . ' ' . $acc->name . ' ' . ($acc->classification ?? '')) }}">
                            <td class="py-2.5 px-4 font-mono whitespace-nowrap {{ $depth === 0 ? 'text-brand-900 text-sm' : ($isGroup ? 'text-slate-800' : 'text-slate-600') }}">
                                {{ $acc->code }}
                            </td>
                            <td class="py-2.5 px-4">
                                <div class="flex items-center gap-2" style="padding-right: {{ $indentPx }}px">
                                    @if($node['has_children'])
                                        <button type="button"
                                                onclick="toggleCoaBranch({{ $acc->id }})"
                                                data-toggle-id="{{ $acc->id }}"
                                                aria-expanded="true"
                                                class="no-print w-5 h-5 inline-flex items-center justify-center rounded border border-slate-300 bg-white text-slate-600 hover:text-brand-700 hover:border-brand-400 shrink-0 font-mono text-[11px]">
                                            −
                                        </button>
                                    @else
                                        <span class="w-5 inline-block text-center text-slate-300 shrink-0">└</span>
                                    @endif
                                    <a href="{{ route('books.ledger', ['account_id' => $acc->id]) }}" class="hover:text-brand-700 hover:underline">
                                        {{ $acc->name }}
                                    </a>
                                    @if($isGroup)
                                        <span class="text-[10px] font-normal text-slate-400">· تجميعي</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-2.5 px-3 text-slate-500 whitespace-nowrap">
                                {{ $acc->statementLabel() }} · {{ $acc->normalBalanceLabel() }}
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono text-slate-600 whitespace-nowrap">
                                {{ $acc->currency ?: 'USD' }}
                            </td>
                            <td class="py-2.5 px-4 text-left font-mono whitespace-nowrap {{ abs($balUsd) < 0.01 ? 'text-slate-400' : ($balUsd < 0 ? 'text-rose-700 font-bold' : ($depth <= 1 ? 'text-brand-950 font-extrabold' : 'text-slate-900 font-semibold')) }}" dir="ltr">
                                {{ $balUsd < 0 ? '-' : '' }}${{ number_format(abs($balUsd), 2) }}
                            </td>
                            <td class="py-2.5 px-4 text-left font-mono whitespace-nowrap {{ abs($balSyp) >= 1 ? 'text-slate-700' : 'text-slate-300' }}" dir="ltr">
                                {{ abs($balSyp) >= 1 ? (number_format($balSyp) . ' ل.س') : '—' }}
                            </td>
                            <td class="py-2.5 px-4 text-center whitespace-nowrap no-print">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('books.ledger', ['account_id' => $acc->id]) }}" class="text-brand-700 hover:underline font-semibold">أستاذ</a>
                                    @if($isGroup)
                                        <button type="button" onclick="openAccountModal({{ $acc->id }})" class="text-slate-600 hover:text-brand-700 font-semibold">+ فرعي</button>
                                    @else
                                        <button type="button"
                                                onclick='openEditAccountModal(@json(["id" => $acc->id, "code" => $acc->code, "name" => $acc->name, "classification" => $acc->classification, "currency" => $acc->currency ?: "USD", "opening_debit" => (float) $acc->opening_debit, "opening_credit" => (float) $acc->opening_credit]))'
                                                class="text-slate-600 hover:text-brand-700 font-semibold">تعديل</button>
                                        @if(!$acc->is_system && $node['lines_count'] === 0)
                                            <form method="POST" action="{{ route('books.accounts.destroy', $acc) }}" onsubmit="return confirm('حذف الحساب {{ $acc->name }}؟')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:underline font-semibold">حذف</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Account Modal -->
<div id="add-account-modal" class="hidden fixed inset-0 z-50 bg-slate-950/50 flex items-center justify-center p-4 no-print">
    <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-lg p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900">إضافة حساب جديد إلى شجرة الحسابات</h2>
            <button type="button" onclick="closeAccountModal()" class="text-slate-400 hover:text-slate-700 text-lg font-bold px-2">×</button>
        </div>
        <form method="POST" action="{{ route('books.accounts.store') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label for="add-parent-id" class="block font-semibold text-slate-700 mb-1">الحساب الأب (الرئيسي التجميعي)</label>
                <select id="add-parent-id" name="parent_id" required onchange="suggestAccountCode()" class="w-full p-2.5 rounded-xl border border-slate-300 bg-white">
                    @foreach($parents as $p)
                        <option value="{{ $p->id }}">{{ str_repeat('— ', max(0, $p->level - 1)) }}{{ $p->code }} · {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="add-code" class="block font-semibold text-slate-700 mb-1">رمز الحساب</label>
                    <input id="add-code" name="code" required class="w-full p-2.5 rounded-xl border border-slate-300 font-mono">
                </div>
                <div>
                    <label for="add-currency" class="block font-semibold text-slate-700 mb-1">عملة الحساب</label>
                    <select id="add-currency" name="currency" class="w-full p-2.5 rounded-xl border border-slate-300 bg-white">
                        <option value="USD">USD (العملة الأساسية)</option>
                        <option value="SYP">SYP (ليرة سورية - ثانوية)</option>
                    </select>
                </div>
            </div>
            <div>
                <label for="add-name" class="block font-semibold text-slate-700 mb-1">اسم الحساب</label>
                <input id="add-name" name="name" required placeholder="مثال: مصاريف اشتراكات وبرامج" class="w-full p-2.5 rounded-xl border border-slate-300">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="add-classification" class="block font-semibold text-slate-700 mb-1">التصنيف (اختياري)</label>
                    <input id="add-classification" name="classification" placeholder="مثال: مصاريف إدارية" class="w-full p-2.5 rounded-xl border border-slate-300">
                </div>
                <div>
                    <label for="add-postable" class="block font-semibold text-slate-700 mb-1">نوع العقدة</label>
                    <select id="add-postable" name="is_postable" class="w-full p-2.5 rounded-xl border border-slate-300 bg-white">
                        <option value="1" selected>حساب فرعي (قابل للترحيل)</option>
                        <option value="0">حساب رئيسي (تجميعي للأبناء)</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="add-op-debit" class="block font-semibold text-slate-700 mb-1">رصيد افتتاحي مدين ($)</label>
                    <input id="add-op-debit" name="opening_debit" type="number" step="0.01" min="0" value="0" class="w-full p-2.5 rounded-xl border border-slate-300 font-mono">
                </div>
                <div>
                    <label for="add-op-credit" class="block font-semibold text-slate-700 mb-1">رصيد افتتاحي دائن ($)</label>
                    <input id="add-op-credit" name="opening_credit" type="number" step="0.01" min="0" value="0" class="w-full p-2.5 rounded-xl border border-slate-300 font-mono">
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeAccountModal()" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold">إلغاء</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold">حفظ الحساب</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Account Modal -->
<div id="edit-account-modal" class="hidden fixed inset-0 z-50 bg-slate-950/50 flex items-center justify-center p-4 no-print">
    <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-lg p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900">تعديل الحساب <span id="edit-code-label" class="font-mono text-brand-700"></span></h2>
            <button type="button" onclick="closeEditAccountModal()" class="text-slate-400 hover:text-slate-700 text-lg font-bold px-2">×</button>
        </div>
        <form id="edit-account-form" method="POST" action="" class="space-y-3 text-xs">
            @csrf
            @method('PUT')
            <div>
                <label for="edit-name" class="block font-semibold text-slate-700 mb-1">اسم الحساب</label>
                <input id="edit-name" name="name" required class="w-full p-2.5 rounded-xl border border-slate-300">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="edit-classification" class="block font-semibold text-slate-700 mb-1">التصنيف</label>
                    <input id="edit-classification" name="classification" class="w-full p-2.5 rounded-xl border border-slate-300">
                </div>
                <div>
                    <label for="edit-currency" class="block font-semibold text-slate-700 mb-1">العملة</label>
                    <select id="edit-currency" name="currency" class="w-full p-2.5 rounded-xl border border-slate-300 bg-white">
                        <option value="USD">USD</option>
                        <option value="SYP">SYP</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="edit-op-debit" class="block font-semibold text-slate-700 mb-1">افتتاحي مدين ($)</label>
                    <input id="edit-op-debit" name="opening_debit" type="number" step="0.01" min="0" class="w-full p-2.5 rounded-xl border border-slate-300 font-mono">
                </div>
                <div>
                    <label for="edit-op-credit" class="block font-semibold text-slate-700 mb-1">افتتاحي دائن ($)</label>
                    <input id="edit-op-credit" name="opening_credit" type="number" step="0.01" min="0" class="w-full p-2.5 rounded-xl border border-slate-300 font-mono">
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeEditAccountModal()" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold">إلغاء</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold">حفظ التعديلات</button>
            </div>
        </form>
    </div>
</div>

<script>
    const nextCodesByParent = @json($nextCodes);
    const collapsedParents = new Set();

    function suggestAccountCode() {
        const parentSelect = document.getElementById('add-parent-id');
        const codeInput = document.getElementById('add-code');
        if (parentSelect && codeInput) {
            codeInput.value = nextCodesByParent[parentSelect.value] || '';
        }
    }

    function openAccountModal(parentId) {
        const modal = document.getElementById('add-account-modal');
        const select = document.getElementById('add-parent-id');
        if (parentId && select) {
            select.value = String(parentId);
        }
        suggestAccountCode();
        modal.classList.remove('hidden');
        document.getElementById('add-name')?.focus();
    }

    function closeAccountModal() {
        document.getElementById('add-account-modal').classList.add('hidden');
    }

    function openEditAccountModal(acc) {
        const form = document.getElementById('edit-account-form');
        form.action = '/books/accounts/' + acc.id;
        document.getElementById('edit-code-label').textContent = acc.code;
        document.getElementById('edit-name').value = acc.name || '';
        document.getElementById('edit-classification').value = acc.classification || '';
        document.getElementById('edit-currency').value = acc.currency || 'USD';
        document.getElementById('edit-op-debit').value = acc.opening_debit || 0;
        document.getElementById('edit-op-credit').value = acc.opening_credit || 0;
        document.getElementById('edit-account-modal').classList.remove('hidden');
    }

    function closeEditAccountModal() {
        document.getElementById('edit-account-modal').classList.add('hidden');
    }

    function applyTreeVisibility() {
        const rows = Array.from(document.querySelectorAll('#coa-tree-table .coa-row'));
        const parentMap = {};
        rows.forEach(r => { parentMap[r.dataset.id] = r.dataset.parent; });

        rows.forEach(r => {
            let pid = r.dataset.parent;
            let hidden = false;
            while (pid && pid !== '0') {
                if (collapsedParents.has(pid)) {
                    hidden = true;
                    break;
                }
                pid = parentMap[pid];
            }
            r.classList.toggle('hidden', hidden);
        });
    }

    function toggleCoaBranch(id) {
        const key = String(id);
        const btn = document.querySelector('[data-toggle-id="' + key + '"]');
        if (collapsedParents.has(key)) {
            collapsedParents.delete(key);
            if (btn) { btn.textContent = '−'; btn.setAttribute('aria-expanded', 'true'); }
        } else {
            collapsedParents.add(key);
            if (btn) { btn.textContent = '+'; btn.setAttribute('aria-expanded', 'false'); }
        }
        applyTreeVisibility();
    }

    function toggleAllCoa(expand) {
        collapsedParents.clear();
        document.querySelectorAll('[data-toggle-id]').forEach(btn => {
            const id = btn.getAttribute('data-toggle-id');
            const row = btn.closest('.coa-row');
            if (!expand && row && Number(row.dataset.depth) >= 1) {
                collapsedParents.add(id);
                btn.textContent = '+';
                btn.setAttribute('aria-expanded', 'false');
            } else {
                btn.textContent = '−';
                btn.setAttribute('aria-expanded', 'true');
            }
        });
        applyTreeVisibility();
    }

    document.getElementById('coa-search')?.addEventListener('input', function (e) {
        const q = (e.target.value || '').trim().toLowerCase();
        const rows = Array.from(document.querySelectorAll('#coa-tree-table .coa-row'));
        if (!q) {
            applyTreeVisibility();
            return;
        }
        rows.forEach(r => {
            const hay = r.dataset.search || '';
            r.classList.toggle('hidden', !hay.includes(q));
        });
    });
</script>
@endsection
