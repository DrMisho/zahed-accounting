@extends('layouts.app')

@section('title', 'إدارة المحلات والاشتراكات (Super Admin)')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-l from-brand-950 via-brand-900 to-brand-800 text-white rounded-3xl p-6 sm:p-8 border border-gold-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-block px-2.5 py-1 rounded-md bg-gold-500/20 text-gold-300 border border-gold-400/40 text-xs font-bold mb-2">
                لوحة السوبر أدمن (مالك المنصة) - Spatie Role: super_admin
            </div>
            <h1 class="text-2xl font-bold tracking-tight">إدارة المشتركين والمحلات (Tenant Management)</h1>
            <p class="text-xs sm:text-sm text-brand-200 mt-1">توليد shop_id تلقائياً وتفعيل أو إيقاف اشتراكات المحلات</p>
        </div>
        <button onclick="document.getElementById('modal-shop').classList.remove('hidden')" class="px-5 py-2.5 text-xs font-bold rounded-xl bg-brand-800 hover:bg-brand-900 text-white shadow-xs transition-colors self-start sm:self-center">
            + إضافة محل جديد
        </button>
    </div>

    <!-- Platform Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <span class="text-xs text-slate-500 font-semibold">إجمالي المحلات</span>
            <div class="text-2xl font-bold font-mono text-slate-900 mt-1">{{ $stats['total_shops'] }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <span class="text-xs text-slate-500 font-semibold">المحلات النشطة</span>
            <div class="text-2xl font-bold font-mono text-brand-800 mt-1">{{ $stats['active_shops'] }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <span class="text-xs text-slate-500 font-semibold">الإيرادات الشهرية التقديرية</span>
            <div class="text-2xl font-bold font-mono text-slate-900 mt-1">${{ number_format($stats['monthly_revenue']) }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <span class="text-xs text-slate-500 font-semibold">إجمالي المستخدمين</span>
            <div class="text-2xl font-bold font-mono text-indigo-600 mt-1">{{ $stats['total_users'] }}</div>
        </div>
    </div>

    <!-- Shops Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                        <th class="py-3 px-4">مُعرف المحل (shop_id)</th>
                        <th class="py-3 px-4">اسم المحل</th>
                        <th class="py-3 px-4">المالك والهاتف</th>
                        <th class="py-3 px-4 text-center">حالة الاشتراك</th>
                        <th class="py-3 px-4 text-center">تاريخ الانتهاء</th>
                        <th class="py-3 px-4 text-left">الاشتراك الشهري</th>
                        <th class="py-3 px-4 text-left">سعر الصرف</th>
                        <th class="py-3 px-4 text-center">الدخول للمحل</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($shops as $shop)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3.5 px-4 font-mono font-bold">{{ $shop->id }}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">{{ $shop->name }}</td>
                            <td class="py-3.5 px-4 text-slate-600">
                                <div>{{ $shop->owner_name }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $shop->phone }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <form method="POST" action="{{ route('admin.shops.toggle-status', $shop) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $shop->subscription_status === 'active' ? 'bg-brand-50 text-brand-800 border border-brand-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                        {{ $shop->subscription_status === 'active' ? 'نشط وساري' : 'موقوف' }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono">{{ $shop->end_date->format('Y-m-d') }}</td>
                            <td class="py-3.5 px-4 text-left font-mono font-bold">${{ number_format($shop->plan_price_usd) }}</td>
                            <td class="py-3.5 px-4 text-left font-mono">{{ number_format($shop->exchange_rate) }} ل.س</td>
                            <td class="py-3.5 px-4 text-center">
                                @if($shop->admin_one_time_access)
                                <form method="POST" action="{{ route('admin.shops.impersonate', $shop) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 bg-brand-900 hover:bg-brand-800 text-white rounded-lg text-xs font-bold">
                                        دخول كمدير (مرة واحدة)
                                    </button>
                                </form>
                                @else
                                    <span class="text-[11px] text-slate-400">لا يوجد تخويل</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add Shop -->
<div id="modal-shop" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex justify-between items-center pb-3 border-b">
            <h2 class="text-base font-bold text-slate-900">إضافة محل تجاري جديد</h2>
            <button onclick="document.getElementById('modal-shop').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 font-bold">✕</button>
        </div>
        <form method="POST" action="{{ route('admin.shops.store') }}" class="mt-4 space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">اسم المحل</label>
                <input type="text" name="name" required placeholder="مثال: مؤسسة الفخامة" class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">اسم المالك</label>
                    <input type="text" name="owner_name" required placeholder="الاسم الكامل" class="w-full text-xs p-2.5 rounded-xl border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">الهاتف</label>
                    <input type="text" name="phone" required placeholder="09..." class="w-full text-xs p-2.5 rounded-xl border border-slate-300 font-mono text-left">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">كلمة مرور مدير المحل</label>
                <input type="password" name="password" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300 text-left font-mono">
                <p class="text-[11px] text-slate-400 mt-1">تسجيل الدخول يتم برقم هاتف المالك أعلاه وليس بالبريد.</p>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">تاريخ نهاية الاشتراك</label>
                    <input type="date" name="end_date" value="{{ date('Y-m-d', strtotime('+1 year')) }}" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">الاشتراك الشهري ($)</label>
                    <input type="number" step="0.01" name="plan_price_usd" value="35" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300 font-mono text-left font-bold">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">سعر الصرف الافتراضي</label>
                <input type="number" name="exchange_rate" value="14500" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300 font-mono text-left font-bold">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-shop').classList.add('hidden')" class="px-4 py-2 text-xs text-slate-600">إلغاء</button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-brand-900 hover:bg-brand-800 rounded-xl">إنشاء المحل وحساب المدير</button>
            </div>
        </form>
    </div>
</div>
@endsection
