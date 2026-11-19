<div class="relative shrink-0 no-print" id="header-calc-root">
    <button type="button"
            id="header-calc-toggle"
            aria-haspopup="dialog"
            aria-expanded="false"
            aria-controls="header-calc-popover"
            class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 hover:text-brand-700 text-xs font-semibold transition-colors">
        <svg class="w-4 h-4 text-brand-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <rect x="4" y="2.75" width="16" height="18.5" rx="2.25"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 11.5h.01M12 11.5h.01M16 11.5h.01M8 15h.01M12 15h.01M16 15h.01M8 18.25h.01M12 18.25h.01M16 18.25h.01"/>
        </svg>
        <span>آلة الحاسبة</span>
    </button>

    <div id="header-calc-popover"
         role="dialog"
         aria-label="آلة الحاسبة"
         tabindex="-1"
         class="hidden absolute left-0 top-full mt-2 z-50 w-72 sm:w-80 max-w-[calc(100vw-2rem)] bg-white border border-slate-200 rounded-2xl shadow-xl p-3.5 outline-none">
        <div class="flex items-center justify-between pb-2.5 mb-2.5 border-b border-slate-100">
            <span class="text-xs font-bold text-slate-800">آلة الحاسبة السريعة</span>
            <div class="flex items-center gap-1.5">
                <button type="button"
                        id="header-calc-copy"
                        title="نسخ الناتج"
                        class="px-2 py-1 rounded-lg text-[11px] font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 transition-colors">
                    نسخ
                </button>
                <button type="button"
                        id="header-calc-close"
                        aria-label="إغلاق آلة الحاسبة"
                        class="inline-flex items-center justify-center w-6 h-6 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Calculator Display -->
        <div class="bg-slate-50 border border-slate-200/80 rounded-xl px-3 py-2.5 mb-3 text-left" dir="ltr">
            <div id="header-calc-expr" class="text-[11px] font-mono text-slate-400 min-h-[16px] truncate">&nbsp;</div>
            <div id="header-calc-display" class="text-2xl font-bold font-mono text-slate-900 truncate mt-0.5">0</div>
        </div>

        <!-- Keypad -->
        <div class="grid grid-cols-4 gap-1.5 font-mono text-sm" dir="ltr">
            <button type="button" data-calc-action="clear" class="py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs transition-colors">C</button>
            <button type="button" data-calc-action="backspace" aria-label="حذف رقم" class="py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-colors">⌫</button>
            <button type="button" data-calc-action="percent" class="py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition-colors">%</button>
            <button type="button" data-calc-op="/" class="py-2.5 rounded-xl bg-brand-50 hover:bg-brand-100 text-brand-700 font-bold text-base transition-colors">÷</button>

            <button type="button" data-calc-num="7" class="py-2.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-800 font-semibold transition-colors">7</button>
            <button type="button" data-calc-num="8" class="py-2.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-800 font-semibold transition-colors">8</button>
            <button type="button" data-calc-num="9" class="py-2.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-800 font-semibold transition-colors">9</button>
            <button type="button" data-calc-op="*" class="py-2.5 rounded-xl bg-brand-50 hover:bg-brand-100 text-brand-700 font-bold text-base transition-colors">×</button>

            <button type="button" data-calc-num="4" class="py-2.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-800 font-semibold transition-colors">4</button>
            <button type="button" data-calc-num="5" class="py-2.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-800 font-semibold transition-colors">5</button>
            <button type="button" data-calc-num="6" class="py-2.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-800 font-semibold transition-colors">6</button>
            <button type="button" data-calc-op="-" class="py-2.5 rounded-xl bg-brand-50 hover:bg-brand-100 text-brand-700 font-bold text-base transition-colors">−</button>

            <button type="button" data-calc-num="1" class="py-2.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-800 font-semibold transition-colors">1</button>
            <button type="button" data-calc-num="2" class="py-2.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-800 font-semibold transition-colors">2</button>
            <button type="button" data-calc-num="3" class="py-2.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-800 font-semibold transition-colors">3</button>
            <button type="button" data-calc-op="+" class="py-2.5 rounded-xl bg-brand-50 hover:bg-brand-100 text-brand-700 font-bold text-base transition-colors">+</button>

            <button type="button" data-calc-num="00" class="py-2.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-semibold text-xs transition-colors">00</button>
            <button type="button" data-calc-num="0" class="py-2.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-800 font-semibold transition-colors">0</button>
            <button type="button" data-calc-num="." class="py-2.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-800 font-bold transition-colors">.</button>
            <button type="button" data-calc-action="equals" class="py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-base shadow-sm transition-colors">=</button>
        </div>

        <p class="text-[10px] text-slate-400 text-center mt-2.5">
            تدعم الإدخال المباشر من لوحة المفاتيح · تحفظ القيمة حتى تحديث الصفحة
        </p>
    </div>
</div>

<script>
    (function () {
        function initHeaderCalculator() {
            const root = document.getElementById('header-calc-root');
            const toggleBtn = document.getElementById('header-calc-toggle');
            const popover = document.getElementById('header-calc-popover');
            const closeBtn = document.getElementById('header-calc-close');
            const copyBtn = document.getElementById('header-calc-copy');
            const exprEl = document.getElementById('header-calc-expr');
            const displayEl = document.getElementById('header-calc-display');

            if (!root || !toggleBtn || !popover || !exprEl || !displayEl) return;

            // In-memory state only: persists across open/close, resets on refresh or page navigation
            let tokens = [];
            let current = '0';
            let justEvaluated = false;
            let hasError = false;
            let lastExprText = '';

            function isOpen() {
                return !popover.classList.contains('hidden');
            }

            function openPopover() {
                popover.classList.remove('hidden');
                toggleBtn.setAttribute('aria-expanded', 'true');
                toggleBtn.classList.add('border-brand-300', 'bg-brand-50', 'text-brand-700');
                popover.focus({ preventScroll: true });
            }

            function closePopover() {
                popover.classList.add('hidden');
                toggleBtn.setAttribute('aria-expanded', 'false');
                toggleBtn.classList.remove('border-brand-300', 'bg-brand-50', 'text-brand-700');
            }

            function formatOp(op) {
                if (op === '*') return '×';
                if (op === '/') return '÷';
                if (op === '-') return '−';
                return op;
            }

            function roundClean(num) {
                if (!Number.isFinite(num)) return null;
                return Math.round((num + Number.EPSILON) * 1e10) / 1e10;
            }

            function evaluateTokens(list) {
                if (!list.length) return 0;
                const values = [];
                const ops = [];
                const prec = { '+': 1, '-': 1, '*': 2, '/': 2 };

                function applyTop() {
                    const op = ops.pop();
                    const b = values.pop();
                    const a = values.pop();
                    if (a === undefined || b === undefined) return false;
                    if (op === '+') values.push(a + b);
                    else if (op === '-') values.push(a - b);
                    else if (op === '*') values.push(a * b);
                    else if (op === '/') {
                        if (b === 0) return false;
                        values.push(a / b);
                    }
                    return true;
                }

                for (let i = 0; i < list.length; i++) {
                    const tk = list[i];
                    if (tk === '+' || tk === '-' || tk === '*' || tk === '/') {
                        while (ops.length && prec[ops[ops.length - 1]] >= prec[tk]) {
                            if (!applyTop()) return null;
                        }
                        ops.push(tk);
                    } else {
                        const n = Number(tk);
                        if (!Number.isFinite(n)) return null;
                        values.push(n);
                    }
                }
                while (ops.length) {
                    if (!applyTop()) return null;
                }
                return values.length === 1 ? roundClean(values[0]) : null;
            }

            function render() {
                if (hasError) {
                    displayEl.textContent = 'خطأ';
                    exprEl.textContent = lastExprText || '\u00A0';
                    return;
                }
                const liveExpr = tokens.map(formatOp).join(' ');
                exprEl.textContent = liveExpr || lastExprText || '\u00A0';
                const shown = current !== '' ? current : (tokens.length >= 2 ? tokens[tokens.length - 2] : '0');
                displayEl.textContent = shown;
            }

            function resetAll() {
                tokens = [];
                current = '0';
                justEvaluated = false;
                hasError = false;
                lastExprText = '';
                render();
            }

            function inputDigit(val) {
                if (hasError) {
                    resetAll();
                }
                if (justEvaluated) {
                    tokens = [];
                    lastExprText = '';
                    current = val === '.' ? '0.' : (val === '00' ? '0' : val);
                    justEvaluated = false;
                    render();
                    return;
                }
                if (val === '.') {
                    if (current.includes('.')) return;
                    current = current === '' ? '0.' : current + '.';
                } else if (val === '00') {
                    if (current === '0' || current === '') current = '0';
                    else current += '00';
                } else {
                    if (current === '0') current = val;
                    else current += val;
                }
                render();
            }

            function inputOperator(op) {
                if (hasError) {
                    resetAll();
                }
                if (justEvaluated) {
                    justEvaluated = false;
                    lastExprText = '';
                }
                if (current === '' && tokens.length) {
                    const last = tokens[tokens.length - 1];
                    if (last === '+' || last === '-' || last === '*' || last === '/') {
                        tokens[tokens.length - 1] = op;
                        render();
                        return;
                    }
                }
                tokens.push(current === '' ? '0' : current);
                tokens.push(op);
                current = '';
                render();
            }

            function inputPercent() {
                if (hasError) return;
                const num = Number(current || '0');
                if (!Number.isFinite(num)) return;
                const pct = roundClean(num / 100);
                current = pct === null ? '0' : String(pct);
                justEvaluated = false;
                render();
            }

            function inputBackspace() {
                if (hasError) {
                    resetAll();
                    return;
                }
                if (justEvaluated) {
                    justEvaluated = false;
                    lastExprText = '';
                }
                if (current.length > 1) {
                    current = current.slice(0, -1);
                } else if (current.length === 1 && current !== '0') {
                    current = '0';
                } else if (current === '' && tokens.length >= 2) {
                    tokens.pop();
                    current = String(tokens.pop());
                }
                render();
            }

            function evaluate() {
                if (hasError) return;
                const full = tokens.slice();
                if (current !== '') {
                    full.push(current);
                } else if (full.length && ['+', '-', '*', '/'].includes(full[full.length - 1])) {
                    full.pop();
                }
                if (!full.length) return;

                const result = evaluateTokens(full);
                lastExprText = full.map(formatOp).join(' ') + ' =';
                tokens = [];
                if (result === null) {
                    hasError = true;
                    current = '0';
                } else {
                    current = String(result);
                    justEvaluated = true;
                }
                render();
            }

            toggleBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (isOpen()) closePopover();
                else openPopover();
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', function () {
                    closePopover();
                });
            }

            if (copyBtn) {
                copyBtn.addEventListener('click', function () {
                    if (hasError) return;
                    const text = current || '0';
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(function () {
                            const orig = copyBtn.textContent;
                            copyBtn.textContent = 'تم النسخ';
                            setTimeout(function () { copyBtn.textContent = orig; }, 1200);
                        }).catch(function () {});
                    }
                });
            }

            popover.addEventListener('click', function (e) {
                const btn = e.target instanceof Element ? e.target.closest('button') : null;
                if (!btn) return;
                const num = btn.getAttribute('data-calc-num');
                const op = btn.getAttribute('data-calc-op');
                const action = btn.getAttribute('data-calc-action');
                if (num !== null) inputDigit(num);
                else if (op !== null) inputOperator(op);
                else if (action === 'clear') resetAll();
                else if (action === 'backspace') inputBackspace();
                else if (action === 'percent') inputPercent();
                else if (action === 'equals') evaluate();
            });

            // Close on click anywhere outside WITHOUT clearing state
            document.addEventListener('click', function (e) {
                if (!isOpen()) return;
                if (e.target instanceof Node && !root.contains(e.target)) {
                    closePopover();
                }
            });

            // Keyboard input while calculator is open
            document.addEventListener('keydown', function (e) {
                if (!isOpen()) return;

                const activeTag = document.activeElement ? document.activeElement.tagName : '';
                if (activeTag === 'INPUT' || activeTag === 'TEXTAREA' || activeTag === 'SELECT') {
                    return;
                }

                const key = e.key;
                if (key === 'Escape') {
                    e.preventDefault();
                    closePopover();
                    return;
                }
                if (/^[0-9]$/.test(key) || key === '.') {
                    e.preventDefault();
                    inputDigit(key);
                } else if (key === '+' || key === '-' || key === '*' || key === '/') {
                    e.preventDefault();
                    inputOperator(key);
                } else if (key === 'Enter' || key === '=') {
                    e.preventDefault();
                    evaluate();
                } else if (key === 'Backspace') {
                    e.preventDefault();
                    inputBackspace();
                } else if (key === '%') {
                    e.preventDefault();
                    inputPercent();
                } else if (key === 'Delete' || key === 'c' || key === 'C') {
                    e.preventDefault();
                    resetAll();
                }
            });

            render();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initHeaderCalculator);
        } else {
            initHeaderCalculator();
        }
    })();
</script>
