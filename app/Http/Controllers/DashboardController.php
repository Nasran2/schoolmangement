<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSalaryPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $today = Carbon::today();
        [$rangeKey, $rangeLabel, $rangeStart, $rangeEnd] = $this->resolveRange($request, $today);

        $revenueQuery = Revenue::query()->whereBetween('paid_at', [$rangeStart, $rangeEnd]);
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
            fn ($s, $e) => (float) Revenue::query()->whereBetween('paid_at', [$s, $e])->sum('amount'),
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

        [$enrollLabels, $enrollData] = $this->buildSingleTrend(
            $rangeStart,
            $rangeEnd,
            $granularity === 'year' ? 'month' : $granularity,
            fn ($s, $e) => (int) Student::query()->whereBetween('created_at', [$s, $e])->count(),
        );

        $dueCandidates = Student::query()
            ->where('active', true)
            ->get();
        $dueStudents = $dueCandidates->filter(function ($s) {
                return ($s->computed_due_amount ?? $s->due_amount) > 0;
            })
            ->sortByDesc(function ($s) { return $s->computed_due_amount ?? $s->due_amount; })
            ->take(5)
            ->values();

        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $paidTeacherIds = TeacherSalaryPayment::query()
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->distinct()
            ->pluck('teacher_id')
            ->filter()
            ->values();
        $dueTeachers = Teacher::query()
            ->when($paidTeacherIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $paidTeacherIds))
            ->orderBy('name')
            ->limit(5)
            ->get();

        $recentRevenues = Revenue::query()
            ->with(['category', 'student'])
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

        return view('dashboard', [
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $netProfit,
            'dueStudents' => $dueStudents,
            'dueTeachers' => $dueTeachers,
            'recentRevenues' => $recentRevenues,
            'recentExpenses' => $recentExpenses,
            'recentSalaryPayments' => $recentSalaryPayments,
            'recentActivity' => $recentActivity,
            'alerts' => $alerts,
            'dashboardRange' => [
                'key' => $rangeKey,
                'label' => $rangeLabel,
                'from' => $rangeStart->toDateString(),
                'to' => $rangeEnd->toDateString(),
            ],
            'dashboardData' => [
                'cashFlow' => ['labels' => $cashFlowLabels, 'data' => $cashFlowData],
                'monthly' => ['labels' => $trendLabels, 'revenue' => $trendRevenue, 'expense' => $trendExpense],
                'revCats' => ['labels' => $revCatLabels, 'data' => $revCatData],
                'expCats' => ['labels' => $expCatLabels, 'data' => $expCatData],
                'enroll' => ['labels' => $enrollLabels, 'data' => $enrollData],
            ],
        ]);
    }

    private function resolveRange(Request $request, Carbon $today): array
    {
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
}
