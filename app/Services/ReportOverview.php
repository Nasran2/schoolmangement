<?php

namespace App\Services;

use App\Models\ClassFeeSchedule;
use App\Models\ClassRoom;
use App\Models\Expense;
use App\Models\ExtraClassTeacherPayment;
use App\Models\Revenue;
use App\Models\SeminarTeacherPayment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSalaryPayment;
use App\Models\User;
use App\Services\Billing\ClassFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ReportOverview
{
    public const METRICS = [
        'collection' => ['title' => 'Total collection', 'note' => 'Confirmed student payments, after refunds.', 'tone' => 'teal', 'scope' => 'period', 'permissions' => ['reports.revenue.view', 'reports.fee_collection_summary.view']],
        'expense' => ['title' => 'Total expense', 'note' => 'Expenses, salaries and teacher payouts. Owner transfers excluded.', 'tone' => 'rose', 'scope' => 'period', 'permissions' => ['reports.expense.view', 'reports.outflows.view']],
        'revenue' => ['title' => 'Total revenue', 'note' => 'All confirmed income, including student collections, after refunds.', 'tone' => 'blue', 'scope' => 'period', 'permissions' => ['reports.revenue.view']],
        'teachers' => ['title' => 'Pending teacher payments', 'note' => 'Unsettled salary for the current payroll month.', 'tone' => 'purple', 'scope' => 'pending', 'permissions' => ['teachers.salary.pay', 'teachers.salary.summary.view']],
        'students' => ['title' => 'Pending student receivables', 'note' => 'Current monthly fee balances, including earlier dues.', 'tone' => 'amber', 'scope' => 'pending', 'permissions' => ['reports.student_due.view']],
        'exam' => ['title' => 'Pending exam fees', 'note' => 'Unpaid exam installments due today or earlier.', 'tone' => 'indigo', 'scope' => 'pending', 'permissions' => ['class_fees.view']],
        'facility' => ['title' => 'Pending facility fees', 'note' => 'Unpaid annual facility fees due today or earlier.', 'tone' => 'teal', 'scope' => 'pending', 'permissions' => ['class_fees.view']],
    ];

    private array $incomeByPeriod = [];

    private int $examSessionCount = 3;

    public function allowed(User $user, string $metric): bool
    {
        return isset(self::METRICS[$metric]) && collect(self::METRICS[$metric]['permissions'])->contains(fn ($permission) => $user->can($permission));
    }

    public function filters(Request $request): array
    {
        $data = $request->validate([
            'period' => ['nullable', Rule::in(['today', 'yesterday', 'this_week', 'this_month', 'last_month', 'custom'])],
            'from' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d'],
            'to' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $period = $data['period'] ?? 'today';
        [$from, $to] = match ($period) {
            'yesterday' => [today()->subDay(), today()->subDay()],
            'this_week' => [today()->startOfWeek(), today()],
            'this_month' => [today()->startOfMonth(), today()],
            'last_month' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
            'custom' => [\Carbon\Carbon::parse($data['from']), \Carbon\Carbon::parse($data['to'])],
            default => [today(), today()],
        };

        return ['period' => $period, 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'q' => $data['q'] ?? ''];
    }

    public function cards(User $user, array $filters): Collection
    {
        return collect(self::METRICS)->filter(fn ($settings, $metric) => $this->allowed($user, $metric))
            ->map(function ($settings, $metric) use ($filters) {
                $rows = $this->rows($metric, $filters);

                return array_merge($settings, ['key' => $metric, 'amount' => round($rows->sum('amount'), 2), 'count' => $rows->count(),
                    'url' => route('reports.overview.details', array_merge(['metric' => $metric], $filters))]);
            });
    }

    public function detailFilters(Request $request, string $metric): array
    {
        $rules = ['sort' => ['nullable', Rule::in(['default', 'amount_desc', 'amount_asc', 'name_asc', 'date_asc', 'date_desc'])],
            'min_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999']];
        if (in_array($metric, ['students', 'exam', 'facility'], true)) {
            $rules['class_room_id'] = ['nullable', 'integer', 'exists:class_rooms,id'];
        }
        if (in_array($metric, ['exam', 'facility'], true)) {
            $rules['installment_year'] = ['nullable', 'integer', 'between:2000,2099'];
            $rules['balance_status'] = ['nullable', Rule::in(['unpaid', 'partial', 'pending'])];
        }
        if ($metric === 'exam') {
            $rules['sessions_selected'] = ['nullable', 'boolean'];
            $rules['exam_sessions'] = ['nullable', 'array', 'max:4'];
            $rules['exam_sessions.*'] = ['required', 'integer', 'between:1,4', 'distinct'];
        }
        if ($metric === 'teachers') {
            $rules['payroll_month'] = ['nullable', 'date_format:Y-m', 'before_or_equal:'.today()->format('Y-m')];
            $rules['teacher_id'] = ['nullable', 'integer', 'exists:teachers,id'];
            $rules['balance_status'] = ['nullable', Rule::in(['unpaid', 'partial'])];
        }
        $data = $request->validate($rules);
        if ($metric === 'exam') {
            $data['exam_sessions'] = ($request->boolean('sessions_selected') || $request->has('exam_sessions'))
                ? array_map('intval', $data['exam_sessions'] ?? []) : [1, 2, 3, 4];
        }
        if ($metric === 'teachers') {
            $data['payroll_month'] = $data['payroll_month'] ?? today()->format('Y-m');
        }

        return array_merge($this->filters($request), $data, ['sort' => $data['sort'] ?? 'default', 'min_amount' => $data['min_amount'] ?? null]);
    }

    public function filterRows(Collection $rows, array $filters): Collection
    {
        $rows = $rows->filter(function ($row) use ($filters) {
            foreach (['class_room_id', 'teacher_id', 'installment_year'] as $field) {
                if (filled($filters[$field] ?? null) && (int) ($row[$field] ?? 0) !== (int) $filters[$field]) {
                    return false;
                }
            }
            if (filled($filters['balance_status'] ?? null) && ($row['status_key'] ?? '') !== $filters['balance_status']) {
                return false;
            }
            if (isset($filters['exam_sessions']) && ! in_array($row['exam_session'] ?? null, $filters['exam_sessions'], true)) {
                return false;
            }
            if (filled($filters['min_amount'] ?? null) && $row['amount'] < (float) $filters['min_amount']) {
                return false;
            }

            return $filters['q'] === '' || str_contains(mb_strtolower(implode(' ', $row)), mb_strtolower($filters['q']));
        });
        $rows = match ($filters['sort'] ?? 'default') {
            'amount_desc' => $rows->sortByDesc('amount'), 'amount_asc' => $rows->sortBy('amount'),
            'name_asc' => $rows->sortBy(fn ($row) => mb_strtolower($row['party'])),
            'date_asc' => $rows->sortBy('date'), 'date_desc' => $rows->sortByDesc('date'),
            default => $rows,
        };

        return $rows->values();
    }

    public function detailOptions(string $metric, Collection $rows, array $filters): array
    {
        return [
            'classes' => in_array($metric, ['students', 'exam', 'facility'], true) ? ClassRoom::orderBy('level')->orderBy('name')->get(['id', 'name']) : collect(),
            'teachers' => $metric === 'teachers' ? Teacher::where('active', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'years' => $rows->pluck('installment_year')->push(today()->year)->push($filters['installment_year'] ?? null)->filter()->unique()->sortDesc()->values(),
            'session_count' => max($this->examSessionCount, (int) $rows->max('exam_session')),
        ];
    }

    public function rows(string $metric, array $filters): Collection
    {
        return match ($metric) {
            'collection' => $this->income($filters)->filter(fn ($row) => $row['student_id'] !== null)->values(),
            'revenue' => $this->income($filters),
            'expense' => $this->expenses($filters),
            'teachers' => $this->teachers($filters['payroll_month'] ?? today()->format('Y-m')),
            'students' => $this->students(),
            'exam', 'facility' => $this->classFees($metric),
            default => collect(),
        };
    }

    private function income(array $filters): Collection
    {
        $key = $filters['from'].':'.$filters['to'];

        return $this->incomeByPeriod[$key] ??= Revenue::with(['student', 'category', 'adjustments'])
            ->whereNull('cancelled_at')
            ->where(fn ($q) => $q->whereNull('payment_status')->orWhereIn('payment_status', ['confirmed', 'paid']))
            ->whereDate('paid_at', '>=', $filters['from'])->whereDate('paid_at', '<=', $filters['to'])
            ->orderByDesc('paid_at')->orderByDesc('id')->get()->map(fn ($payment) => [
                'date' => $payment->paid_at?->toDateString(), 'reference' => $payment->bill_no ?: 'Receipt #'.$payment->id,
                'party' => $payment->student?->name ?: 'Other income', 'student_id' => $payment->student_id,
                'description' => ($payment->category?->name ?: 'Income').' · '.ucwords(str_replace('_', ' ', $payment->payment_method ?: 'cash')),
                'amount' => max(0, round((float) $payment->amount - (float) $payment->adjustments->where('type', 'refund')->sum('amount'), 2)),
                'status' => 'Confirmed',
            ]);
    }

    private function expenses(array $filters): Collection
    {
        $rows = Expense::with('category')->whereDate('expense_date', '>=', $filters['from'])->whereDate('expense_date', '<=', $filters['to'])->get()
            ->map(fn ($expense) => ['date' => $expense->expense_date?->toDateString(), 'reference' => 'Expense #'.$expense->id,
                'party' => $expense->category?->name ?: 'Expense', 'description' => $expense->notes ?: 'Operating expense', 'amount' => (float) $expense->amount, 'status' => 'Recorded']);
        $salaries = TeacherSalaryPayment::with('teacher')->whereDate('paid_at', '>=', $filters['from'])->whereDate('paid_at', '<=', $filters['to'])->get()
            ->map(fn ($payment) => ['date' => $payment->paid_at?->toDateString(), 'reference' => $payment->receipt_number,
                'party' => $payment->teacher?->name ?: 'Teacher', 'description' => 'Salary · '.$payment->payment_month, 'amount' => (float) $payment->amount, 'status' => 'Paid']);
        $rows = $rows->concat($salaries);
        // Linked payouts are already included in expenses. Only add payouts without an expense row.
        foreach ([SeminarTeacherPayment::class => 'Seminar payout', ExtraClassTeacherPayment::class => 'Extra class payout'] as $model => $label) {
            $rows = $rows->concat($model::whereNull('expense_id')->whereDate('paid_at', '>=', $filters['from'])->whereDate('paid_at', '<=', $filters['to'])->get()
                ->map(fn ($payment) => ['date' => $payment->paid_at?->toDateString(), 'reference' => $label.' #'.$payment->id,
                    'party' => 'Teacher', 'description' => $payment->notes ?: $label, 'amount' => (float) $payment->amount, 'status' => 'Paid']));
        }

        return $rows->sortByDesc('date')->values();
    }

    private function teachers(string $month): Collection
    {
        $payrollDate = \Carbon\Carbon::createFromFormat('!Y-m', $month);
        $employmentCutoff = $payrollDate->copy()->endOfMonth()->min(today());
        $settled = TeacherSalaryPayment::where('payment_month', $month)->whereDate('paid_at', '<=', today())
            ->selectRaw('teacher_id, SUM(base_salary) as settled')->groupBy('teacher_id')->pluck('settled', 'teacher_id');

        return Teacher::where('active', true)->where('salary_amount', '>', 0)->orderBy('name')->get()
            ->filter(fn ($teacher) => (! $teacher->joining_date || $teacher->joining_date->lte($employmentCutoff)) && (! $teacher->payment_start_date || $teacher->payment_start_date->lte($employmentCutoff)))
            ->map(fn ($teacher) => ['date' => today()->toDateString(), 'reference' => 'Teacher #'.$teacher->id,
                'teacher_id' => $teacher->id,
                'party' => $teacher->name, 'description' => $payrollDate->format('F Y').' salary · Rs '.number_format((float) ($settled[$teacher->id] ?? 0), 2).' settled',
                'amount' => max(0, round((float) $teacher->salary_amount - (float) ($settled[$teacher->id] ?? 0), 2)),
                'status' => (float) ($settled[$teacher->id] ?? 0) > 0 ? 'Partial' : 'Unpaid', 'status_key' => (float) ($settled[$teacher->id] ?? 0) > 0 ? 'partial' : 'unpaid'])
            ->where('amount', '>', 0)->values();
    }

    private function students(): Collection
    {
        return Student::with('classRoom')->where('active', true)->where('alumni', false)->orderBy('name')->get()
            ->map(fn ($student) => ['date' => today()->toDateString(), 'reference' => $student->admission_number,
                'class_room_id' => $student->class_room_id,
                'party' => $student->name, 'description' => ($student->classRoom?->name ?: 'Unassigned class').' · Monthly fee balance',
                'amount' => round($student->computed_due_amount, 2), 'status' => 'Pending'])
            ->where('amount', '>', 0)->values();
    }

    private function classFees(string $type): Collection
    {
        $schedules = ClassFeeSchedule::with('classRoom')->where('fee_type', $type)->get();
        if ($schedules->isEmpty()) {
            return collect();
        }
        $students = Student::with('promotionHistories')->where('active', true)->where('alumni', false)->get();
        $fees = app(ClassFeeService::class);
        $tracker = $fees->tracker($schedules, $students, $schedules->min('first_due_date')->copy(), today());
        // Number sessions from every scheduled date, including settled and future cycles,
        // so paying an earlier installment or changing a fee cannot renumber a later session.
        $sessionMap = [];
        if ($type === 'exam') {
            $annualCycles = $fees->cycles($schedules, $schedules->min('first_due_date')->copy()->startOfYear(), today()->endOfYear());
            foreach ($annualCycles->groupBy(fn ($cycle) => $cycle['schedule']->class_room_id.':'.$cycle['due']->year) as $group) {
                $dates = $group->pluck('due')->map(fn ($due) => $due->toDateString())->unique()->sort()->values();
                $this->examSessionCount = max($this->examSessionCount, $dates->count());
                foreach ($group as $cycle) {
                    $sessionMap[$cycle['key']] = $dates->search($cycle['due']->toDateString()) + 1;
                }
            }
        }
        $rows = collect();
        foreach ($tracker['rows'] as $row) {
            foreach ($row['installments'] as $key => $status) {
                if ($status['due'] <= 0) {
                    continue;
                }
                $cycle = $tracker['cycles'][$key];
                $rows->push(['date' => $cycle['due']->toDateString(), 'reference' => $row['student']->admission_number,
                    'class_room_id' => $cycle['schedule']->class_room_id, 'installment_year' => $cycle['due']->year,
                    'exam_session' => $type === 'exam' ? $sessionMap[$key] : null,
                    'party' => $row['student']->name, 'description' => ($cycle['schedule']->classRoom?->name ?: 'Class').' · '.ClassFeeSchedule::TYPES[$type],
                    'amount' => $status['due'], 'status' => $status['status'],
                    'status_key' => match ($status['status']) {
                        'Partial' => 'partial', 'Pending cheque' => 'pending', default => 'unpaid'
                    }]);
            }
        }

        return $rows->sortBy('date')->values();
    }
}
