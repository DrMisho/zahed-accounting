<!DOCTYPE html>
<html lang="ar" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="زاهد للحلول المالية — نظام محاسبي وإداري سحابي بسيط للمتاجر والشركات. ابدأ بمبلغ شهري بسيط دون الحاجة لمحاسب أو شراء أنظمة ضخمة معقدة.">
    <title>زاهد للحلول المالية — نظامك المحاسبي البسيط دون الحاجة لمحاسب</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-mark.png') }}?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('layouts.partials.theme')
    <style>
        .hero-headline {
            font-size: clamp(2rem, 3.7vw, 3.25rem);
            line-height: 1.22;
            text-wrap: balance;
        }
        .section-title {
            font-size: clamp(1.5rem, 2.5vw, 2.25rem);
            line-height: 1.3;
            text-wrap: balance;
        }
        .tabular-nums {
            font-variant-numeric: tabular-nums;
        }
        input[type="range"] {
            accent-color: #10B981;
        }
        details[open] .faq-chevron {
            transform: rotate(180deg);
        }
        .faq-chevron {
            transition: transform 180ms cubic-bezier(0.16, 1, 0.3, 1);
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { transition-duration: 0.01ms !important; animation-duration: 0.01ms !important; }
        }
    </style>
</head>
<body id="top" class="font-sans antialiased bg-[#F3F6F4] text-slate-900 overflow-x-hidden">

    {{-- Top Bar: Strict 3-Zone Contract (Brand — 4 Nav Links — 1 Primary Action) --}}
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-8">
            {{-- Zone 1: Single-line Brand Wordmark --}}
            <a href="#top" class="flex items-center gap-2.5 text-brand-950 font-extrabold text-base sm:text-lg tracking-tight whitespace-nowrap shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-lg">
                @include('layouts.partials.logo-mark', ['class' => 'h-9 w-9 object-contain shrink-0'])
                <span>زاهد للحلول المالية</span>
            </a>

            {{-- Zone 2: 4 Clean Single-Line Nav Links --}}
            <nav aria-label="التنقل الرئيسي" class="hidden md:flex items-center gap-7 text-sm font-semibold text-slate-600">
                <a href="#why" class="py-1 hover:text-brand-800 hover:underline underline-offset-8 decoration-brand-600/60 transition-colors whitespace-nowrap shrink-0">لماذا زاهد</a>
                <a href="#how" class="py-1 hover:text-brand-800 hover:underline underline-offset-8 decoration-brand-600/60 transition-colors whitespace-nowrap shrink-0">بساطة العمل</a>
                <a href="#comparison" class="py-1 hover:text-brand-800 hover:underline underline-offset-8 decoration-brand-600/60 transition-colors whitespace-nowrap shrink-0">مقارنة التكلفة</a>
                <a href="#start" class="py-1 hover:text-brand-800 hover:underline underline-offset-8 decoration-brand-600/60 transition-colors whitespace-nowrap shrink-0">الاشتراك الشهري</a>
            </nav>

            {{-- Zone 3: Single Primary Action + Mobile Menu Trigger --}}
            <div class="flex items-center gap-2.5 shrink-0">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 text-xs sm:text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors whitespace-nowrap shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                        لوحة التحكم
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 text-xs sm:text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors whitespace-nowrap shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                        تسجيل الدخول
                    </a>
                @endauth
                <button type="button" id="mobile-nav-btn" aria-label="فتح القائمة" aria-expanded="false" aria-controls="mobile-nav-panel"
                    class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile Navigation Drawer --}}
        <div id="mobile-nav-panel" class="hidden md:hidden border-t border-slate-200 bg-white px-4 py-3 space-y-1">
            <a href="#why" class="mobile-nav-link block py-2.5 px-3 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-brand-800">لماذا زاهد</a>
            <a href="#how" class="mobile-nav-link block py-2.5 px-3 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-brand-800">بساطة العمل</a>
            <a href="#comparison" class="mobile-nav-link block py-2.5 px-3 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-brand-800">مقارنة التكلفة</a>
            <a href="#start" class="mobile-nav-link block py-2.5 px-3 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-brand-800">الاشتراك الشهري</a>
        </div>
    </header>

    <main>
        {{-- SECTION 1: HERO (Proposition + Dominant Visual + Proof Strip) --}}
        <section class="pt-10 pb-14 lg:pt-16 lg:pb-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-stretch">
                    {{-- Right Column: Core Value Proposition --}}
                    <div class="lg:col-span-7 flex flex-col justify-between bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-10 lg:p-12">
                        <div>
                            <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs sm:text-sm font-semibold text-brand-700 mb-5">
                                <span>نظام محاسبي وإداري سحابي</span>
                                <span aria-hidden="true" class="text-slate-300">·</span>
                                <span>لا يحتاج إلى محاسب</span>
                                <span aria-hidden="true" class="text-slate-300">·</span>
                                <span>دولار وليرة سورية</span>
                            </div>

                            <h1 class="hero-headline font-extrabold text-brand-950 tracking-tight mb-5">
                                حسابات عملك بدقة المحاسبين، ببساطة يومية وبإشتراك شهري بسيط
                            </h1>

                            <p class="text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl mb-8">
                                لم تعُد مضطراً لدفع آلاف الدولارات لشراء برنامج محاسبي ضخم ومعقد، ولا لتوظيف محاسب متفرغ فقط لتعرف كم ربحت ومن يدين لك. مع <strong class="text-brand-950 font-bold">زاهد للحلول المالية</strong>، تسجّل فواتيرك ومصاريفك بواجهة عربية واضحة، ويتكفّل النظام تلقائياً بالقيود المزدوجة، دفتر الأستاذ، حساب المتاجرة، الأرباح والخسائر، والميزانية العمومية.
                            </p>

                            <div class="flex flex-wrap items-center gap-4">
                                @auth
                                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm sm:text-base transition-colors whitespace-nowrap shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                                        <span>الدخول إلى لوحة التحكم</span>
                                        <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                        </svg>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm sm:text-base transition-colors whitespace-nowrap shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                                        <span>تسجيل الدخول إلى النظام</span>
                                        <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                        </svg>
                                    </a>
                                @endauth

                                <a href="#comparison" class="inline-flex items-center justify-center px-5 py-3.5 rounded-xl border border-slate-300 hover:border-brand-600 text-slate-800 hover:text-brand-800 font-bold text-sm sm:text-base transition-colors whitespace-nowrap shrink-0">
                                    احسب كم توفر شهرياً
                                </a>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-200/80 flex flex-wrap items-center justify-between gap-3 text-xs sm:text-sm text-slate-500">
                            <span>تبدأ فوراً دون رسوم تأسيس أو شراء سيرفرات</span>
                            <a href="tel:0984393644" class="font-bold text-brand-800 hover:text-brand-600 transition-colors tabular-nums" dir="ltr">0984393644</a>
                        </div>
                    </div>

                    {{-- Left Column: High-Fidelity Editorial Visual Anchor --}}
                    <div class="lg:col-span-5 relative rounded-2xl overflow-hidden border border-slate-200/80 bg-brand-950 min-h-[300px] lg:min-h-0 flex">
                        <img
                            src="{{ asset('images/landing-workspace.jpg') }}"
                            alt="مكتب إدارة مالية هادئ يعمل عليه صاحب العمل عبر نظام زاهد للحلول المالية"
                            referrerpolicy="no-referrer"
                            onerror="this.style.display='none'; document.getElementById('hero-img-fallback').classList.remove('hidden');"
                            class="w-full h-full object-cover"
                        >
                        <div id="hero-img-fallback" class="hidden w-full h-full bg-gradient-to-br from-brand-950 via-brand-900 to-brand-800 p-8 flex flex-col justify-center items-center text-center text-white">
                            @include('layouts.partials.logo-mark', ['class' => 'h-20 w-20 object-contain mb-4'])
                            <div class="text-lg font-extrabold">زاهد للحلول المالية</div>
                            <p class="text-sm text-brand-100/80 mt-2">محاسبة سحابية متكاملة للمتاجر والشركات</p>
                        </div>
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-brand-950/90 via-brand-950/55 to-transparent p-6 sm:p-7 text-white">
                            <p class="text-xs text-brand-200 font-semibold mb-1.5">رؤية فورية لوضعك المالي</p>
                            <p class="text-sm sm:text-base font-bold leading-snug">
                                من فاتورة البيع اليومية إلى الميزانية العمومية وكشف العميل — كل شيء يتحدث آلياً في الخلفية.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Full-Width 4-Column Quantitative Proof Strip Below Hero Split --}}
                <div class="mt-6 bg-white rounded-2xl border border-slate-200/80 grid grid-cols-2 lg:grid-cols-4 divide-y lg:divide-y-0 divide-x divide-x-reverse divide-slate-200/80">
                    <div class="p-5 sm:p-6">
                        <div class="text-2xl sm:text-3xl font-extrabold text-brand-950 tabular-nums" dir="ltr">$0</div>
                        <div class="text-xs sm:text-sm font-semibold text-slate-600 mt-1">تكلفة شراء رخصة أو تجهيز سيرفر محلي</div>
                    </div>
                    <div class="p-5 sm:p-6">
                        <div class="text-2xl sm:text-3xl font-extrabold text-brand-950 tabular-nums">5 دقائق</div>
                        <div class="text-xs sm:text-sm font-semibold text-slate-600 mt-1">للبدء وإصدار أول فاتورة وتسجيل الصندوق</div>
                    </div>
                    <div class="p-5 sm:p-6">
                        <div class="text-2xl sm:text-3xl font-extrabold text-brand-950 tabular-nums">100% آلي</div>
                        <div class="text-xs sm:text-sm font-semibold text-slate-600 mt-1">الترحيل لشجرة الحسابات ودفتر الأستاذ</div>
                    </div>
                    <div class="p-5 sm:p-6">
                        <div class="text-2xl sm:text-3xl font-extrabold text-brand-950 tabular-nums">USD / SYP</div>
                        <div class="text-xs sm:text-sm font-semibold text-slate-600 mt-1">حساب تلقائي لفروقات صرف العملة</div>
                    </div>
                </div>
            </div>
        </section>

        {{-- SECTION 2: WHY ZAHED — ASYMMETRIC BENTO CAPABILITIES + INTERACTIVE REPORT PREVIEW --}}
        <section id="why" class="py-14 lg:py-20 bg-white border-y border-slate-200/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mb-10 lg:mb-12">
                    <p class="text-xs sm:text-sm font-bold text-brand-700 mb-2">أهمية النظام لعملك التجاري</p>
                    <h2 class="section-title font-extrabold text-brand-950 mb-4">
                        لماذا يغنيك «زاهد» عن دفاتر الورق وعن الأنظمة المحاسبية المعقدة؟
                    </h2>
                    <p class="text-base text-slate-600 leading-relaxed">
                        الكثير من أصحاب المتاجر والشركات يقعون بين خيارين صعبين: إما العمل على الدفاتر وجداول متفرقة تضيع فيها الديون والأرباح الحقيقية، أو شراء نظام ضخم يكلف آلاف الدولارات ويحتاج إلى محاسب دائم لإدارته. صُمم «زاهد» ليمنحك قوة المحاسبة الحقيقية بأبسط تجربة استخدام يومية.
                    </p>
                </div>

                {{-- Asymmetric Bento Row 1: 7 Cols + 5 Cols --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
                    {{-- Card 01: Automatic Accounting Engine (7 cols) --}}
                    <div class="lg:col-span-7 bg-[#F3F6F4] rounded-2xl border border-slate-200/80 p-6 sm:p-8 flex flex-col justify-between">
                        <div>
                            <div class="text-xs font-bold text-brand-700 mb-2">01. لا حاجة لخبرة محاسبية</div>
                            <h3 class="text-xl sm:text-2xl font-extrabold text-brand-950 mb-3">
                                أنت تدخل الفاتورة فقط… والنظام يكتب القيود المحاسبية عنك
                            </h3>
                            <p class="text-sm sm:text-base text-slate-600 leading-relaxed mb-6">
                                لا تحتاج لأن تعرف ما هو «المدين» و«الدائن» ولا كيف تُغلق الحسابات في نهاية الشهر. عندما تسجّل فاتورة مبيع أو شراء، أو قبض من عميل، أو مصروف إيجار، يقوم النظام خلف الكواليس بتوليد القيد المزدوج المتوازن وترحيله فوراً إلى شجرة حسابات كاملة ومُعدة مسبقاً.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-5 border-t border-slate-200/90 text-sm">
                            <div>
                                <div class="font-bold text-brand-950">شجرة حسابات جاهزة</div>
                                <p class="text-xs text-slate-600 mt-1">أصول، التزامات، إيرادات، ومصاريف مرتبة تلقائياً لكل نشاط تجاري.</p>
                            </div>
                            <div>
                                <div class="font-bold text-brand-950">ميزان مراجعة متوازن</div>
                                <p class="text-xs text-slate-600 mt-1">تأكيد تلقائي لتوازن القيود دون أخطاء جمع أو سهو بشري.</p>
                            </div>
                            <div>
                                <div class="font-bold text-brand-950">تعديل وحذف آمن</div>
                                <p class="text-xs text-slate-600 mt-1">أي تعديل على فاتورة يصحّح أثره المحاسبي والمخزني لحظياً.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Card 02: Interactive Live Financial Preview (5 cols) --}}
                    <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 p-6 sm:p-7 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-4">
                                <div>
                                    <div class="text-xs font-bold text-brand-700">02. تقارير يفهمها صاحب العمل</div>
                                    <h3 class="text-lg font-extrabold text-brand-950 mt-0.5">معاينة حية لمخرجات النظام</h3>
                                </div>
                            </div>

                            {{-- Functional Segmented Tabs --}}
                            <div role="tablist" aria-label="معاينة التقارير" class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl mb-5">
                                <button type="button" role="tab" aria-selected="true" data-preview-tab="pnl"
                                    class="preview-tab flex-1 px-2.5 py-2 text-xs font-bold rounded-lg transition-colors whitespace-nowrap bg-white text-brand-950 shadow-sm">
                                    الأرباح والخسائر
                                </button>
                                <button type="button" role="tab" aria-selected="false" data-preview-tab="balance"
                                    class="preview-tab flex-1 px-2.5 py-2 text-xs font-bold rounded-lg transition-colors whitespace-nowrap text-slate-600 hover:text-brand-950">
                                    الميزانية
                                </button>
                                <button type="button" role="tab" aria-selected="false" data-preview-tab="statement"
                                    class="preview-tab flex-1 px-2.5 py-2 text-xs font-bold rounded-lg transition-colors whitespace-nowrap text-slate-600 hover:text-brand-950">
                                    كشف عميل
                                </button>
                            </div>

                            {{-- Panel 1: P&L --}}
                            <div data-preview-panel="pnl" class="space-y-3 text-sm">
                                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                    <span class="text-slate-600">صافي المبيعات</span>
                                    <span class="font-bold text-slate-900 tabular-nums" dir="ltr">$42,850.00</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                    <span class="text-slate-600">تكلفة البضاعة المباعة</span>
                                    <span class="font-semibold text-slate-700 tabular-nums" dir="ltr">-$29,400.00</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                    <span class="text-slate-600">المصاريف التشغيلية والرواتب</span>
                                    <span class="font-semibold text-slate-700 tabular-nums" dir="ltr">-$4,120.00</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                    <span class="text-slate-600">فروقات صرف العملة (صافي)</span>
                                    <span class="font-semibold text-brand-700 tabular-nums" dir="ltr">+$310.00</span>
                                </div>
                                <div class="flex items-center justify-between pt-2">
                                    <span class="font-extrabold text-brand-950">صافي الربح الفعلي</span>
                                    <span class="text-lg font-extrabold text-brand-700 tabular-nums" dir="ltr">$9,640.00</span>
                                </div>
                            </div>

                            {{-- Panel 2: Balance Sheet --}}
                            <div data-preview-panel="balance" class="hidden space-y-3 text-sm">
                                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                    <span class="text-slate-600">النقدية في الصناديق (دولار + ليرة)</span>
                                    <span class="font-bold text-slate-900 tabular-nums" dir="ltr">$18,450.00</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                    <span class="text-slate-600">قيمة المخزون الحالي</span>
                                    <span class="font-bold text-slate-900 tabular-nums" dir="ltr">$34,200.00</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                    <span class="text-slate-600">ذمم العملاء (لك في السوق)</span>
                                    <span class="font-bold text-slate-900 tabular-nums" dir="ltr">$11,900.00</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                    <span class="text-slate-600">ذمم الموردين (عليك للموردين)</span>
                                    <span class="font-semibold text-amber-700 tabular-nums" dir="ltr">$8,750.00</span>
                                </div>
                                <div class="flex items-center justify-between pt-2">
                                    <span class="font-extrabold text-brand-950">صافي حقوق الملكية</span>
                                    <span class="text-lg font-extrabold text-brand-700 tabular-nums" dir="ltr">$55,800.00</span>
                                </div>
                            </div>

                            {{-- Panel 3: Customer Statement --}}
                            <div data-preview-panel="statement" class="hidden space-y-3 text-sm">
                                <div class="py-2 border-b border-slate-100">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-900">فاتورة مبيع #1042</span>
                                        <span class="font-bold text-slate-900 tabular-nums" dir="ltr">$1,450.00</span>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1">محتويات الفاتورة: 10 قطع صنف (أ) · 5 قطع صنف (ب)</div>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                    <span class="text-slate-600">سند قبض نقدي (دولار)</span>
                                    <span class="font-semibold text-brand-700 tabular-nums" dir="ltr">-$900.00</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                    <span class="text-slate-600">سند قبض (ليرة سورية — معادل)</span>
                                    <span class="font-semibold text-brand-700 tabular-nums" dir="ltr">-$250.00</span>
                                </div>
                                <div class="flex items-center justify-between pt-2">
                                    <span class="font-extrabold text-brand-950">الرصيد المتبقي على العميل</span>
                                    <span class="text-lg font-extrabold text-brand-700 tabular-nums" dir="ltr">$300.00</span>
                                </div>
                            </div>
                        </div>

                        <p class="text-xs text-slate-500 mt-5 pt-4 border-t border-slate-100">
                            بيانات توضيحية لطريقة عرض التقارير الفورية داخل النظام.
                        </p>
                    </div>
                </div>

                {{-- Asymmetric Bento Row 2: 5 Cols + 7 Cols --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    {{-- Card 03: Dual Currency & FX (5 cols) --}}
                    <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 p-6 sm:p-8">
                        <div class="text-xs font-bold text-brand-700 mb-2">03. حماية رأس المال من تقلب الصرف</div>
                        <h3 class="text-xl font-extrabold text-brand-950 mb-3">
                            الدولار عملة أساسية والليرة ثانوية بحساب صرف دقيق
                        </h3>
                        <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                            عندما تشتري بالدولار وتبيع أو تقبض بالليرة السورية، يحسب النظام المعادل تلقائياً ويفصل أرباح أو خسائر فرق صرف العملة في حساب مستقل، لتعرف بدقة ربح تجارتك الحقيقي دون تضليل من تغيّر الأسعار.
                        </p>
                    </div>

                    {{-- Card 04: Complete Operations Coverage (7 cols) --}}
                    <div class="lg:col-span-7 bg-[#F3F6F4] rounded-2xl border border-slate-200/80 p-6 sm:p-8">
                        <div class="text-xs font-bold text-brand-700 mb-2">04. كل ما تحتاجه في مكان واحد</div>
                        <h3 class="text-xl font-extrabold text-brand-950 mb-4">
                            المبيعات، المشتريات، المخزون، الصندوق، العملاء والموظفون
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                            <div class="pb-3 border-b border-slate-200/80">
                                <div class="font-bold text-brand-950">كشوف عملاء وموردين تفصيلية</div>
                                <p class="text-xs text-slate-600 mt-1">تُظهر محتويات كل فاتورة داخل كشف الحساب مع طباعة رسمية مرتبة.</p>
                            </div>
                            <div class="pb-3 border-b border-slate-200/80">
                                <div class="font-bold text-brand-950">مراقبة المخزون والمستودعات</div>
                                <p class="text-xs text-slate-600 mt-1">جرد فوري، حركة الأصناف، وتنبيهات عند وصول البضاعة للحد الأدنى.</p>
                            </div>
                            <div class="pb-3 sm:pb-0 sm:border-b-0 border-b border-slate-200/80">
                                <div class="font-bold text-brand-950">إغلاق الصندوق اليومي</div>
                                <p class="text-xs text-slate-600 mt-1">مطابقة سريعة للنقد الفعلي بالدولار والليرة بنهاية كل يوم عمل.</p>
                            </div>
                            <div>
                                <div class="font-bold text-brand-950">الموظفون والشركاء</div>
                                <p class="text-xs text-slate-600 mt-1">رواتب، سلف، مسحوبات الشركاء، وتقرير مختصر للمالك في صفحة واحدة.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- SECTION 3: SIMPLICITY IN 3 CLEAR STEPS + EDITORIAL SHOP VISUAL --}}
        <section id="how" class="py-14 lg:py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-stretch">
                    {{-- Editorial Image (5 cols) --}}
                    <div class="lg:col-span-5 rounded-2xl overflow-hidden border border-slate-200/80 bg-brand-950 min-h-[260px] lg:min-h-0">
                        <img
                            src="{{ asset('images/landing-shop.jpg') }}"
                            alt="بيئة متجر وشركة منظمة تستخدم نظام زاهد بسهولة من جهاز لوحي"
                            referrerpolicy="no-referrer"
                            onerror="this.style.display='none'; document.getElementById('shop-img-fallback').classList.remove('hidden');"
                            class="w-full h-full object-cover"
                        >
                        <div id="shop-img-fallback" class="hidden w-full h-full bg-gradient-to-br from-brand-900 to-brand-950 p-8 flex flex-col justify-center items-center text-center text-white">
                            <div class="text-lg font-extrabold">سهولة الاستخدام اليومي</div>
                            <p class="text-sm text-brand-100/80 mt-2">واجهة واضحة من الجوال أو التابلت أو الحاسوب</p>
                        </div>
                    </div>

                    {{-- 3 Simple Steps (7 cols) --}}
                    <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-10 flex flex-col justify-center">
                        <p class="text-xs sm:text-sm font-bold text-brand-700 mb-2">بساطة التشغيل</p>
                        <h2 class="section-title font-extrabold text-brand-950 mb-8">
                            كيف تبدأ العمل على «زاهد» في ثلاث خطوات فقط؟
                        </h2>

                        <div class="space-y-6">
                            <div class="flex items-start gap-4 pb-6 border-b border-slate-200/80">
                                <span class="text-lg font-extrabold text-brand-700 tabular-nums shrink-0 mt-0.5">01.</span>
                                <div>
                                    <h3 class="text-base sm:text-lg font-extrabold text-brand-950">تفعيل فوري دون تركيب أجهزة أو برامج</h3>
                                    <p class="text-sm text-slate-600 leading-relaxed mt-1">
                                        بمجرد اشتراكك الشهري، تستلم بيانات الدخول وتفتح نظامك مباشرة من المتصفح على الحاسوب أو الموبايل أو التابلت، بشجرة حسابات وإعدادات جاهزة لطبيعة عملك.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4 pb-6 border-b border-slate-200/80">
                                <span class="text-lg font-extrabold text-brand-700 tabular-nums shrink-0 mt-0.5">02.</span>
                                <div>
                                    <h3 class="text-base sm:text-lg font-extrabold text-brand-950">تسجيل الفواتير والمصاريف بلغة يومية بسيطة</h3>
                                    <p class="text-sm text-slate-600 leading-relaxed mt-1">
                                        شاشات بيع وشراء وقبض وصرف واضحة وخالية من التعقيد؛ أي موظف أو صاحب محل يتقنها من اليوم الأول دون دورات تدريبية طويلة أو مساعدة محاسب.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <span class="text-lg font-extrabold text-brand-700 tabular-nums shrink-0 mt-0.5">03.</span>
                                <div>
                                    <h3 class="text-base sm:text-lg font-extrabold text-brand-950">متابعة الأرباح والديون والميزانية في أي لحظة</h3>
                                    <p class="text-sm text-slate-600 leading-relaxed mt-1">
                                        في نهاية اليوم أو الشهر، افتح تقرير المالك أو الحسابات الختامية لتعرف صافي ربحك، قيمة بضاعتك، ورصيد كل عميل ومورد بوضوح تام.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- SECTION 4: COST COMPARISON + INTERACTIVE SAVINGS CALCULATOR (Deep Emerald Contrast Section) --}}
        <section id="comparison" class="py-16 lg:py-24 bg-brand-950 text-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mb-10 lg:mb-12">
                    <p class="text-xs sm:text-sm font-bold text-brand-300 mb-2">قرار مالي ذكي لعملك</p>
                    <h2 class="section-title font-extrabold text-white mb-4">
                        ابدأ بمبلغ شهري بسيط بدلاً من تجميد آلاف الدولارات في نظام ضخم
                    </h2>
                    <p class="text-sm sm:text-base text-slate-300 leading-relaxed">
                        لماذا تدفع رأس مال كبير مقدماً لشراء برنامج معقد وتتحمّل راتب محاسب شهرياً لتشغيله، بينما يمكنك الحصول على نظام محاسبي متكامل باشتراك شهري ميسّر يواكب نمو عملك؟
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
                    {{-- Comparison Table (7 cols) --}}
                    <div class="lg:col-span-7 bg-[#062E25] rounded-2xl border border-brand-800/70 overflow-hidden flex flex-col">
                        <div class="overflow-x-auto flex-1">
                            <table class="w-full text-right text-sm">
                                <thead>
                                    <tr class="border-b border-brand-800/80 text-xs text-brand-200">
                                        <th class="py-4 px-5 font-bold">وجه المقارنة</th>
                                        <th class="py-4 px-5 font-bold text-slate-400">الأنظمة الضخمة التقليدية</th>
                                        <th class="py-4 px-5 font-extrabold text-white bg-brand-900/70">زاهد للحلول المالية</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-brand-800/50">
                                    <tr>
                                        <td class="py-4 px-5 font-bold text-white">تكلفة البداية</td>
                                        <td class="py-4 px-5 text-slate-300">شراء رخصة وسيرفر بمبلغ كبير مقدماً</td>
                                        <td class="py-4 px-5 font-semibold text-brand-200 bg-brand-900/40">اشتراك شهري بسيط بدون دفعة تأسيس ضخمة</td>
                                    </tr>
                                    <tr>
                                        <td class="py-4 px-5 font-bold text-white">الحاجة لمحاسب</td>
                                        <td class="py-4 px-5 text-slate-300">يتطلب محاسباً متفرغاً لإدخال القيود</td>
                                        <td class="py-4 px-5 font-semibold text-brand-200 bg-brand-900/40">لا يحتاج محاسباً — الترحيل آلي بالكامل</td>
                                    </tr>
                                    <tr>
                                        <td class="py-4 px-5 font-bold text-white">وقت التشغيل والتعلم</td>
                                        <td class="py-4 px-5 text-slate-300">أسابيع من التهيئة والتدريب المعقد</td>
                                        <td class="py-4 px-5 font-semibold text-brand-200 bg-brand-900/40">تبدأ خلال دقائق بواجهة عربية سهلة</td>
                                    </tr>
                                    <tr>
                                        <td class="py-4 px-5 font-bold text-white">التحديثات والصيانة</td>
                                        <td class="py-4 px-5 text-slate-300">عقود صيانة ورسوم إضافية لكل تحديث</td>
                                        <td class="py-4 px-5 font-semibold text-brand-200 bg-brand-900/40">مشمولة تلقائياً ضمن الاشتراك الشهري</td>
                                    </tr>
                                    <tr>
                                        <td class="py-4 px-5 font-bold text-white">الدولار وفروقات الصرف</td>
                                        <td class="py-4 px-5 text-slate-300">إعدادات معقدة أو وحدات مدفوعة إضافية</td>
                                        <td class="py-4 px-5 font-semibold text-brand-200 bg-brand-900/40">مبني أصلاً للدولار والليرة وفروقات الصرف</td>
                                    </tr>
                                    <tr>
                                        <td class="py-4 px-5 font-bold text-white">الوصول للبيانات</td>
                                        <td class="py-4 px-5 text-slate-300">مقيّد بجهاز واحد داخل المحل أو المكتب</td>
                                        <td class="py-4 px-5 font-semibold text-brand-200 bg-brand-900/40">من أي مكان عبر الموبايل أو الحاسوب</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Interactive First-Year Savings Calculator (5 cols) --}}
                    <div class="lg:col-span-5 bg-white text-slate-900 rounded-2xl border border-slate-200 p-6 sm:p-8 flex flex-col justify-between">
                        <div>
                            <div class="text-xs font-bold text-brand-700 mb-1">حاسبة التوفير التقديرية</div>
                            <h3 class="text-xl font-extrabold text-brand-950 mb-2">
                                احسب كم توفر في السنة الأولى
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-600 mb-6">
                                حرّك المؤشرات لتقدير الفرق بين شراء نظام تقليدي مع راتب محاسب، وبين الاشتراك الشهري البسيط في «زاهد».
                            </p>

                            <div class="space-y-5">
                                <div>
                                    <div class="flex items-center justify-between text-xs sm:text-sm mb-2">
                                        <label for="calc-upfront" class="font-bold text-slate-700">سعر شراء نظام تقليدي وتجهيزه</label>
                                        <span id="calc-upfront-val" class="font-extrabold text-brand-950 tabular-nums" dir="ltr">$2,000</span>
                                    </div>
                                    <input id="calc-upfront" type="range" min="800" max="6000" step="100" value="2000" class="w-full cursor-pointer">
                                </div>

                                <div>
                                    <div class="flex items-center justify-between text-xs sm:text-sm mb-2">
                                        <label for="calc-accountant" class="font-bold text-slate-700">التكلفة الشهرية لمحاسب متفرغ</label>
                                        <span id="calc-accountant-val" class="font-extrabold text-brand-950 tabular-nums" dir="ltr">$300 / شهر</span>
                                    </div>
                                    <input id="calc-accountant" type="range" min="150" max="1000" step="25" value="300" class="w-full cursor-pointer">
                                </div>

                                <div>
                                    <div class="flex items-center justify-between text-xs sm:text-sm mb-2">
                                        <label for="calc-zahed" class="font-bold text-slate-700">تقدير اشتراكك الشهري البسيط</label>
                                        <span id="calc-zahed-val" class="font-extrabold text-brand-700 tabular-nums" dir="ltr">$25 / شهر</span>
                                    </div>
                                    <input id="calc-zahed" type="range" min="10" max="60" step="5" value="25" class="w-full cursor-pointer">
                                </div>
                            </div>
                        </div>

                        <div class="mt-7 pt-5 border-t border-slate-200 space-y-3">
                            <div class="flex items-center justify-between text-xs sm:text-sm">
                                <span class="text-slate-600">تكلفة الحل التقليدي خلال سنة</span>
                                <span id="calc-traditional-year" class="font-bold text-slate-800 tabular-nums" dir="ltr">$5,600</span>
                            </div>
                            <div class="flex items-center justify-between text-xs sm:text-sm">
                                <span class="text-slate-600">تكلفة «زاهد» خلال سنة كاملة</span>
                                <span id="calc-zahed-year" class="font-bold text-brand-700 tabular-nums" dir="ltr">$300</span>
                            </div>
                            <div class="flex items-center justify-between pt-3 border-t border-slate-200">
                                <span class="text-sm font-extrabold text-brand-950">توفيرك التقديري في السنة الأولى</span>
                                <span id="calc-savings" class="text-2xl font-extrabold text-brand-700 tabular-nums" dir="ltr">$5,300</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- SECTION 5: START WITH SIMPLE MONTHLY PLAN + LEAD CAPTURE + FAQ --}}
        <section id="start" class="py-16 lg:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
                    {{-- Left/Right Primary Start & Login Block (6 cols) --}}
                    <div class="lg:col-span-6 bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-10 flex flex-col justify-between">
                        <div>
                            <p class="text-xs sm:text-sm font-bold text-brand-700 mb-2">ابدأ اليوم بأقل تكلفة ممكنة</p>
                            <h2 class="section-title font-extrabold text-brand-950 mb-4">
                                اشتراك شهري بسيط يناسب حجم عملك — بدون التزامات معقدة
                            </h2>
                            <p class="text-sm sm:text-base text-slate-600 leading-relaxed mb-6">
                                بدلاً من المخاطرة بمبلغ كبير لشراء برنامج قد لا يناسبك، تبدأ في «زاهد للحلول المالية» باشتراك شهري بسيط يشمل كافة وحدات النظام المحاسبي والمخزني، الترحيل الآلي، الدعم الفني، والنسخ الاحتياطي.
                            </p>

                            <ul class="space-y-3 text-sm text-slate-700 mb-8">
                                <li class="flex items-start gap-2.5">
                                    <span class="font-extrabold text-brand-700" aria-hidden="true">—</span>
                                    <span>شجرة حسابات متكاملة، دفتر أستاذ، وميزانية عمومية مفعّلة من اليوم الأول.</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="font-extrabold text-brand-700" aria-hidden="true">—</span>
                                    <span>دعم كامل للدولار والليرة السورية وحساب فروقات الصرف آلياً.</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="font-extrabold text-brand-700" aria-hidden="true">—</span>
                                    <span>كشوفات مبيعات ومشتريات وعملاء وموردين جاهزة للطباعة بترويسة واضحة.</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="font-extrabold text-brand-700" aria-hidden="true">—</span>
                                    <span>دعم مباشر لمساعدتك في الإعداد الأولي ونقل أرصدتك الافتتاحية.</span>
                                </li>
                            </ul>
                        </div>

                        <div class="pt-6 border-t border-slate-200/80 flex flex-wrap items-center justify-between gap-4">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-6 py-3.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm transition-colors whitespace-nowrap shrink-0">
                                    الذهاب إلى لوحة التحكم
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-3.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm transition-colors whitespace-nowrap shrink-0">
                                    تسجيل الدخول للنظام
                                </a>
                            @endauth

                            <div class="text-right">
                                <div class="text-xs text-slate-500">تواصل مباشر لتفعيل اشتراكك</div>
                                <a href="tel:0984393644" class="text-base font-extrabold text-brand-950 hover:text-brand-700 transition-colors tabular-nums" dir="ltr">0984393644</a>
                            </div>
                        </div>
                    </div>

                    {{-- Subscription Inquiry Form with Real Client Validation & Direct Confirmation (6 cols) --}}
                    <div class="lg:col-span-6 bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-10">
                        <p class="text-xs sm:text-sm font-bold text-brand-700 mb-2">طلب تفعيل أو تجربة</p>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-brand-950 mb-2">
                            اطلب إنشاء حساب لعملك خلال دقائق
                        </h3>
                        <p class="text-sm text-slate-600 mb-6">
                            اترك بياناتك وسيتواصل معك فريق «زاهد للحلول المالية» لتفعيل حسابك الشهري ومساعدتك في البدء، أو اتصل مباشرة على <span class="font-bold text-brand-900 tabular-nums" dir="ltr">0984393644</span>.
                        </p>

                        <form id="inquiry-form" novalidate class="space-y-4">
                            <div>
                                <label for="lead-name" class="block text-xs font-bold text-slate-700 mb-1.5">الاسم الكامل</label>
                                <input id="lead-name" type="text" required placeholder="مثال: أحمد العلي"
                                    class="w-full text-sm px-3.5 py-3 rounded-xl border border-slate-300 focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none transition-colors">
                                <p id="lead-name-err" class="hidden text-xs text-rose-600 mt-1">يرجى إدخال الاسم الكامل.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="lead-phone" class="block text-xs font-bold text-slate-700 mb-1.5">رقم الموبايل</label>
                                    <input id="lead-phone" type="tel" required dir="ltr" inputmode="numeric" placeholder="09XXXXXXXX"
                                        class="w-full text-sm px-3.5 py-3 rounded-xl border border-slate-300 focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none tabular-nums transition-colors">
                                    <p id="lead-phone-err" class="hidden text-xs text-rose-600 mt-1">يرجى إدخال رقم موبايل صحيح (8 أرقام على الأقل).</p>
                                </div>
                                <div>
                                    <label for="lead-business" class="block text-xs font-bold text-slate-700 mb-1.5">اسم المحل أو النشاط التجاري</label>
                                    <input id="lead-business" type="text" required placeholder="مثال: تجارة مواد غذائية"
                                        class="w-full text-sm px-3.5 py-3 rounded-xl border border-slate-300 focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none transition-colors">
                                    <p id="lead-business-err" class="hidden text-xs text-rose-600 mt-1">يرجى إدخال اسم أو طبيعة النشاط.</p>
                                </div>
                            </div>

                            <button type="submit" class="w-full py-3.5 px-5 rounded-xl bg-brand-950 hover:bg-brand-900 text-white font-bold text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                                تجهيز رسالة طلب الاشتراك
                            </button>
                        </form>

                        <div id="inquiry-success" class="hidden mt-5 p-4 rounded-xl bg-brand-50 border border-brand-200 text-brand-950 text-sm space-y-3">
                            <div class="font-extrabold">تم تجهيز طلبك بنجاح</div>
                            <p id="inquiry-summary" class="text-xs sm:text-sm text-brand-900 leading-relaxed"></p>
                            <div class="flex flex-wrap items-center gap-3 pt-1">
                                <a id="inquiry-whatsapp-link" href="#" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold transition-colors whitespace-nowrap">
                                    إرسال الطلب عبر واتساب
                                </a>
                                <a href="tel:0984393644"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-brand-300 text-brand-900 hover:bg-brand-100 text-xs font-bold transition-colors whitespace-nowrap tabular-nums" dir="ltr">
                                    اتصال: 0984393644
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Concise FAQ --}}
                <div class="mt-14 lg:mt-20 max-w-4xl mx-auto">
                    <div class="text-center mb-8">
                        <p class="text-xs sm:text-sm font-bold text-brand-700 mb-2">أسئلة متكررة</p>
                        <h2 class="section-title font-extrabold text-brand-950">إجابات واضحة قبل أن تبدأ</h2>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200/80 divide-y divide-slate-200/80">
                        <details class="group p-5 sm:p-6" open>
                            <summary class="flex items-center justify-between gap-4 cursor-pointer list-none font-bold text-brand-950 text-sm sm:text-base">
                                <span>هل فعلاً لا أحتاج إلى محاسب أو خبرة محاسبية لاستخدام النظام؟</span>
                                <svg class="faq-chevron w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </summary>
                            <p class="mt-3 text-sm text-slate-600 leading-relaxed">
                                نعم. صُمم «زاهد للحلول المالية» بحيث تتعامل فقط مع شاشات عملك الطبيعية مثل فاتورة البيع، فاتورة الشراء، قبض عميل، ومصروف يومي. جميع القيود المحاسبية والترحيل لدفتر الأستاذ وحساب الأرباح والميزانية تتم تلقائياً بالكامل دون أي تدخل يدوي.
                            </p>
                        </details>

                        <details class="group p-5 sm:p-6">
                            <summary class="flex items-center justify-between gap-4 cursor-pointer list-none font-bold text-brand-950 text-sm sm:text-base">
                                <span>كيف يختلف الاشتراك الشهري عن شراء برنامج محاسبي تقليدي؟</span>
                                <svg class="faq-chevron w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </summary>
                            <p class="mt-3 text-sm text-slate-600 leading-relaxed">
                                عند شراء نظام تقليدي، تدفع مبلغاً كبيراً مقدماً بالإضافة لتكلفة السيرفر والصيانة والتحديثات وراتب محاسب. في «زاهد» تبدأ بمبلغ شهري بسيط دون أي دفعة تأسيس ضخمة، مع شمول التحديثات والنسخ الاحتياطي والعمل من أي جهاز.
                            </p>
                        </details>

                        <details class="group p-5 sm:p-6">
                            <summary class="flex items-center justify-between gap-4 cursor-pointer list-none font-bold text-brand-950 text-sm sm:text-base">
                                <span>هل يدعم النظام التعامل بالدولار والليرة السورية معاً؟</span>
                                <svg class="faq-chevron w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </summary>
                            <p class="mt-3 text-sm text-slate-600 leading-relaxed">
                                نعم، يعتمد النظام الدولار الأمريكي كعملة أساسية والليرة السورية كعملة ثانوية، ويعالج تلقائياً عمليات التصريف وفروقات سعر الصرف حتى تبقى أرقام أرباحك وذممك دقيقة تماماً.
                            </p>
                        </details>

                        <details class="group p-5 sm:p-6">
                            <summary class="flex items-center justify-between gap-4 cursor-pointer list-none font-bold text-brand-950 text-sm sm:text-base">
                                <span>كيف أدخل إلى حسابي إذا كنت مشتركاً حالياً؟</span>
                                <svg class="faq-chevron w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </summary>
                            <p class="mt-3 text-sm text-slate-600 leading-relaxed">
                                اضغط على زر «تسجيل الدخول» في أعلى الصفحة، ثم أدخل رقم الموبايل وكلمة المرور المخصصة لنشاطك التجاري للانتقال مباشرة إلى لوحة التحكم.
                            </p>
                        </details>
                    </div>
                </div>
            </div>
        </section>
    </main>

    {{-- Quiet Footer --}}
    <footer class="bg-white border-t border-slate-200/80 py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 pb-8 border-b border-slate-200/70">
                <div class="flex items-center gap-3">
                    @include('layouts.partials.logo-mark', ['class' => 'h-11 w-11 object-contain shrink-0'])
                    <div>
                        <div class="text-base font-extrabold text-brand-950">زاهد للحلول المالية</div>
                        <a href="tel:0984393644" class="text-xs font-bold text-brand-700 hover:text-brand-900 tabular-nums" dir="ltr">0984393644</a>
                    </div>
                </div>

                <nav aria-label="روابط التذييل" class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm font-semibold text-slate-600">
                    <a href="#why" class="hover:text-brand-800 transition-colors">لماذا زاهد</a>
                    <a href="#how" class="hover:text-brand-800 transition-colors">بساطة العمل</a>
                    <a href="#comparison" class="hover:text-brand-800 transition-colors">مقارنة التكلفة</a>
                    <a href="#start" class="hover:text-brand-800 transition-colors">الاشتراك الشهري</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-brand-700 hover:text-brand-900 font-bold transition-colors">لوحة التحكم</a>
                    @else
                        <a href="{{ route('login') }}" class="text-brand-700 hover:text-brand-900 font-bold transition-colors">تسجيل الدخول</a>
                    @endauth
                </nav>
            </div>

            <div class="pt-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs text-slate-500">
                <span>جميع الحقوق محفوظة &copy; {{ date('Y') }} زاهد للحلول المالية</span>
                <span>نظام محاسبي وإداري سحابي للمتاجر والشركات</span>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            // 1. Mobile Navigation Drawer
            var mobileBtn = document.getElementById('mobile-nav-btn');
            var mobilePanel = document.getElementById('mobile-nav-panel');
            if (mobileBtn && mobilePanel) {
                mobileBtn.addEventListener('click', function () {
                    var isHidden = mobilePanel.classList.toggle('hidden');
                    mobileBtn.setAttribute('aria-expanded', String(!isHidden));
                });
                mobilePanel.querySelectorAll('.mobile-nav-link').forEach(function (link) {
                    link.addEventListener('click', function () {
                        mobilePanel.classList.add('hidden');
                        mobileBtn.setAttribute('aria-expanded', 'false');
                    });
                });
            }

            // 2. Interactive Report Preview Tabs
            var tabs = document.querySelectorAll('.preview-tab');
            var panels = document.querySelectorAll('[data-preview-panel]');
            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    var target = tab.getAttribute('data-preview-tab');
                    tabs.forEach(function (t) {
                        var active = t === tab;
                        t.setAttribute('aria-selected', String(active));
                        t.classList.toggle('bg-white', active);
                        t.classList.toggle('text-brand-950', active);
                        t.classList.toggle('shadow-sm', active);
                        t.classList.toggle('text-slate-600', !active);
                    });
                    panels.forEach(function (panel) {
                        panel.classList.toggle('hidden', panel.getAttribute('data-preview-panel') !== target);
                    });
                });
            });

            // 3. Interactive First-Year Savings Calculator
            var upfrontInput = document.getElementById('calc-upfront');
            var accountantInput = document.getElementById('calc-accountant');
            var zahedInput = document.getElementById('calc-zahed');

            function formatUsd(num) {
                return '$' + Number(num).toLocaleString('en-US');
            }

            function updateSavings() {
                if (!upfrontInput || !accountantInput || !zahedInput) return;
                var upfront = Number(upfrontInput.value) || 0;
                var accountantMonthly = Number(accountantInput.value) || 0;
                var zahedMonthly = Number(zahedInput.value) || 0;

                var traditionalYear = upfront + (accountantMonthly * 12);
                var zahedYear = zahedMonthly * 12;
                var savings = Math.max(0, traditionalYear - zahedYear);

                document.getElementById('calc-upfront-val').textContent = formatUsd(upfront);
                document.getElementById('calc-accountant-val').textContent = formatUsd(accountantMonthly) + ' / شهر';
                document.getElementById('calc-zahed-val').textContent = formatUsd(zahedMonthly) + ' / شهر';
                document.getElementById('calc-traditional-year').textContent = formatUsd(traditionalYear);
                document.getElementById('calc-zahed-year').textContent = formatUsd(zahedYear);
                document.getElementById('calc-savings').textContent = formatUsd(savings);
            }

            [upfrontInput, accountantInput, zahedInput].forEach(function (el) {
                if (el) el.addEventListener('input', updateSavings);
            });
            updateSavings();

            // 4. Inquiry Form Validation & WhatsApp / Direct Contact Handoff
            var form = document.getElementById('inquiry-form');
            if (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var nameEl = document.getElementById('lead-name');
                    var phoneEl = document.getElementById('lead-phone');
                    var bizEl = document.getElementById('lead-business');

                    var nameVal = (nameEl.value || '').trim();
                    var phoneVal = (phoneEl.value || '').trim();
                    var bizVal = (bizEl.value || '').trim();
                    var phoneDigits = phoneVal.replace(/[^0-9]/g, '');

                    var valid = true;
                    document.getElementById('lead-name-err').classList.toggle('hidden', nameVal.length >= 2);
                    if (nameVal.length < 2) valid = false;

                    document.getElementById('lead-phone-err').classList.toggle('hidden', phoneDigits.length >= 8);
                    if (phoneDigits.length < 8) valid = false;

                    document.getElementById('lead-business-err').classList.toggle('hidden', bizVal.length >= 2);
                    if (bizVal.length < 2) valid = false;

                    if (!valid) return;

                    var msg = 'السلام عليكم، أرغب بالاشتراك في نظام زاهد للحلول المالية.\nالاسم: ' + nameVal + '\nالنشاط: ' + bizVal + '\nرقم الموبايل: ' + phoneVal;
                    var waUrl = 'https://wa.me/963984393644?text=' + encodeURIComponent(msg);

                    document.getElementById('inquiry-summary').textContent =
                        'الاسم: ' + nameVal + ' · النشاط: ' + bizVal + ' · الموبايل: ' + phoneVal + '. يمكنك إرسال الطلب مباشرة عبر واتساب أو الاتصال على الرقم الموضح.';
                    document.getElementById('inquiry-whatsapp-link').setAttribute('href', waUrl);
                    document.getElementById('inquiry-success').classList.remove('hidden');
                });
            }
        })();
    </script>
</body>
</html>
