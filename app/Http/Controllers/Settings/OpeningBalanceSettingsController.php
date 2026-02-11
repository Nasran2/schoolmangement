<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class OpeningBalanceSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $settings = app('settings');

        $locked = (string) $settings->get('opening_balance.locked', '0') === '1';

        return view('settings.opening-balance', [
            'locked' => $locked,
            'as_of' => (string) $settings->get('opening_balance.as_of', ''),
            'cash_amount' => (string) $settings->get('opening_balance.cash', ''),
            'bank_amount' => (string) $settings->get('opening_balance.bank', ''),
            'set_at' => (string) $settings->get('opening_balance.set_at', ''),
            'set_by' => (string) $settings->get('opening_balance.set_by', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = app('settings');

        $locked = (string) $settings->get('opening_balance.locked', '0') === '1';
        if ($locked) {
            return back()->withErrors([
                'opening_balance' => 'Opening balance is already set and cannot be changed.',
            ]);
        }

        $validated = $request->validate([
            'as_of' => ['required', 'date'],
            'cash_amount' => ['required', 'numeric', 'min:0'],
            'bank_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $settings->setMany([
            'opening_balance.as_of' => $validated['as_of'],
            'opening_balance.cash' => number_format((float) $validated['cash_amount'], 2, '.', ''),
            'opening_balance.bank' => number_format((float) $validated['bank_amount'], 2, '.', ''),
            'opening_balance.set_at' => now()->toDateTimeString(),
            'opening_balance.set_by' => (string) ($request->user()?->id ?? ''),
            'opening_balance.locked' => '1',
        ], 'opening_balance');

        return back()->with('status', 'Opening balance saved. This can only be set once.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $keys = [
            'opening_balance.locked',
            'opening_balance.as_of',
            'opening_balance.cash',
            'opening_balance.bank',
            'opening_balance.set_at',
            'opening_balance.set_by',
        ];

        Setting::query()->whereIn('key', $keys)->delete();

        Cache::forget('settings.all');

        return back()->with('status', 'Opening balance was reset. You can set it again.');
    }
}
