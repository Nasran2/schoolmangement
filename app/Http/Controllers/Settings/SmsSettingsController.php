<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\SMS\SmsService;
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
            'last_tested_at' => $s->get('sms.last_tested_at', ''),
            'last_test_status' => $s->get('sms.last_test_status', ''),
            'last_test_error' => $s->get('sms.last_test_error', ''),
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

    public function sendTest(Request $request, SmsService $sms): RedirectResponse
    {
        $validated = $request->validate([
            'test_phone' => ['required', 'string', 'max:30'],
            'test_message' => ['nullable', 'string', 'max:160'],
        ]);

        if (! $sms->isConfigured()) {
            return back()->withErrors(['test_phone' => 'SMS is not configured. Please save SMS settings first.']);
        }

        $message = trim((string) ($validated['test_message'] ?? ''));
        if ($message === '') {
            $schoolName = (string) app('settings')->get('school.name', config('app.name'));
            $message = $schoolName.' - SMS test ('.now()->format('Y-m-d H:i').')';
        }

        $ok = $sms->send([(string) $validated['test_phone']], $message);

        if (! $ok) {
            app('settings')->setMany([
                'sms.last_tested_at' => now()->toDateTimeString(),
                'sms.last_test_status' => 'failed',
                'sms.last_test_error' => 'Gateway request failed.',
            ], 'sms');

            return back()->withErrors(['test_phone' => 'Failed to send SMS test. Please check gateway settings.']);
        }

        app('settings')->setMany([
            'sms.last_tested_at' => now()->toDateTimeString(),
            'sms.last_test_status' => 'ok',
            'sms.last_test_error' => '',
        ], 'sms');

        return back()->with('status', 'Test SMS sent to '.$validated['test_phone'].'.');
    }
}
