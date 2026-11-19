<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('settings.edit', [
            'shop' => current_shop(),
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $shop = current_shop();

        $rules = [
            'account_phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ];

        if ($shop && $user->hasRole('shop_manager')) {
            $rules = array_merge($rules, [
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:50',
                'owner_name' => 'nullable|string|max:255',
                'exchange_rate' => 'required|numeric|min:1',
                'default_alert_limit' => 'required|integer|min:0',
                'company_notes' => 'nullable|string',
                'admin_one_time_access' => 'nullable|boolean',
            ]);
        }

        $validated = $request->validate($rules);

        $user->phone = normalize_phone($validated['account_phone']);
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        if ($shop && $user->hasRole('shop_manager')) {
            $shop->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? $shop->phone,
                'owner_name' => $validated['owner_name'] ?? $shop->owner_name,
                'exchange_rate' => $validated['exchange_rate'],
                'default_alert_limit' => $validated['default_alert_limit'],
                'company_notes' => $validated['company_notes'] ?? null,
                'admin_one_time_access' => $request->boolean('admin_one_time_access'),
            ]);
        }

        return back()->with('success', 'تم حفظ الإعدادات.');
    }
}
