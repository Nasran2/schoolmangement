<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\SMS\SmsService;
use Illuminate\View\View;

class SystemStatusController extends Controller
{
    public function index(SmsService $sms): View
    {
        $s = app('settings');

        $smtpConfigured = (string) $s->get('smtp.host', '') !== '';
        $smsConfigured = $sms->isConfigured();
        $printerConfigured = (string) $s->get('printer.slip.header', '') !== '' || (string) $s->get('printer.slip.footer', '') !== '';

        return view('settings.status', [
            'smtp' => [
                'configured' => $smtpConfigured,
                'last_tested_at' => (string) $s->get('smtp.last_tested_at', ''),
                'last_test_status' => (string) $s->get('smtp.last_test_status', ''),
                'last_test_error' => (string) $s->get('smtp.last_test_error', ''),
            ],
            'sms' => [
                'configured' => $smsConfigured,
                'last_tested_at' => (string) $s->get('sms.last_tested_at', ''),
                'last_test_status' => (string) $s->get('sms.last_test_status', ''),
                'last_test_error' => (string) $s->get('sms.last_test_error', ''),
            ],
            'printer' => [
                'configured' => $printerConfigured,
                'last_updated_at' => (string) $s->get('printer.last_updated_at', ''),
            ],
        ]);
    }
}
