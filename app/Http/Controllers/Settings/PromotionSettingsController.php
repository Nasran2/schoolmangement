<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionSettingsController extends Controller
{
    public function edit(): View
    {
        $s = app('settings');

        $monthDay = $s->get('promotion.auto.month_day', '');
        $month = null;
        $day = null;

        if (preg_match('/^(\d{2})-(\d{2})$/', $monthDay, $matches)) {
            $month = (int) $matches[1];
            $day = (int) $matches[2];
        }

        return view('settings.promotion', [
            'auto_enabled' => $s->get('promotion.auto.enabled', '0'),
            'month' => $month,
            'day' => $day,
            'last_year_run' => $s->get('promotion.auto.last_year_run', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'auto_enabled' => ['nullable', 'in:0,1'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'day' => ['nullable', 'integer', 'between:1,31'],
        ]);

        $autoEnabled = $validated['auto_enabled'] ?? '0';
        $month = $validated['month'] ?? null;
        $day = $validated['day'] ?? null;

        if ($autoEnabled === '1') {
            if (! $month || ! $day || ! checkdate((int) $month, (int) $day, (int) now()->format('Y'))) {
                return back()->withErrors(['month' => 'Select a valid month/day combination.'])->withInput();
            }
        }

        $monthDay = ($autoEnabled === '1' && $month && $day)
            ? sprintf('%02d-%02d', $month, $day)
            : '';

        app('settings')->setMany([
            'promotion.auto.enabled' => $autoEnabled,
            'promotion.auto.month_day' => $monthDay,
        ], 'promotion');

        return back()->with('status', 'Promotion settings updated.');
    }
}
