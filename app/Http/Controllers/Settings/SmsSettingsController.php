<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsSettingsController extends Controller
{
    public function edit(): View
    {
        $s = app('settings');

        return view('settings.sms', [
            'gateway_url' => $s->get('sms.gateway.url', ''),
            'gateway_token' => $s->get('sms.gateway.token', ''),
            'sender' => $s->get('sms.sender', ''),
            'due_template' => $s->get('sms.template.due', 'Dear {name}, your due is {amount}. Please pay by {date}.'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gateway_url' => ['required', 'url'],
            'gateway_token' => ['required', 'string'],
            'sender' => ['nullable', 'string', 'max:20'],
            'due_template' => ['required', 'string', 'max:500'],
        ]);

        app('settings')->setMany([
            'sms.gateway.url' => $validated['gateway_url'],
            'sms.gateway.token' => $validated['gateway_token'],
            'sms.sender' => $validated['sender'] ?? '',
            'sms.template.due' => $validated['due_template'],
        ], 'sms');

        return back()->with('status', 'SMS settings updated.');
    }
}
