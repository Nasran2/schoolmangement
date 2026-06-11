<?php

namespace App\Http\Controllers;

use App\Mail\TeacherPayslipMail;
use App\Models\Expense;
use App\Models\Teacher;
use App\Models\TeacherSalaryAdvance;
use App\Models\TeacherSalaryAdvanceSettlement;
use App\Models\TeacherSalaryPayment;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class TeacherSalaryPaymentController extends Controller
{
    private const ADVANCE_DEDUCTION_REASON = 'Advance Adjustment';

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
            ->with(['teacher', 'advanceSettlements'])
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
                    'total_salary_settled' => (float) $rows->sum('base_salary'),
                ];
            });

        $teachers = Teacher::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();
        $pendingAdvanceByTeacherId = $this->pendingAdvanceMap($teachers->pluck('id')->all());

        $dueTeachers = $teachers->filter(function (Teacher $t) use ($paidByTeacherId) {
            if ((float) ($t->salary_amount ?? 0) <= 0) return false;
            return ! $paidByTeacherId->has($t->id);
        })->values();

        $paidTeachers = $teachers->filter(function (Teacher $t) use ($paidByTeacherId) {
            return $paidByTeacherId->has($t->id);
        })->values();

        $dueTotal = (float) $dueTeachers->sum(fn (Teacher $t) => (float) ($t->salary_amount ?? 0));
        $paidTotal = (float) $payments->sum('amount');
        $salarySettledTotal = (float) $payments->sum('base_salary');

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
            'salarySettledTotal' => $salarySettledTotal,
            'nextMonthLabel' => $nextMonthLabel,
            'nextMonthTotalExpected' => $nextMonthTotalExpected,
            'pendingAdvanceByTeacherId' => $pendingAdvanceByTeacherId,
            'pendingAdvanceTotal' => collect($pendingAdvanceByTeacherId)->sum('total'),
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = TeacherSalaryPayment::query()->with(['teacher', 'advanceSettlements']);

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
        $advanceQuery = TeacherSalaryAdvance::query()
            ->with(['teacher'])
            ->withSum('settlements as settled_amount', 'amount');

        if ($request->filled('teacher_id')) {
            $advanceQuery->where('teacher_id', $request->string('teacher_id'));
        }
        if ($request->filled('from')) {
            $advanceQuery->whereDate('paid_at', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $advanceQuery->whereDate('paid_at', '<=', $request->string('to'));
        }

        return view('teacher-salary-payments.index', [
            'payments' => $query->paginate(20)->withQueryString(),
            'teachers' => Teacher::query()->orderBy('name')->get(),
            'filters' => $request->only(['teacher_id', 'from', 'to', 'month']),
            'advances' => $advanceQuery->orderByDesc('paid_at')->paginate(10, ['*'], 'advances_page')->withQueryString(),
            'pendingAdvanceTotal' => collect($this->pendingAdvanceMap())->sum('total'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $teachers = Teacher::query()->orderBy('name')->get();
        $deductionTypes = $this->getDeductionTypes(app(SettingsService::class));
        $pendingAdvanceByTeacherId = $this->pendingAdvanceMap($teachers->pluck('id')->all());

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
            'pendingAdvanceByTeacherId' => $pendingAdvanceByTeacherId,
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

        $deductions = $this->withoutAdvanceAdjustmentRows($deductions);

        $nonAdvanceDeductions = (float) collect($deductions)->sum('amount');
        $advanceDeductionAmount = $this->advanceDeductionAmount(
            (int) $validated['teacher_id'],
            $totalSalary,
            $nonAdvanceDeductions
        );

        if ($advanceDeductionAmount > 0) {
            $deductions[] = [
                'reason' => self::ADVANCE_DEDUCTION_REASON,
                'amount' => $advanceDeductionAmount,
            ];
        }

        $totalDeductions = collect($deductions)->sum('amount');
        $finalAmount = $totalSalary - $totalDeductions;

        $payment = DB::transaction(function () use ($validated, $deductions, $totalDeductions, $employeeEpfAmount, $employerEpfAmount, $employerEtfAmount, $finalAmount, $request, $advanceDeductionAmount) {
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

            if ($advanceDeductionAmount > 0) {
                $this->settleSalaryAdvances($payment, $advanceDeductionAmount);
            }

            return $payment;
        });

        $status = 'Salary payment recorded successfully.';
        if ($advanceDeductionAmount > 0) {
            $status .= ' Advance deduction applied: Rs '.number_format($advanceDeductionAmount, 2).'.';
        }

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
                    'user_id' => $request->user()?->id,
                    'route' => $request->route()?->getName(),
                    'action' => optional($request->route())->getActionName(),
                    'error' => $e->getMessage(),
                ]);
                $status .= ' Payslip email failed (see logs).';
            }
        }

        return redirect()->route('teacher-salary-payments.show', $payment)->with('status', $status);
    }

    public function storeAdvance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'paid_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $teacher = Teacher::query()->findOrFail((int) $validated['teacher_id']);
        $paidAt = Carbon::parse($validated['paid_at'])->toDateString();
        $amount = (float) $validated['amount'];
        $notes = $validated['notes'] ?? null;
        $category = $this->salaryExpenseCategory();

        DB::transaction(function () use ($teacher, $paidAt, $amount, $notes, $category, $request) {
            $expenseNotes = 'Teacher salary advance - '.$teacher->name;
            if ($notes) {
                $expenseNotes .= ' - '.$notes;
            }

            $expense = Expense::create([
                'expense_category_id' => $category->id,
                'amount' => $amount,
                'payment_method' => 'cash',
                'expense_date' => $paidAt,
                'notes' => $expenseNotes,
                'created_by' => $request->user()?->id,
            ]);

            TeacherSalaryAdvance::create([
                'teacher_id' => $teacher->id,
                'expense_id' => $expense->id,
                'amount' => $amount,
                'paid_at' => $paidAt,
                'notes' => $notes,
                'created_by' => $request->user()?->id,
            ]);
        });

        return back()->with('status', 'Advance salary payment recorded and added to expenses.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TeacherSalaryPayment $teacherSalaryPayment): View
    {
        $teacherSalaryPayment->load('teacher', 'creator', 'advanceSettlements.advance');

        return view('teacher-salary-payments.show', [
            'payment' => $teacherSalaryPayment,
        ]);
    }

    /**
     * Print receipt
     */
    public function receipt(TeacherSalaryPayment $teacherSalaryPayment)
    {
        $teacherSalaryPayment->load('teacher', 'advanceSettlements.advance');

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
        $teacherSalaryPayment->load('teacher', 'creator', 'advanceSettlements.advance');

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
        $teacherSalaryPayment->load('teacher', 'creator', 'advanceSettlements.advance');

        $teacher = $teacherSalaryPayment->teacher;
        if (! $teacher || ! $teacher->email) {
            return back()->withErrors(['email' => 'Teacher email is missing. Please add an email for this teacher.']);
        }

        $host = (string) app('settings')->get('smtp.host', '');
        if ($host === '') {
            return back()->withErrors(['email' => 'SMTP is not configured. Please set Email (SMTP) settings first.']);
        }

        try {
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
        } catch (\Throwable $e) {
            Log::error('Manual payslip email failed.', [
                'payment_id' => $teacherSalaryPayment->id,
                'teacher_id' => $teacher->id,
                'user_id' => $request->user()?->id,
                'route' => $request->route()?->getName(),
                'action' => optional($request->route())->getActionName(),
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Unable to send payslip right now. Please try again.',
            ]);
        }

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
        DB::transaction(function () use ($teacherSalaryPayment) {
            $teacherSalaryPayment->advanceSettlements()->delete();
            $teacherSalaryPayment->delete();
        });

        return redirect()->route('teacher-salary-payments.index')->with('status', 'Salary payment deleted.');
    }

    /**
     * @param array<int,array{reason:string,amount:float}> $deductions
     * @return array<int,array{reason:string,amount:float}>
     */
    private function withoutAdvanceAdjustmentRows(array $deductions): array
    {
        return array_values(array_filter($deductions, function ($deduction) {
            return strtolower(trim((string) ($deduction['reason'] ?? ''))) !== strtolower(self::ADVANCE_DEDUCTION_REASON);
        }));
    }

    private function advanceDeductionAmount(int $teacherId, float $salary, float $existingDeductions): float
    {
        $available = max(0.0, $salary - $existingDeductions);
        if ($available <= 0) {
            return 0.0;
        }

        return round(min($available, $this->pendingAdvanceTotalForTeacher($teacherId)), 2);
    }

    private function pendingAdvanceTotalForTeacher(int $teacherId): float
    {
        $map = $this->pendingAdvanceMap([$teacherId]);

        return (float) ($map[$teacherId]['total'] ?? 0);
    }

    /**
     * @param array<int> $teacherIds
     * @return array<int,array{total:float,items:array<int,array<string,mixed>>}>
     */
    private function pendingAdvanceMap(array $teacherIds = []): array
    {
        $query = TeacherSalaryAdvance::query()
            ->with(['settlements'])
            ->orderBy('paid_at')
            ->orderBy('id');

        if ($teacherIds !== []) {
            $query->whereIn('teacher_id', $teacherIds);
        }

        $map = [];
        foreach ($query->get() as $advance) {
            $settled = (float) $advance->settlements->sum('amount');
            $balance = round((float) $advance->amount - $settled, 2);
            if ($balance <= 0) {
                continue;
            }

            $teacherId = (int) $advance->teacher_id;
            if (! isset($map[$teacherId])) {
                $map[$teacherId] = ['total' => 0.0, 'items' => []];
            }

            $map[$teacherId]['total'] = round($map[$teacherId]['total'] + $balance, 2);
            $map[$teacherId]['items'][] = [
                'id' => $advance->id,
                'date' => optional($advance->paid_at)->toDateString(),
                'amount' => (float) $advance->amount,
                'settled' => $settled,
                'balance' => $balance,
                'notes' => $advance->notes,
            ];
        }

        return $map;
    }

    private function settleSalaryAdvances(TeacherSalaryPayment $payment, float $amount): void
    {
        $remaining = round($amount, 2);
        if ($remaining <= 0) {
            return;
        }

        $advances = TeacherSalaryAdvance::query()
            ->with('settlements')
            ->where('teacher_id', $payment->teacher_id)
            ->orderBy('paid_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($advances as $advance) {
            if ($remaining <= 0) {
                break;
            }

            $settled = (float) $advance->settlements->sum('amount');
            $balance = round((float) $advance->amount - $settled, 2);
            if ($balance <= 0) {
                continue;
            }

            $applied = round(min($balance, $remaining), 2);

            TeacherSalaryAdvanceSettlement::create([
                'teacher_salary_advance_id' => $advance->id,
                'teacher_salary_payment_id' => $payment->id,
                'amount' => $applied,
            ]);

            $remaining = round($remaining - $applied, 2);
        }
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
