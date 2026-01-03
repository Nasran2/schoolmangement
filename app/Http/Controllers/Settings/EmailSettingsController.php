<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class EmailSettingsController extends Controller
{
    public function edit(): View
    {
        $s = app('settings');

        return view('settings.email', [
            'smtp_host' => $s->get('smtp.host', ''),
            'smtp_port' => $s->get('smtp.port', ''),
            'smtp_username' => $s->get('smtp.username', ''),
            'smtp_encryption' => $s->get('smtp.encryption', ''),
            'from_address' => $s->get('smtp.from.address', ''),
            'from_name' => $s->get('smtp.from.name', ''),
            'has_password' => (string) $s->get('smtp.password', '') !== '',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'smtp_host' => ['required', 'string', 'max:255'],
            'smtp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', 'in:tls,ssl,'],
            'from_address' => ['nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
        ]);

        $host = trim((string) $validated['smtp_host']);
        if (str_contains($host, '://')) {
            $parsed = parse_url($host);
            if (is_array($parsed) && ! empty($parsed['host'])) {
                $host = (string) $parsed['host'];
            }
        }
        // Strip any accidental path/query and port.
        $host = preg_replace('~[/?#].*$~', '', $host) ?? $host;
        $host = preg_replace('#:\d+$#', '', $host) ?? $host;

        $s = app('settings');

        $values = [
            'smtp.host' => $host,
            'smtp.port' => (string) $validated['smtp_port'],
            'smtp.username' => $validated['smtp_username'] ?? '',
            'smtp.encryption' => $validated['smtp_encryption'] ?? '',
            'smtp.from.address' => $validated['from_address'] ?? '',
            'smtp.from.name' => $validated['from_name'] ?? '',
        ];

        // Only overwrite password when a new one is provided.
        if (($validated['smtp_password'] ?? '') !== '') {
            $values['smtp.password'] = $validated['smtp_password'];
        }

        $s->setMany($values, 'smtp');

        return back()->with('status', 'Email (SMTP) settings updated.');
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_to' => ['required', 'email', 'max:255'],
        ]);

        $host = (string) app('settings')->get('smtp.host', '');
        if ($host === '') {
            return back()->withErrors(['test_to' => 'SMTP is not configured. Please save SMTP settings first.']);
        }

        $schoolName = (string) app('settings')->get('school.name', config('app.name'));
        $now = now()->format('Y-m-d H:i:s');

        try {
            Mail::to($validated['test_to'])->send(new class($schoolName, $now) extends \Illuminate\Mail\Mailable {
                public function __construct(private readonly string $schoolName, private readonly string $now) {}

                public function build(): static
                {
                    return $this
                        ->subject($this->schoolName.' - SMTP Test')
                        ->html('<p>This is a test email from <strong>'.e($this->schoolName).'</strong>.</p><p>Sent at: '.e($this->now).'</p>');
                }
            });
        } catch (Throwable $e) {
            app('settings')->setMany([
                'smtp.last_tested_at' => now()->toDateTimeString(),
                'smtp.last_test_status' => 'failed',
                'smtp.last_test_error' => $e->getMessage(),
            ], 'smtp');

            return back()->withErrors([
                'test_to' => 'Failed to send test email: '.$e->getMessage(),
            ]);
        }

        app('settings')->setMany([
            'smtp.last_tested_at' => now()->toDateTimeString(),
            'smtp.last_test_status' => 'ok',
            'smtp.last_test_error' => '',
        ], 'smtp');

        return back()->with('status', 'Test email sent to '.$validated['test_to'].'.');
    }
}
