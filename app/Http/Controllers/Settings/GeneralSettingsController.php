<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class GeneralSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $settings = app('settings');

        return view('settings.general', [
            'school_name' => $settings->get('school.name', config('app.name')),
            'school_logo' => $settings->get('school.logo'),
            'academic_year' => $settings->get('school.academic_year', ''),
            'school_address' => $settings->get('school.address', ''),
            'school_phone' => $settings->get('school.phone', ''),
            'school_email' => $settings->get('school.email', ''),
            'auto_print_receipt' => $settings->get('receipt.auto_print', '0'),

            'auto_email_teacher_payslip' => $settings->get('salary.auto_email_payslip', '0'),

            'revenue_bill_autogenerate' => $settings->get('billing.revenue.autogenerate', '1'),
            'revenue_bill_prefix' => $settings->get('billing.revenue.prefix', 'BILL-'),
            'revenue_bill_start_number' => $settings->get('billing.revenue.start_number', '1000'),
            'revenue_bill_next_number' => $settings->get('billing.revenue.next_number', $settings->get('billing.revenue.start_number', '1000')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:120'],
            'school_logo' => ['nullable', 'image', 'max:2048'], // 2MB Max
            'remove_logo' => ['nullable', 'in:0,1'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'school_address' => ['nullable', 'string', 'max:255'],
            'school_phone' => ['nullable', 'string', 'max:20'],
            'school_email' => ['nullable', 'email', 'max:120'],
            'auto_print_receipt' => ['nullable', 'in:0,1'],

            'auto_email_teacher_payslip' => ['nullable', 'in:0,1'],

            'revenue_bill_autogenerate' => ['nullable', 'in:0,1'],
            'revenue_bill_prefix' => ['nullable', 'string', 'max:20'],
            'revenue_bill_start_number' => ['required', 'integer', 'min:1'],
            'revenue_bill_next_number' => ['required', 'integer', 'min:1'],
        ]);

        // Handle logo remove
        if (($validated['remove_logo'] ?? '0') === '1') {
            $existing = app('settings')->get('school.logo');
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }
            app('settings')->set('school.logo', '', 'general');
        }

        // Handle logo upload
        if ($request->hasFile('school_logo')) {
            $path = $request->file('school_logo')->store('logos', 'public');
            app('settings')->set('school.logo', $path, 'general');
        }

        app('settings')->setMany([
            'school.name' => $validated['school_name'],
            'school.academic_year' => $validated['academic_year'] ?? '',
            'school.address' => $validated['school_address'] ?? '',
            'school.phone' => $validated['school_phone'] ?? '',
            'school.email' => $validated['school_email'] ?? '',
            'receipt.auto_print' => $validated['auto_print_receipt'] ?? '0',

            'salary.auto_email_payslip' => $validated['auto_email_teacher_payslip'] ?? '0',

            'billing.revenue.autogenerate' => $validated['revenue_bill_autogenerate'] ?? '0',
            'billing.revenue.prefix' => $validated['revenue_bill_prefix'] ?? '',
            'billing.revenue.start_number' => (string) $validated['revenue_bill_start_number'],
            'billing.revenue.next_number' => (string) $validated['revenue_bill_next_number'],
        ], 'general');

        return back()->with('status', 'Settings updated.');
    }
}
