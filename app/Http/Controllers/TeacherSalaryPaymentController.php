<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\TeacherSalaryPayment;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherSalaryPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = TeacherSalaryPayment::query()->with('teacher');

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->string('teacher_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('paid_at', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('paid_at', '<=', $request->string('to'));
        }
        if ($request->filled('month')) {
            $query->where('payment_month', $request->string('month'));
        }

        $query->orderByDesc('paid_at');

        return view('teacher-salary-payments.index', [
            'payments' => $query->paginate(20)->withQueryString(),
            'teachers' => Teacher::query()->orderBy('name')->get(),
            'filters' => $request->only(['teacher_id', 'from', 'to', 'month']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $teachers = Teacher::query()->orderBy('name')->get();
        $deductionTypes = $this->getDeductionTypes(app(SettingsService::class));
        return view('teacher-salary-payments.create', [
            'teachers' => $teachers,
            'deductionTypes' => $deductionTypes,
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'base_salary' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'payment_month' => ['required', 'string'],
            'payment_method' => ['nullable', 'in:cash,bank,cheque'],
            'notes' => ['nullable', 'string'],
            'deductions' => ['nullable', 'array'],
            'deductions.*.reason' => ['required', 'string'],
            'deductions.*.amount' => ['required', 'numeric', 'min:0'],
        ]);

        $deductions = $validated['deductions'] ?? [];
        // Deduplicate EPF/ETF while preserving the first user-provided entry
        $normalized = [];
        $seen = ['epf' => false, 'etf' => false];
        foreach ($deductions as $d) {
            $reason = strtolower((string) ($d['reason'] ?? ''));
            if ($reason === 'epf' || $reason === 'etf') {
                if ($seen[$reason]) { continue; }
                $seen[$reason] = true;
            }
            $normalized[] = [
                'reason' => (string) ($d['reason'] ?? ''),
                'amount' => (float) ($d['amount'] ?? 0),
            ];
        }
        $deductions = $normalized;

        // Auto-apply EPF/ETF based on basic salary from settings
        $settings = app(SettingsService::class);
        $epfPercent = (float) ($settings->get('salary_epf_percent', '0') ?: 0);
        $etfPercent = (float) ($settings->get('salary_etf_percent', '0') ?: 0);
        $base = (float) $validated['base_salary'];
        $teacher = Teacher::query()->find((int) $validated['teacher_id']);
        $epfEnabled = true;
        $etfEnabled = true;
        if ($teacher) {
            // Default to enabled when flags are missing (pre-migration data)
            $epfEnabled = $teacher->epf_enabled === null ? true : (bool) $teacher->epf_enabled;
            $etfEnabled = $teacher->etf_enabled === null ? true : (bool) $teacher->etf_enabled;
        }

        $hasEpf = collect($deductions)->contains(function ($d) { return strtolower((string)($d['reason'] ?? '')) === 'epf'; });
        $hasEtf = collect($deductions)->contains(function ($d) { return strtolower((string)($d['reason'] ?? '')) === 'etf'; });

        if ($epfEnabled && $epfPercent > 0 && !$hasEpf) {
            $deductions[] = [
                'reason' => 'EPF',
                'amount' => round($base * ($epfPercent / 100), 2),
            ];
        }
        if ($etfEnabled && $etfPercent > 0 && !$hasEtf) {
            $deductions[] = [
                'reason' => 'ETF',
                'amount' => round($base * ($etfPercent / 100), 2),
            ];
        }
        // If teacher has EPF/ETF disabled, strip them even if present
        if (!$epfEnabled) {
            $deductions = array_values(array_filter($deductions, function ($d) { return strtolower((string)($d['reason'] ?? '')) !== 'epf'; }));
        }
        if (!$etfEnabled) {
            $deductions = array_values(array_filter($deductions, function ($d) { return strtolower((string)($d['reason'] ?? '')) !== 'etf'; }));
        }

        $totalDeductions = collect($deductions)->sum('amount');
        $finalAmount = $base - $totalDeductions;

        $payment = TeacherSalaryPayment::create([
            'teacher_id' => (int) $validated['teacher_id'],
            'base_salary' => $validated['base_salary'],
            'deductions' => $deductions,
            'total_deductions' => $totalDeductions,
            'amount' => $finalAmount,
            'paid_at' => $validated['paid_at'],
            'payment_month' => $validated['payment_month'],
            'payment_method' => $validated['payment_method'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('teacher-salary-payments.show', $payment)->with('status', 'Salary payment recorded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TeacherSalaryPayment $teacherSalaryPayment): View
    {
        $teacherSalaryPayment->load('teacher', 'creator');

        return view('teacher-salary-payments.show', [
            'payment' => $teacherSalaryPayment,
        ]);
    }

    /**
     * Print receipt
     */
    public function receipt(TeacherSalaryPayment $teacherSalaryPayment)
    {
        $teacherSalaryPayment->load('teacher');

        $html = view('teacher-salary-payments.receipt', [
            'payment' => $teacherSalaryPayment,
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a5')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);

        return $pdf->download('receipt-' . $teacherSalaryPayment->receipt_number . '.pdf');
    }

    /**
     * Print payslip
     */
    public function payslip(TeacherSalaryPayment $teacherSalaryPayment)
    {
        $teacherSalaryPayment->load('teacher', 'creator');

        $html = view('teacher-salary-payments.payslip', [
            'payment' => $teacherSalaryPayment,
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a5')
            ->setOption('margin-top', 5)
            ->setOption('margin-bottom', 5)
            ->setOption('margin-left', 5)
            ->setOption('margin-right', 5);

        return $pdf->download('payslip-' . $teacherSalaryPayment->receipt_number . '.pdf');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TeacherSalaryPayment $teacherSalaryPayment): View
    {
        $teachers = Teacher::query()->orderBy('name')->get();

        return view('teacher-salary-payments.edit', [
            'payment' => $teacherSalaryPayment,
            'teachers' => $teachers,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TeacherSalaryPayment $teacherSalaryPayment): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'base_salary' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'payment_month' => ['required', 'string'],
            'payment_method' => ['nullable', 'in:cash,bank,cheque'],
            'notes' => ['nullable', 'string'],
            'deductions' => ['nullable', 'array'],
            'deductions.*.reason' => ['required', 'string'],
            'deductions.*.amount' => ['required', 'numeric', 'min:0'],
        ]);

        $deductions = $validated['deductions'] ?? [];
        // Deduplicate EPF/ETF while preserving the first user-provided entry
        $normalized = [];
        $seen = ['epf' => false, 'etf' => false];
        foreach ($deductions as $d) {
            $reason = strtolower((string) ($d['reason'] ?? ''));
            if ($reason === 'epf' || $reason === 'etf') {
                if ($seen[$reason]) { continue; }
                $seen[$reason] = true;
            }
            $normalized[] = [
                'reason' => (string) ($d['reason'] ?? ''),
                'amount' => (float) ($d['amount'] ?? 0),
            ];
        }
        $deductions = $normalized;

        // Re-apply EPF/ETF on update to reflect current settings
        $settings = app(SettingsService::class);
        $epfPercent = (float) ($settings->get('salary_epf_percent', '0') ?: 0);
        $etfPercent = (float) ($settings->get('salary_etf_percent', '0') ?: 0);
        $base = (float) $validated['base_salary'];
        $teacher = Teacher::query()->find((int) $validated['teacher_id']);
        $epfEnabled = true;
        $etfEnabled = true;
        if ($teacher) {
            // Default to enabled when flags are missing (pre-migration data)
            $epfEnabled = $teacher->epf_enabled === null ? true : (bool) $teacher->epf_enabled;
            $etfEnabled = $teacher->etf_enabled === null ? true : (bool) $teacher->etf_enabled;
        }

        $hasEpf = collect($deductions)->contains(function ($d) { return strtolower((string)($d['reason'] ?? '')) === 'epf'; });
        $hasEtf = collect($deductions)->contains(function ($d) { return strtolower((string)($d['reason'] ?? '')) === 'etf'; });

        if ($epfEnabled && $epfPercent > 0 && !$hasEpf) {
            $deductions[] = [
                'reason' => 'EPF',
                'amount' => round($base * ($epfPercent / 100), 2),
            ];
        }
        if ($etfEnabled && $etfPercent > 0 && !$hasEtf) {
            $deductions[] = [
                'reason' => 'ETF',
                'amount' => round($base * ($etfPercent / 100), 2),
            ];
        }
        // If teacher has EPF/ETF disabled, strip them even if present
        if (!$epfEnabled) {
            $deductions = array_values(array_filter($deductions, function ($d) { return strtolower((string)($d['reason'] ?? '')) !== 'epf'; }));
        }
        if (!$etfEnabled) {
            $deductions = array_values(array_filter($deductions, function ($d) { return strtolower((string)($d['reason'] ?? '')) !== 'etf'; }));
        }

        $totalDeductions = collect($deductions)->sum('amount');
        $finalAmount = $base - $totalDeductions;

        $teacherSalaryPayment->update([
            'teacher_id' => (int) $validated['teacher_id'],
            'base_salary' => $validated['base_salary'],
            'deductions' => $deductions,
            'total_deductions' => $totalDeductions,
            'amount' => $finalAmount,
            'paid_at' => $validated['paid_at'],
            'payment_month' => $validated['payment_month'],
            'payment_method' => $validated['payment_method'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('teacher-salary-payments.show', $teacherSalaryPayment)->with('status', 'Salary payment updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TeacherSalaryPayment $teacherSalaryPayment): RedirectResponse
    {
        $teacherSalaryPayment->delete();

        return redirect()->route('teacher-salary-payments.index')->with('status', 'Salary payment deleted.');
    }

    /**
     * @return array<int,string>
     */
    private function getDeductionTypes(SettingsService $settings): array
    {
        $defaults = [
            'Leave Deduction',
            'Loan Recovery',
            'Advance Adjustment',
            'Late Arrival',
            'Penalty',
        ];

        $raw = $settings->get('salary_deduction_types', '');
        $decoded = json_decode((string) $raw, true);

        if (is_array($decoded) && count($decoded) > 0) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }

        return $defaults;
    }
}
