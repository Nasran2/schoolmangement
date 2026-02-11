<?php

namespace App\Http\Controllers;

use App\Mail\TeacherPayslipMail;
use App\Models\Teacher;
use App\Models\TeacherSalaryPayment;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class TeacherSalaryPaymentController extends Controller
{
    /**
     * Salary due/upcoming summary by month.
     */
    public function summary(Request $request): View
    {
        $month = (string) $request->query('month', now()->format('Y-m'));
        try {
            $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable $e) {
            $monthStart = now()->startOfMonth();
            $month = $monthStart->format('Y-m');
        }
        $monthEnd = $monthStart->copy()->endOfMonth();

        $payments = TeacherSalaryPayment::query()
            ->with(['teacher'])
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->orderByDesc('paid_at')
            ->get();

        $paidByTeacherId = $payments
            ->groupBy('teacher_id')
            ->map(function ($rows) {
                /** @var \Illuminate\Support\Collection $rows */
                $first = $rows->first();
                return [
                    'payment' => $first,
                    'total_paid' => (float) $rows->sum('amount'),
                ];
            });

        $teachers = Teacher::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $dueTeachers = $teachers->filter(function (Teacher $t) use ($paidByTeacherId) {
            if ((float) ($t->salary_amount ?? 0) <= 0) return false;
            return ! $paidByTeacherId->has($t->id);
        })->values();

        $paidTeachers = $teachers->filter(function (Teacher $t) use ($paidByTeacherId) {
            return $paidByTeacherId->has($t->id);
        })->values();

        $dueTotal = (float) $dueTeachers->sum(fn (Teacher $t) => (float) ($t->salary_amount ?? 0));
        $paidTotal = (float) $payments->sum('amount');

        $settings = app(SettingsService::class);
        $deadlineDay = (int) ($settings->get('salary.payment_deadline_day', '25') ?: 25);
        $deadlineDay = max(1, min(28, $deadlineDay));
        $deadlineDate = $monthStart->copy()->addDays($deadlineDay - 1);

        $nextMonthStart = $monthStart->copy()->addMonthNoOverflow()->startOfMonth();
        $nextMonthLabel = $nextMonthStart->format('F Y');
        $nextMonthTotalExpected = (float) $teachers
            ->filter(fn (Teacher $t) => (float) ($t->salary_amount ?? 0) > 0)
            ->sum(fn (Teacher $t) => (float) ($t->salary_amount ?? 0));

        return view('teacher-salary-payments.summary', [
            'month' => $month,
            'monthLabel' => $monthStart->format('F Y'),
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
            'deadlineDate' => $deadlineDate,
            'teachers' => $teachers,
            'payments' => $payments,
            'paidByTeacherId' => $paidByTeacherId,
            'dueTeachers' => $dueTeachers,
            'paidTeachers' => $paidTeachers,
            'dueTotal' => $dueTotal,
            'paidTotal' => $paidTotal,
            'nextMonthLabel' => $nextMonthLabel,
            'nextMonthTotalExpected' => $nextMonthTotalExpected,
        ]);
    }

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
    public function create(Request $request): View
    {
        $teachers = Teacher::query()->orderBy('name')->get();
        $deductionTypes = $this->getDeductionTypes(app(SettingsService::class));

        $prefillTeacherId = null;
        if ($request->filled('teacher_id')) {
            $requestedId = (int) $request->query('teacher_id');
            if ($requestedId > 0 && $teachers->firstWhere('id', $requestedId)) {
                $prefillTeacherId = $requestedId;
            }
        }

        return view('teacher-salary-payments.create', [
            'teachers' => $teachers,
            'deductionTypes' => $deductionTypes,
            'prefillTeacherId' => $prefillTeacherId,
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
        // Deduplicate EPF while preserving the first user-provided entry
        $normalized = [];
        $seen = ['epf' => false];
        foreach ($deductions as $d) {
            $reason = strtolower((string) ($d['reason'] ?? ''));
            if ($reason === 'epf') {
                if ($seen['epf']) { continue; }
                $seen['epf'] = true;
            }
            $normalized[] = [
                'reason' => (string) ($d['reason'] ?? ''),
                'amount' => (float) ($d['amount'] ?? 0),
            ];
        }
        $deductions = $normalized;

        // Auto-apply Employee EPF (deduction) + compute Employer EPF/ETF (company contributions)
        $settings = app(SettingsService::class);

        $employeeEpfPercent = (float) ($settings->get('salary_epf_employee_percent', (string) ($settings->get('salary_epf_percent', '0') ?: '0')) ?: 0);
        $employerEpfPercent = (float) ($settings->get('salary_epf_employer_percent', '12') ?: 12);
        $employerEtfPercent = (float) ($settings->get('salary_etf_employer_percent', (string) ($settings->get('salary_etf_percent', '3') ?: '3')) ?: 3);

        $totalSalary = (float) $validated['base_salary'];
        $teacher = Teacher::query()->find((int) $validated['teacher_id']);
        $epfEnabled = true;
        $etfEnabled = true;
        if ($teacher) {
            // Default to enabled when flags are missing (pre-migration data)
            $epfEnabled = $teacher->epf_enabled === null ? true : (bool) $teacher->epf_enabled;
            $etfEnabled = $teacher->etf_enabled === null ? true : (bool) $teacher->etf_enabled;
        }

        $basicSalary = 0.0;
        if ($teacher) {
            $basicSalary = (float) data_get(
                collect($teacher->salary_components ?? [])->firstWhere('type', 'Basic Salary'),
                'amount',
                0
            );
        }
        $epfBaseSalary = $basicSalary > 0 ? $basicSalary : $totalSalary;

        $hasEmployeeEpf = collect($deductions)->contains(function ($d) { return strtolower((string)($d['reason'] ?? '')) === 'epf'; });

        $computedEmployeeEpf = 0.0;
        if ($epfEnabled && $employeeEpfPercent > 0) {
            $computedEmployeeEpf = round($epfBaseSalary * ($employeeEpfPercent / 100), 2);
            if (! $hasEmployeeEpf) {
                $deductions[] = [
                    'reason' => 'EPF',
                    'amount' => $computedEmployeeEpf,
                ];
            }
        }

        // If teacher has EPF disabled, strip it even if present
        if (! $epfEnabled) {
            $deductions = array_values(array_filter($deductions, function ($d) {
                return strtolower((string)($d['reason'] ?? '')) !== 'epf';
            }));
        }

        $employeeEpfAmount = 0.0;
        $epfRow = collect($deductions)->first(function ($d) {
            return strtolower((string)($d['reason'] ?? '')) === 'epf';
        });
        if ($epfEnabled && $employeeEpfPercent > 0) {
            $employeeEpfAmount = $epfRow ? (float) ($epfRow['amount'] ?? 0) : (float) $computedEmployeeEpf;
        }

        $employerEpfAmount = ($epfEnabled && $employerEpfPercent > 0)
            ? round($epfBaseSalary * ($employerEpfPercent / 100), 2)
            : 0.0;
        $employerEtfAmount = ($etfEnabled && $employerEtfPercent > 0)
            ? round($epfBaseSalary * ($employerEtfPercent / 100), 2)
            : 0.0;

        $totalDeductions = collect($deductions)->sum('amount');
        $finalAmount = $totalSalary - $totalDeductions;

        $payment = TeacherSalaryPayment::create([
            'teacher_id' => (int) $validated['teacher_id'],
            'base_salary' => $validated['base_salary'],
            'deductions' => $deductions,
            'total_deductions' => $totalDeductions,
            'employee_epf_amount' => $employeeEpfAmount,
            'employer_epf_amount' => $employerEpfAmount,
            'employer_etf_amount' => $employerEtfAmount,
            'amount' => $finalAmount,
            'paid_at' => $validated['paid_at'],
            'payment_month' => $validated['payment_month'],
            'payment_method' => $validated['payment_method'] ?? null,
            'bank_name' => $request->string('bank_name')->toString() ?: null,
            'bank_branch' => $request->string('bank_branch')->toString() ?: null,
            'bank_account_no' => $request->string('bank_account_no')->toString() ?: null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        $status = 'Salary payment recorded successfully.';

        // Optionally email payslip automatically
        $autoEmail = (string) $settings->get('salary.auto_email_payslip', '0');
        if ($autoEmail === '1') {
            try {
                $teacher = $teacher ?? null;
                if (! $teacher) {
                    $teacher = Teacher::query()->find((int) $validated['teacher_id']);
                }

                if (! $teacher || ! $teacher->email) {
                    $status .= ' Payslip not emailed (teacher email missing).';
                } else {
                    $host = (string) app('settings')->get('smtp.host', '');
                    if ($host === '') {
                        $status .= ' Payslip not emailed (SMTP not configured).';
                    } else {
                        $payment->load('teacher', 'creator');

                        $html = view('teacher-salary-payments.payslip', [
                            'payment' => $payment,
                        ])->render();

                        $pdf = Pdf::loadHTML($html)
                            ->setPaper('a5')
                            ->setOption('margin-top', 5)
                            ->setOption('margin-bottom', 5)
                            ->setOption('margin-left', 5)
                            ->setOption('margin-right', 5);

                        $binary = $pdf->output();
                        $filename = 'payslip-' . $payment->receipt_number . '.pdf';

                        Mail::to($teacher->email)->send(new TeacherPayslipMail($payment, $binary, $filename));
                        $status .= ' Payslip emailed to ' . $teacher->email . '.';
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Auto email payslip failed', [
                    'teacher_id' => (int) $validated['teacher_id'],
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
                $status .= ' Payslip email failed (see logs).';
            }
        }

        return redirect()->route('teacher-salary-payments.show', $payment)->with('status', $status);
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
     * Email payslip PDF to teacher.
     */
    public function emailPayslip(Request $request, TeacherSalaryPayment $teacherSalaryPayment): RedirectResponse
    {
        $teacherSalaryPayment->load('teacher', 'creator');

        $teacher = $teacherSalaryPayment->teacher;
        if (! $teacher || ! $teacher->email) {
            return back()->withErrors(['email' => 'Teacher email is missing. Please add an email for this teacher.']);
        }

        $host = (string) app('settings')->get('smtp.host', '');
        if ($host === '') {
            return back()->withErrors(['email' => 'SMTP is not configured. Please set Email (SMTP) settings first.']);
        }

        $html = view('teacher-salary-payments.payslip', [
            'payment' => $teacherSalaryPayment,
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a5')
            ->setOption('margin-top', 5)
            ->setOption('margin-bottom', 5)
            ->setOption('margin-left', 5)
            ->setOption('margin-right', 5);

        $binary = $pdf->output();
        $filename = 'payslip-' . $teacherSalaryPayment->receipt_number . '.pdf';

        Mail::to($teacher->email)->send(new TeacherPayslipMail($teacherSalaryPayment, $binary, $filename));

        return back()->with('status', 'Payslip emailed to '.$teacher->email.'.');
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
        $before = [
            'teacher_id' => $teacherSalaryPayment->teacher_id,
            'base_salary' => (float) $teacherSalaryPayment->base_salary,
            'deductions' => $teacherSalaryPayment->deductions,
            'total_deductions' => (float) $teacherSalaryPayment->total_deductions,
            'amount' => (float) $teacherSalaryPayment->amount,
            'paid_at' => optional($teacherSalaryPayment->paid_at)->toDateString(),
            'payment_month' => $teacherSalaryPayment->payment_month,
            'payment_method' => $teacherSalaryPayment->payment_method,
            'bank_name' => $teacherSalaryPayment->bank_name ?? null,
            'bank_branch' => $teacherSalaryPayment->bank_branch ?? null,
            'bank_account_no' => $teacherSalaryPayment->bank_account_no ?? null,
            'notes' => $teacherSalaryPayment->notes,
        ];
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
        // Deduplicate EPF while preserving the first user-provided entry
        $normalized = [];
        $seen = ['epf' => false];
        foreach ($deductions as $d) {
            $reason = strtolower((string) ($d['reason'] ?? ''));
            if ($reason === 'epf') {
                if ($seen['epf']) { continue; }
                $seen['epf'] = true;
            }
            $normalized[] = [
                'reason' => (string) ($d['reason'] ?? ''),
                'amount' => (float) ($d['amount'] ?? 0),
            ];
        }
        $deductions = $normalized;

        // Re-apply Employee EPF (deduction) + recompute Employer EPF/ETF (company contributions)
        $settings = app(SettingsService::class);

        $employeeEpfPercent = (float) ($settings->get('salary_epf_employee_percent', (string) ($settings->get('salary_epf_percent', '0') ?: '0')) ?: 0);
        $employerEpfPercent = (float) ($settings->get('salary_epf_employer_percent', '12') ?: 12);
        $employerEtfPercent = (float) ($settings->get('salary_etf_employer_percent', (string) ($settings->get('salary_etf_percent', '3') ?: '3')) ?: 3);

        $totalSalary = (float) $validated['base_salary'];
        $teacher = Teacher::query()->find((int) $validated['teacher_id']);
        $epfEnabled = true;
        $etfEnabled = true;
        if ($teacher) {
            // Default to enabled when flags are missing (pre-migration data)
            $epfEnabled = $teacher->epf_enabled === null ? true : (bool) $teacher->epf_enabled;
            $etfEnabled = $teacher->etf_enabled === null ? true : (bool) $teacher->etf_enabled;
        }

        $basicSalary = 0.0;
        if ($teacher) {
            $basicSalary = (float) data_get(
                collect($teacher->salary_components ?? [])->firstWhere('type', 'Basic Salary'),
                'amount',
                0
            );
        }
        $epfBaseSalary = $basicSalary > 0 ? $basicSalary : $totalSalary;

        $hasEmployeeEpf = collect($deductions)->contains(function ($d) { return strtolower((string)($d['reason'] ?? '')) === 'epf'; });

        $computedEmployeeEpf = 0.0;
        if ($epfEnabled && $employeeEpfPercent > 0) {
            $computedEmployeeEpf = round($epfBaseSalary * ($employeeEpfPercent / 100), 2);
            if (! $hasEmployeeEpf) {
                $deductions[] = [
                    'reason' => 'EPF',
                    'amount' => $computedEmployeeEpf,
                ];
            }
        }

        if (! $epfEnabled) {
            $deductions = array_values(array_filter($deductions, function ($d) {
                return strtolower((string)($d['reason'] ?? '')) !== 'epf';
            }));
        }

        $employeeEpfAmount = 0.0;
        $epfRow = collect($deductions)->first(function ($d) {
            return strtolower((string)($d['reason'] ?? '')) === 'epf';
        });
        if ($epfEnabled && $employeeEpfPercent > 0) {
            $employeeEpfAmount = $epfRow ? (float) ($epfRow['amount'] ?? 0) : (float) $computedEmployeeEpf;
        }

        $employerEpfAmount = ($epfEnabled && $employerEpfPercent > 0)
            ? round($epfBaseSalary * ($employerEpfPercent / 100), 2)
            : 0.0;
        $employerEtfAmount = ($etfEnabled && $employerEtfPercent > 0)
            ? round($epfBaseSalary * ($employerEtfPercent / 100), 2)
            : 0.0;

        $totalDeductions = collect($deductions)->sum('amount');
        $finalAmount = $totalSalary - $totalDeductions;

        $teacherSalaryPayment->update([
            'teacher_id' => (int) $validated['teacher_id'],
            'base_salary' => $validated['base_salary'],
            'deductions' => $deductions,
            'total_deductions' => $totalDeductions,
            'employee_epf_amount' => $employeeEpfAmount,
            'employer_epf_amount' => $employerEpfAmount,
            'employer_etf_amount' => $employerEtfAmount,
            'amount' => $finalAmount,
            'paid_at' => $validated['paid_at'],
            'payment_month' => $validated['payment_month'],
            'payment_method' => $validated['payment_method'] ?? null,
            'bank_name' => $request->string('bank_name')->toString() ?: null,
            'bank_branch' => $request->string('bank_branch')->toString() ?: null,
            'bank_account_no' => $request->string('bank_account_no')->toString() ?: null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // audit trail of changes
        try {
            $after = [
                'teacher_id' => $teacherSalaryPayment->teacher_id,
                'base_salary' => (float) $teacherSalaryPayment->base_salary,
                'deductions' => $teacherSalaryPayment->deductions,
                'total_deductions' => (float) $teacherSalaryPayment->total_deductions,
                'amount' => (float) $teacherSalaryPayment->amount,
                'paid_at' => optional($teacherSalaryPayment->paid_at)->toDateString(),
                'payment_month' => $teacherSalaryPayment->payment_month,
                'payment_method' => $teacherSalaryPayment->payment_method,
                'bank_name' => $teacherSalaryPayment->bank_name ?? null,
                'bank_branch' => $teacherSalaryPayment->bank_branch ?? null,
                'bank_account_no' => $teacherSalaryPayment->bank_account_no ?? null,
                'notes' => $teacherSalaryPayment->notes,
            ];
            app(\App\Services\AuditLogger::class)->log(
                'salary_payment.updated',
                $teacherSalaryPayment,
                'Salary payment updated',
                [
                    'before' => $before,
                    'after' => $after,
                ]
            );
        } catch (\Throwable $e) {
            // ignore logging errors
        }

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
