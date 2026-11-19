<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Shop;
use App\Models\User;
use App\Services\ShopSetupService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SuperAdminController extends Controller
{
    public function index()
    {
        $shops = Shop::withCount(['users', 'items', 'transactions'])->latest()->get();

        $stats = [
            'total_shops' => $shops->count(),
            'active_shops' => $shops->where('subscription_status', 'active')->count(),
            'monthly_revenue' => $shops->where('subscription_status', 'active')->sum('plan_price_usd'),
            'total_users' => User::count(),
        ];

        return view('admin.shops', [
            'shops' => $shops,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $phone = normalize_phone($request->input('phone'));
        $request->merge(['phone' => $phone]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')],
            'password' => 'required|string|min:6',
            'end_date' => 'required|date',
            'exchange_rate' => 'required|numeric|min:1',
            'plan_price_usd' => 'required|numeric|min:0',
        ]);

        $shopId = 'shop_' . Str::lower(Str::random(6));

        $shop = Shop::create([
            'id' => $shopId,
            'name' => $validated['name'],
            'owner_name' => $validated['owner_name'],
            'phone' => $validated['phone'],
            'subscription_status' => 'active',
            'end_date' => $validated['end_date'],
            'exchange_rate' => $validated['exchange_rate'],
            'plan_price_usd' => $validated['plan_price_usd'],
        ]);

        $user = User::create([
            'name' => $validated['owner_name'],
            'phone' => $validated['phone'],
            'email' => $validated['phone'].'@zahed.local',
            'password' => Hash::make($validated['password']),
            'shop_id' => $shopId,
        ]);
        $user->syncRoles(['shop_manager']);

        app(ShopSetupService::class)->bootstrap($shop);

        return redirect()->route('admin.shops')->with('success', "تم إنشاء المحل بنجاح برقم تعريف: {$shopId}");
    }

    public function toggleStatus(Shop $shop)
    {
        $shop->subscription_status = $shop->subscription_status === 'active' ? 'suspended' : 'active';
        $shop->save();

        return redirect()->route('admin.shops')->with('success', 'تم تعديل حالة اشتراك المحل.');
    }

    public function impersonate(Request $request, Shop $shop)
    {
        if (! $shop->admin_one_time_access) {
            return redirect()->route('admin.shops')->with('info', 'هذا المحل لم يخوّل دخول الأدمن حالياً.');
        }

        $shop->admin_one_time_access = false;
        $shop->save();

        $request->session()->put('impersonated_shop_id', $shop->id);

        return redirect()->route('dashboard')->with('info', "تم الدخول كمدير لمحل {$shop->name} لهذه الجلسة فقط.");
    }

    public function leaveImpersonation(Request $request)
    {
        $request->session()->forget('impersonated_shop_id');
        return redirect()->route('admin.shops')->with('info', 'تمت العودة للوحة الأدمن.');
    }
}
