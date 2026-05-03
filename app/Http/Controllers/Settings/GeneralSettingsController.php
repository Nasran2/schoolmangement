<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeneralSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $settings = app('settings');

        return view('settings.general', [
            'school_name' => $settings->get('school.name', config('app.name')),
            'school_logo' => $settings->get('school.logo'),
            'login_background' => $settings->get('ui.login.background'),
            'academic_year' => $settings->get('school.academic_year', ''),
            'school_address' => $settings->get('school.address', ''),
            'school_phone' => $settings->get('school.phone', ''),
            'school_email' => $settings->get('school.email', ''),
            'auto_print_receipt' => $settings->get('receipt.auto_print', '0'),
            'receipt_paper_size' => $settings->get('receipt.paper_size', '5.5in 11in'),
            'receipt_paper_width' => $settings->get('receipt.paper_width', '5.5in'),
            'receipt_paper_height' => $settings->get('receipt.paper_height', '11in'),

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
            'login_background' => ['nullable', 'image', 'max:6144'], // 6MB Max
            'remove_login_background' => ['nullable', 'in:0,1'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'school_address' => ['nullable', 'string', 'max:255'],
            'school_phone' => ['nullable', 'string', 'max:20'],
            'school_email' => ['nullable', 'email', 'max:120'],
            'auto_print_receipt' => ['nullable', 'in:0,1'],
            'receipt_paper_width' => ['required', 'string', 'max:20'],
            'receipt_paper_height' => ['required', 'string', 'max:20'],

            'auto_email_teacher_payslip' => ['nullable', 'in:0,1'],

            'revenue_bill_autogenerate' => ['nullable', 'in:0,1'],
            'revenue_bill_prefix' => ['nullable', 'string', 'max:20'],
            'revenue_bill_start_number' => ['required', 'integer', 'min:1'],
            'revenue_bill_next_number' => ['required', 'integer', 'min:1'],
        ]);

        $disk = Storage::disk('public');
        $removeLogo = ($validated['remove_logo'] ?? '0') === '1';
        $removeBackground = ($validated['remove_login_background'] ?? '0') === '1';
        $currentLogo = (string) app('settings')->get('school.logo', '');
        $currentBackground = (string) app('settings')->get('ui.login.background', '');

        $newLogoPath = null;
        $newBackgroundPath = null;

        try {
            if ($request->hasFile('school_logo')) {
                $newLogoPath = $request->file('school_logo')->store('logos', 'public');
            }

            if ($request->hasFile('login_background')) {
                $newBackgroundPath = $request->file('login_background')->store('branding', 'public');
            }

            $settingsPayload = [
            'school.name' => $validated['school_name'],
            'school.academic_year' => $validated['academic_year'] ?? '',
            'school.address' => $validated['school_address'] ?? '',
            'school.phone' => $validated['school_phone'] ?? '',
            'school.email' => $validated['school_email'] ?? '',
            'receipt.auto_print' => $validated['auto_print_receipt'] ?? '0',
            'receipt.paper_size' => trim($validated['receipt_paper_width'] . ' ' . $validated['receipt_paper_height']),
            'receipt.paper_width' => $validated['receipt_paper_width'],
            'receipt.paper_height' => $validated['receipt_paper_height'],

            'salary.auto_email_payslip' => $validated['auto_email_teacher_payslip'] ?? '0',

            'billing.revenue.autogenerate' => $validated['revenue_bill_autogenerate'] ?? '0',
            'billing.revenue.prefix' => $validated['revenue_bill_prefix'] ?? '',
            'billing.revenue.start_number' => (string) $validated['revenue_bill_start_number'],
            'billing.revenue.next_number' => (string) $validated['revenue_bill_next_number'],
            ];

            if ($removeLogo || $newLogoPath) {
                $settingsPayload['school.logo'] = $newLogoPath ?? '';
            }

            if ($removeBackground || $newBackgroundPath) {
                $settingsPayload['ui.login.background'] = $newBackgroundPath ?? '';
            }

            app('settings')->setMany($settingsPayload, 'general');

            if (($removeLogo || $newLogoPath) && $currentLogo !== '' && $currentLogo !== $newLogoPath) {
                $disk->delete($currentLogo);
            }

            if (($removeBackground || $newBackgroundPath) && $currentBackground !== '' && $currentBackground !== $newBackgroundPath) {
                $disk->delete($currentBackground);
            }
        } catch (\Throwable $e) {
            if ($newLogoPath) {
                $disk->delete($newLogoPath);
            }
            if ($newBackgroundPath) {
                $disk->delete($newBackgroundPath);
            }

            Log::error('General settings update failed.', [
                'user_id' => $request->user()?->id,
                'has_logo_upload' => $request->hasFile('school_logo'),
                'has_background_upload' => $request->hasFile('login_background'),
                'route' => $request->route()?->getName(),
                'action' => optional($request->route())->getActionName(),
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors([
                'general_settings' => 'Settings update failed. Please try again.',
            ]);
        }

        return back()->with('status', 'Settings updated.');
    }
}
