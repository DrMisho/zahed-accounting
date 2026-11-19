<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Shop;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'shop_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $shopId = 'shop_' . Str::lower(Str::random(6));

        $shop = Shop::create([
            'id' => $shopId,
            'name' => $request->shop_name,
            'subscription_status' => 'active',
            'end_date' => now()->addDays(30), // فترة تجريبية مجانية 30 يوم
            'exchange_rate' => 14500.00,
            'owner_name' => $request->name,
            'plan_price_usd' => 35.00,
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'shop_id' => $shopId,
        ]);

        $user->syncRoles(['shop_manager']);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard'));
    }
}
