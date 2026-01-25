<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class OnlyAdminController extends Controller
{
    private function ensurePinExists(): void
    {
        $settings = app('settings');

        $existing = (string) $settings->get('onlyadmin.pin_hash', '');
        if ($existing !== '') {
            return;
        }

        // Default PIN so the feature works immediately.
        $settings->set('onlyadmin.pin_hash', Hash::make('1234'), 'onlyadmin');
    }

    private function rebuildRouteCache(): RedirectResponse
    {
        Artisan::call('route:clear');
        Artisan::call('route:cache');

        return back()->with('status', 'Route cache rebuilt.');
    }

    public function cacheRoutes(Request $request): RedirectResponse
    {
        return $this->rebuildRouteCache();
    }

    public function index(Request $request): View|RedirectResponse
    {
        $this->ensurePinExists();

        $settings = app('settings');

        if ($request->has('cache_routes') && $request->session()->get('onlyadmin.unlocked') === true) {
            return $this->rebuildRouteCache();
        }

        return view('onlyadmin.index', [
            'school_name' => $settings->get('school.name', config('app.name')),
            'school_address' => $settings->get('school.address', ''),
            'school_phone' => $settings->get('school.phone', ''),
            'school_email' => $settings->get('school.email', ''),
            'system_lock_enabled' => (string) $settings->get('system.lock.enabled', '0') === '1',
            'onlyadmin_unlocked' => $request->session()->get('onlyadmin.unlocked') === true,
        ]);
    }

    public function unlock(Request $request): RedirectResponse
    {
        $this->ensurePinExists();

        $validated = $request->validate([
            'pin' => ['required', 'digits:4'],
        ]);

        $hash = (string) app('settings')->get('onlyadmin.pin_hash', '');

        if ($hash === '' || ! Hash::check($validated['pin'], $hash)) {
            return back()->withErrors([
                'pin' => 'Invalid PIN.',
            ]);
        }

        $request->session()->put('onlyadmin.unlocked', true);

        return redirect()->route('onlyadmin.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('onlyadmin.unlocked');

        return redirect()->route('onlyadmin.index');
    }

    public function setSystemLock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'in:0,1'],
        ]);

        app('settings')->set('system.lock.enabled', $validated['enabled'], 'system');

        return back()->with('status', 'System lock updated.');
    }

    public function updatePin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_pin' => ['required', 'digits:4'],
            'new_pin' => ['required', 'digits:4', 'different:current_pin'],
            'new_pin_confirmation' => ['required', 'same:new_pin'],
        ]);

        $hash = (string) app('settings')->get('onlyadmin.pin_hash', '');

        if ($hash === '' || ! Hash::check($validated['current_pin'], $hash)) {
            return back()->withErrors([
                'current_pin' => 'Current PIN is incorrect.',
            ]);
        }

        app('settings')->set('onlyadmin.pin_hash', Hash::make($validated['new_pin']), 'onlyadmin');

        return back()->with('status', 'PIN updated.');
    }
}
