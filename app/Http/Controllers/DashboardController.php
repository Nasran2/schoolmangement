<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSalaryPayment;
use App\Services\Billing\ClassFeeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $canViewCashFlow = (bool) $user?->can('dashboard.widget.cash_flow.view');
        $canViewRevenueVsExpense = (bool) $user?->can('dashboard.widget.revenue_vs_expense.view');
        $canViewRevenueBreakdown = (bool) $user?->can('dashboard.widget.revenue_category_breakdown.view');
        $canViewExpenseBreakdown = (bool) $user?->can('dashboard.widget.expense_category_breakdown.view');
        $canViewEnrollmentTrend = (bool) $user?->can('dashboard.widget.enrollment_trend.view');
        $canViewChequeAlerts = (bool) (($user?->can('dashboard.widget.notifications.view') ?? false));

        $today = Carbon::today();
        [$rangeKey, $rangeLabel, $rangeStart, $rangeEnd] = $this->resolveRange($request, $today);

        // NOTE: pending/rejected cheques are not counted as revenue until confirmed.
        $revenueQuery = Revenue::query()
            ->whereIn('payment_status', ['confirmed', 'paid'])
            ->whereBetween('paid_at', [$rangeStart, $rangeEnd]);
        $expenseQuery = Expense::query()->whereBetween('expense_date', [$rangeStart, $rangeEnd]);
        $salaryQuery = TeacherSalaryPayment::query()->whereBetween('paid_at', [$rangeStart, $rangeEnd]);

        $totalRevenue = (float) $revenueQuery->clone()->sum('amount');
        $baseExpenses = (float) $expenseQuery->clone()->sum('amount');
        $salaryExpenses = (float) $salaryQuery->clone()->sum('amount');
        $totalExpenses = $baseExpenses + $salaryExpenses;
        $netProfit = $totalRevenue - $totalExpenses;

        $granularity = $this->granularityFor($rangeStart, $rangeEnd);

        [$trendLabels, $trendRevenue, $trendExpense] = $this->buildTrend(
            $rangeStart,
            $rangeEnd,
            $granularity,
            fn ($s, $e) => (float) Revenue::query()
                ->whereIn('payment_status', ['confirmed', 'paid'])
                ->whereBetween('paid_at', [$s, $e])
                ->sum('amount'),
            fn ($s, $e) => (float) Expense::query()->whereBetween('expense_date', [$s, $e])->sum('amount') + (float) TeacherSalaryPayment::query()->whereBetween('paid_at', [$s, $e])->sum('amount'),
        );

        $cashFlowLabels = $trendLabels;
        $cashFlowData = [];
        foreach ($trendLabels as $idx => $_label) {
            $cashFlowData[] = ($trendRevenue[$idx] ?? 0) - ($trendExpense[$idx] ?? 0);
        }

        $revBreakdown = $revenueQuery->clone()
            ->select('revenue_category_id', DB::raw('sum(amount) as total'))
            ->groupBy('revenue_category_id')
            ->pluck('total', 'revenue_category_id');
        $revCats = RevenueCategory::query()->orderBy('name')->get(['id', 'name']);
        $revCatLabels = [];
        $revCatData = [];
        foreach ($revCats as $cat) {
            $total = (float) ($revBreakdown[$cat->id] ?? 0);
            if ($total <= 0) {
                continue;
            }
            $revCatLabels[] = $cat->name;
            $revCatData[] = $total;
        }

        $expBreakdown = $expenseQuery->clone()
            ->select('expense_category_id', DB::raw('sum(amount) as total'))
            ->groupBy('expense_category_id')
            ->pluck('total', 'expense_category_id');
        $expCats = ExpenseCategory::query()->orderBy('name')->get(['id', 'name']);
        $expCatLabels = [];
        $expCatData = [];
        foreach ($expCats as $cat) {
            $total = (float) ($expBreakdown[$cat->id] ?? 0);
            if ($total <= 0) {
                continue;
            }
            $expCatLabels[] = $cat->name;
            $expCatData[] = $total;
        }

        if ($salaryExpenses > 0) {
            $expCatLabels[] = 'Teacher Salaries';
            $expCatData[] = $salaryExpenses;
        }

        [$enrollLabels, $enrollData] = $this->buildSingleTrend(
            $rangeStart,
            $rangeEnd,
            $granularity === 'year' ? 'month' : $granularity,
            fn ($s, $e) => (int) Student::query()->whereBetween('created_at', [$s, $e])->count(),
        );

        $dueStudents = collect();
        $dueStudentCount = 0;
        if ($user->can('dashboard.widget.due_students.view')) {
            // Stored opening dues can be zero even when monthly fees are now overdue.
            // Use the same current balance as the student's statement.
            $balances = Student::query()->with('classRoom')->where('active', true)->where('alumni', false)
                ->get()->map(function (Student $student) {
                    $student->setAttribute('dashboard_due_amount', $student->computed_due_amount);

                    return $student;
                })->filter(fn (Student $student) => $student->dashboard_due_amount > 0)
                ->sortByDesc('dashboard_due_amount')->values();
            $dueStudentCount = $balances->count();
            $dueStudents = $balances->take(5);
        }

        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $paidTeacherIds = TeacherSalaryPayment::query()
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->distinct()
            ->pluck('teacher_id')
            ->filter()
            ->values();
        $dueTeachersQuery = Teacher::query()
            ->where('active', true)->where('salary_amount', '>', 0)
            ->when($paidTeacherIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $paidTeacherIds));
        $dueTeacherCount = (clone $dueTeachersQuery)->count();
        $dueTeachers = (clone $dueTeachersQuery)->orderBy('name')->limit(5)->get();
        $dueTeachersTotal = (float) (clone $dueTeachersQuery)->sum('salary_amount');

        $salaryDeadline = null;
        $salaryDeadlineDay = null;
        try {
            $salaryDeadlineDay = (int) (app('settings')->get('salary.payment_deadline_day', '25') ?: 25);
            $salaryDeadlineDay = max(1, min(28, $salaryDeadlineDay));
            $salaryDeadline = $today->copy()->startOfMonth()->addDays($salaryDeadlineDay - 1);
            if ($salaryDeadline->gt($today->copy()->endOfMonth())) {
                $salaryDeadline = $today->copy()->endOfMonth();
            }
        } catch (\Throwable $e) {
            $salaryDeadline = null;
            $salaryDeadlineDay = null;
        }

        $recentRevenues = Revenue::query()
            ->with(['category', 'student'])
            ->whereIn('payment_status', ['confirmed', 'paid'])
            ->whereBetween('paid_at', [$rangeStart, $rangeEnd])
            ->orderByDesc('paid_at')
            ->limit(6)
            ->get();

        $recentExpenses = Expense::query()
            ->with(['category'])
            ->whereBetween('expense_date', [$rangeStart, $rangeEnd])
            ->orderByDesc('expense_date')
            ->limit(6)
            ->get();

        $recentSalaryPayments = TeacherSalaryPayment::query()
            ->with(['teacher'])
            ->whereBetween('paid_at', [$rangeStart, $rangeEnd])
            ->orderByDesc('paid_at')
            ->limit(6)
            ->get();

        $recentActivity = collect()
            ->merge($recentRevenues->map(fn ($r) => [
                'date' => optional($r->paid_at)->toDateTimeString(),
                'type' => 'Revenue',
                'label' => ($r->category?->name ?? 'Revenue').($r->student ? ' — '.$r->student->name : ''),
                'amount' => (float) $r->amount,
                'direction' => 'in',
            ]))
            ->merge($recentExpenses->map(fn ($e) => [
                'date' => optional($e->expense_date)->toDateTimeString(),
                'type' => 'Expense',
                'label' => $e->category?->name ?? 'Expense',
                'amount' => (float) $e->amount,
                'direction' => 'out',
            ]))
            ->merge($recentSalaryPayments->map(fn ($s) => [
                'date' => optional($s->paid_at)->toDateTimeString(),
                'type' => 'Salary',
                'label' => $s->teacher?->name ? 'Salary — '.$s->teacher->name : 'Salary Payment',
                'amount' => (float) $s->amount,
                'direction' => 'out',
            ]))
            ->sortByDesc('date')
            ->take(10)
            ->values();

        $smsConfigured = (bool) app('settings')->get('sms.gateway.url');
        $printerConfigured = (bool) app('settings')->get('printer.slip.header');
        $alerts = collect([
            'Dashboard range: '.$rangeLabel,
            $smsConfigured ? null : 'SMS gateway settings are not configured.',
            $printerConfigured ? null : 'Printer slip settings are not configured.',
        ])->filter()->values();

        // Salary alert: unpaid teachers past deadline
        try {
            $deadlineDay = (int) (app('settings')->get('salary.payment_deadline_day', '25') ?: 25);
            $deadlineDay = max(1, min(28, $deadlineDay));
            $deadline = $today->copy()->startOfMonth()->addDays($deadlineDay - 1);
            if ($deadline->gt($today->copy()->endOfMonth())) {
                $deadline = $today->copy()->endOfMonth();
            }
            if ($today->greaterThanOrEqualTo($deadline) && $dueTeacherCount > 0) {
                $alerts->push($dueTeacherCount.' teacher'.($dueTeacherCount > 1 ? 's' : '').' have no salary recorded for this month.');
            }
        } catch (\Throwable $e) {
            // ignore alert if something goes wrong
        }

        // Recent Activity (Audit Logs) filters
        $activityRangeKey = (string) $request->query('activity_range', 'last_30_days');
        [$activityStart, $activityEnd] = $this->resolveActivityRange($activityRangeKey, $today);
        $activityAction = (string) $request->query('activity_action', '');
        $activityUserId = $request->query('activity_user');
        $activityQ = (string) $request->query('activity_q', '');

        $recentAuditLogs = AuditLog::query()
            ->with(['user', 'auditable'])
            ->whereBetween('created_at', [$activityStart, $activityEnd])
            ->when($activityAction !== '', fn ($q) => $q->where('action', $activityAction))
            ->when($activityUserId, fn ($q) => $q->where('user_id', $activityUserId))
            ->when($activityQ !== '', function ($q) use ($activityQ) {
                $q->where(function ($qb) use ($activityQ) {
                    $qb->where('description', 'like', '%'.$activityQ.'%')
                        ->orWhere('metadata', 'like', '%'.$activityQ.'%');
                });
            })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $activityActions = AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $activityUsers = User::query()->orderBy('name')->limit(50)->get(['id', 'name']);

        // Cheque confirmation is handled by cheques:auto-pass in the scheduler.
        // Viewing a dashboard must not change financial records.
        $chequeAutoPassDays = max(1, min(90, (int) app('settings')->get('cheques.auto_pass_days', '14')));

        // Dashboard cheque alerts:
        // 1) Pending cheques with 1-7 days left => reminder.
        // 2) Cheques due today/overdue => show status (passed / not passed / returned).
        $chequeReminderDays = 7;
        $chequeReminderStart = $today->copy()->addDay()->startOfDay();
        $chequeReminderEnd = $today->copy()->addDays($chequeReminderDays)->endOfDay();

        $dashboardChequeQuery = Revenue::query()
            ->with(['student'])
            ->where('payment_method', 'cheque')
            ->whereNotNull('cheque_date')
            ->where(function ($q) use ($today, $chequeReminderStart, $chequeReminderEnd, $chequeReminderDays) {
                $q->where(function ($upcoming) use ($chequeReminderStart, $chequeReminderEnd) {
                    $upcoming->whereIn('payment_status', ['hold', 'pending'])
                        ->whereBetween('cheque_date', [$chequeReminderStart, $chequeReminderEnd]);
                })->orWhereBetween('cheque_date', [$today->copy()->subDays($chequeReminderDays)->startOfDay(), $today->copy()->endOfDay()]);
            });

        $pendingChequeCount = (int) $dashboardChequeQuery->clone()
            ->whereIn('payment_status', ['hold', 'pending'])
            ->count();

        $pendingChequeTotal = (float) $dashboardChequeQuery->clone()
            ->whereIn('payment_status', ['hold', 'pending'])
            ->sum('amount');

        $pendingChequeDueCount = (int) $dashboardChequeQuery->clone()
            ->whereIn('payment_status', ['hold', 'pending'])
            ->whereDate('cheque_date', '<=', $today->toDateString())
            ->count();

        $pendingChequeReminderCount = (int) $dashboardChequeQuery->clone()
            ->whereIn('payment_status', ['hold', 'pending'])
            ->whereBetween('cheque_date', [$chequeReminderStart, $chequeReminderEnd])
            ->count();

        $pendingCheques = $dashboardChequeQuery->clone()
            ->orderByRaw(
                'case when payment_status in (?, ?) and cheque_date > ? then 0 when cheque_date <= ? then 1 else 2 end',
                ['hold', 'pending', $today->toDateString(), $today->toDateString()]
            )
            ->orderBy('cheque_date')
            ->orderByDesc('paid_at')
            ->limit(8)
            ->get()
            ->map(function (Revenue $item) use ($today) {
                $chequeDate = $item->cheque_date ? Carbon::parse($item->cheque_date)->startOfDay() : null;
                $daysLeft = $chequeDate ? $today->diffInDays($chequeDate, false) : null;
                $isReminder = $daysLeft !== null && $daysLeft >= 1 && $daysLeft <= 7 && in_array((string) $item->payment_status, ['hold', 'pending'], true);
                $isDueOrOverdue = $daysLeft !== null && $daysLeft <= 0;

                $statusLabel = match ($item->payment_status) {
                    'confirmed' => 'Passed',
                    'rejected' => 'Returned',
                    default => 'On Hold',
                };
                $statusTone = match ($item->payment_status) {
                    'confirmed' => 'emerald',
                    'rejected' => 'rose',
                    default => 'amber',
                };

                $item->setAttribute('dashboard_days_left', $daysLeft);
                $item->setAttribute('dashboard_is_reminder', $isReminder);
                $item->setAttribute('dashboard_is_due_or_overdue', $isDueOrOverdue);
                $item->setAttribute('dashboard_status_label', $statusLabel);
                $item->setAttribute('dashboard_status_tone', $statusTone);

                return $item;
            });

        $classFeeSummaries = [];
        foreach (['exam', 'facility'] as $feeType) {
            if ($user->can('dashboard.widget.'.$feeType.'_fees.view')) {
                $classFeeSummaries[$feeType] = app(ClassFeeService::class)->dashboard($feeType, $rangeStart, $rangeEnd);
            }
        }

        if ($user->can('dashboard.widget.exam_fees.view') && \App\Models\ClassFeeSchedule::where('fee_type', 'computer')->exists()) {
            $classFeeSummaries['computer'] = app(ClassFeeService::class)->dashboard('computer', $rangeStart, $rangeEnd);
        }

        return view('dashboard', [
            'classFeeSummaries' => $classFeeSummaries,
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $netProfit,
            'pendingChequeCount' => $pendingChequeCount,
            'pendingChequeDueCount' => $pendingChequeDueCount,
            'pendingChequeReminderCount' => $pendingChequeReminderCount,
            'pendingChequeTotal' => $pendingChequeTotal,
            'pendingCheques' => $pendingCheques,
            'chequeAutoPassDays' => $chequeAutoPassDays,
            'canViewChequeAlerts' => $canViewChequeAlerts,
            'dueStudents' => $dueStudents,
            'dueStudentCount' => $dueStudentCount,
            'dueTeachers' => $dueTeachers,
            'dueTeacherCount' => $dueTeacherCount,
            'dueTeachersTotal' => $dueTeachersTotal,
            'salaryDeadline' => $salaryDeadline,
            'salaryDeadlineDay' => $salaryDeadlineDay,
            'recentRevenues' => $recentRevenues,
            'recentExpenses' => $recentExpenses,
            'recentSalaryPayments' => $recentSalaryPayments,
            'recentActivity' => $recentActivity,
            'recentAuditLogs' => $recentAuditLogs,
            'activityFilters' => [
                'range' => $activityRangeKey,
                'action' => $activityAction,
                'user' => $activityUserId,
                'q' => $activityQ,
                'actions' => $activityActions,
                'users' => $activityUsers,
            ],
            'alerts' => $alerts,
            'dashboardRange' => [
                'key' => $rangeKey,
                'label' => $rangeLabel,
                'from' => $rangeStart->toDateString(),
                'to' => $rangeEnd->toDateString(),
            ],
            'dashboardData' => [
                'cashFlow' => $canViewCashFlow
                    ? ['labels' => $cashFlowLabels, 'data' => $cashFlowData]
                    : ['labels' => [], 'data' => []],
                'monthly' => $canViewRevenueVsExpense
                    ? ['labels' => $trendLabels, 'revenue' => $trendRevenue, 'expense' => $trendExpense]
                    : ['labels' => [], 'revenue' => [], 'expense' => []],
                'revCats' => $canViewRevenueBreakdown
                    ? ['labels' => $revCatLabels, 'data' => $revCatData]
                    : ['labels' => [], 'data' => []],
                'expCats' => $canViewExpenseBreakdown
                    ? ['labels' => $expCatLabels, 'data' => $expCatData]
                    : ['labels' => [], 'data' => []],
                'enroll' => $canViewEnrollmentTrend
                    ? ['labels' => $enrollLabels, 'data' => $enrollData]
                    : ['labels' => [], 'data' => []],
            ],
        ]);
    }

    private function resolveRange(Request $request, Carbon $today): array
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $key = (string) $request->query('range', 'last_30_days');

        $start = $today->copy()->subDays(29)->startOfDay();
        $end = $today->copy()->endOfDay();
        $label = 'Last 30 Days';

        if ($key === 'today') {
            $start = $today->copy()->startOfDay();
            $end = $today->copy()->endOfDay();
            $label = 'Today';
        } elseif ($key === 'yesterday') {
            $start = $today->copy()->subDay()->startOfDay();
            $end = $today->copy()->subDay()->endOfDay();
            $label = 'Yesterday';
        } elseif ($key === 'last_7_days') {
            $start = $today->copy()->subDays(6)->startOfDay();
            $end = $today->copy()->endOfDay();
            $label = 'Last 7 Days';
        } elseif ($key === 'last_30_days') {
            $start = $today->copy()->subDays(29)->startOfDay();
            $end = $today->copy()->endOfDay();
            $label = 'Last 30 Days';
        } elseif ($key === 'this_month') {
            $start = $today->copy()->startOfMonth()->startOfDay();
            $end = $today->copy()->endOfDay();
            $label = 'This Month';
        } elseif ($key === 'last_month') {
            $start = $today->copy()->subMonthNoOverflow()->startOfMonth()->startOfDay();
            $end = $today->copy()->subMonthNoOverflow()->endOfMonth()->endOfDay();
            $label = 'Last Month';
        } elseif ($key === 'this_year') {
            $start = $today->copy()->startOfYear()->startOfDay();
            $end = $today->copy()->endOfDay();
            $label = 'This Year';
        } elseif ($key === 'last_year') {
            $start = $today->copy()->subYearNoOverflow()->startOfYear()->startOfDay();
            $end = $today->copy()->subYearNoOverflow()->endOfYear()->endOfDay();
            $label = 'Last Year';
        } elseif ($key === 'current_financial_year') {
            $start = $today->copy()->startOfYear()->startOfDay();
            $end = $today->copy()->endOfDay();
            $label = 'Current Financial Year';
        } elseif ($key === 'last_financial_year') {
            $start = $today->copy()->subYearNoOverflow()->startOfYear()->startOfDay();
            $end = $today->copy()->subYearNoOverflow()->endOfYear()->endOfDay();
            $label = 'Last Financial Year';
        } elseif ($key === 'custom') {
            $from = $request->query('from');
            $to = $request->query('to');

            if ($from && $to) {
                $start = Carbon::parse($from)->startOfDay();
                $end = Carbon::parse($to)->endOfDay();
                $label = 'Custom Range';
            }
        }

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$key, $label, $start, $end];
    }

    private function granularityFor(Carbon $start, Carbon $end): string
    {
        $days = max(1, $start->diffInDays($end));
        if ($days <= 45) {
            return 'day';
        }
        if ($days <= 400) {
            return 'month';
        }

        return 'year';
    }

    private function buildTrend(Carbon $start, Carbon $end, string $granularity, callable $revenueForPeriod, callable $expenseForPeriod): array
    {
        $labels = [];
        $revenue = [];
        $expense = [];

        if ($granularity === 'day') {
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $s = $d->copy()->startOfDay();
                $e = $d->copy()->endOfDay();
                $labels[] = $d->format('M d');
                $revenue[] = (float) $revenueForPeriod($s, $e);
                $expense[] = (float) $expenseForPeriod($s, $e);
            }

            return [$labels, $revenue, $expense];
        }

        if ($granularity === 'month') {
            $m = $start->copy()->startOfMonth();
            $endMonth = $end->copy()->startOfMonth();
            while ($m->lte($endMonth)) {
                $s = $m->copy()->startOfMonth()->startOfDay();
                $e = $m->copy()->endOfMonth()->endOfDay();
                $labels[] = $m->format('M Y');
                $revenue[] = (float) $revenueForPeriod($s, $e);
                $expense[] = (float) $expenseForPeriod($s, $e);
                $m->addMonth();
            }

            return [$labels, $revenue, $expense];
        }

        $y = $start->copy()->startOfYear();
        $endYear = $end->copy()->startOfYear();
        while ($y->lte($endYear)) {
            $s = $y->copy()->startOfYear()->startOfDay();
            $e = $y->copy()->endOfYear()->endOfDay();
            $labels[] = $y->format('Y');
            $revenue[] = (float) $revenueForPeriod($s, $e);
            $expense[] = (float) $expenseForPeriod($s, $e);
            $y->addYear();
        }

        return [$labels, $revenue, $expense];
    }

    private function buildSingleTrend(Carbon $start, Carbon $end, string $granularity, callable $valueForPeriod): array
    {
        $labels = [];
        $data = [];

        if ($granularity === 'day') {
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $s = $d->copy()->startOfDay();
                $e = $d->copy()->endOfDay();
                $labels[] = $d->format('M d');
                $data[] = (float) $valueForPeriod($s, $e);
            }

            return [$labels, $data];
        }

        $m = $start->copy()->startOfMonth();
        $endMonth = $end->copy()->startOfMonth();
        while ($m->lte($endMonth)) {
            $s = $m->copy()->startOfMonth()->startOfDay();
            $e = $m->copy()->endOfMonth()->endOfDay();
            $labels[] = $m->format('M Y');
            $data[] = (float) $valueForPeriod($s, $e);
            $m->addMonth();
        }

        return [$labels, $data];
    }

    private function resolveActivityRange(string $key, Carbon $today): array
    {
        $start = $today->copy()->subDays(29)->startOfDay();
        $end = $today->copy()->endOfDay();

        if ($key === 'today') {
            $start = $today->copy()->startOfDay();
            $end = $today->copy()->endOfDay();
        } elseif ($key === 'last_7_days') {
            $start = $today->copy()->subDays(6)->startOfDay();
            $end = $today->copy()->endOfDay();
        } elseif ($key === 'last_30_days') {
            $start = $today->copy()->subDays(29)->startOfDay();
            $end = $today->copy()->endOfDay();
        } elseif ($key === 'this_month') {
            $start = $today->copy()->startOfMonth()->startOfDay();
            $end = $today->copy()->endOfDay();
        } elseif ($key === 'this_year') {
            $start = $today->copy()->startOfYear()->startOfDay();
            $end = $today->copy()->endOfDay();
        }

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }
}
