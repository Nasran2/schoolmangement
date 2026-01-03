<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrinterSettingsController extends Controller
{
    public function edit(): View
    {
        $s = app('settings');
        return view('settings.printer', [
            'slip_header' => $s->get('printer.slip.header', ''),
            'slip_footer' => $s->get('printer.slip.footer', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slip_header' => ['nullable', 'string', 'max:500'],
            'slip_footer' => ['nullable', 'string', 'max:500'],
        ]);

        app('settings')->setMany([
            'printer.slip.header' => $validated['slip_header'] ?? '',
            'printer.slip.footer' => $validated['slip_footer'] ?? '',
            'printer.last_updated_at' => now()->toDateTimeString(),
        ], 'printer');

        return back()->with('status', 'Printer settings updated.');
    }
}
