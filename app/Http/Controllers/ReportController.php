<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\RevenueAdjustment;
use App\Models\Student;
use App\Models\StudentMonthFeeAllocation;
use App\Models\Teacher;
use App\Models\TeacherSalaryPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    private function authorizeDownload(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->can('reports.download')) {
            abort(403);
        }
    }

    public function index(): View
    {
        return view('reports.index');
    }

    public function revenue(Request $request)
    {
        $query = Revenue::query()->with(['category', 'student']);

        if ($request->filled('category_id')) {
            $query->where('revenue_category_id', $request->string('category_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('paid_at', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('paid_at', '<=', $request->string('to'));
        }

        $query->orderByDesc('paid_at');

        // Handle PDF Download
        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $rows = $query->get();
            $totalAmount = $rows->sum('amount');
            
            $html = view('reports.revenue-pdf', [
                'items' => $rows,
                'totalAmount' => $totalAmount,
                'filters' => $request->only(['category_id', 'from', 'to']),
                'categories' => RevenueCategory::query()->orderBy('name')->get(),
            ])->render();

            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            return $pdf->download('revenue-report-' . now()->format('Y-m-d') . '.pdf');
        }

        // Handle CSV Download
        if ($request->boolean('download')) {
            $this->authorizeDownload($request);
            $rows = $query->get();

            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Bill No', 'Date', 'Category', 'Student', 'Amount', 'Notes']);
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->bill_no,
                        optional($row->paid_at)->format('Y-m-d'),
                        $row->category?->name,
                        $row->student?->name,
                        $row->amount,
                        $row->notes,
                    ]);
                }
                fclose($out);
            }, 'revenue-report.csv', ['Content-Type' => 'text/csv']);
        }

        return view('reports.revenue', [
            'items' => $query->paginate(20)->withQueryString(),
            'categories' => RevenueCategory::query()->orderBy('name')->get(),
            'filters' => $request->only(['category_id', 'from', 'to']),
        ]);
    }

    public function expense(Request $request)
    {
        $query = Expense::query()->with(['category']);

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->string('category_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('expense_date', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('expense_date', '<=', $request->string('to'));
        }

        $query->orderByDesc('expense_date');

        // Handle PDF Download
        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $rows = $query->get();
            $totalAmount = $rows->sum('amount');
            
            $html = view('reports.expense-pdf', [
                'items' => $rows,
                'totalAmount' => $totalAmount,
                'filters' => $request->only(['category_id', 'from', 'to']),
                'categories' => ExpenseCategory::query()->orderBy('name')->get(),
            ])->render();

            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            return $pdf->download('expense-report-' . now()->format('Y-m-d') . '.pdf');
        }

        // Handle CSV Download
        if ($request->boolean('download')) {
            $this->authorizeDownload($request);
            $rows = $query->get();

            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Date', 'Category', 'Amount', 'Notes']);
                foreach ($rows as $row) {
                    fputcsv($out, [
                        optional($row->expense_date)->format('Y-m-d'),
                        $row->category?->name,
                        $row->amount,
                        $row->notes,
                    ]);
                }
                fclose($out);
            }, 'expense-report.csv', ['Content-Type' => 'text/csv']);
        }

        return view('reports.expense', [
            'items' => $query->paginate(20)->withQueryString(),
            'categories' => ExpenseCategory::query()->orderBy('name')->get(),
            'filters' => $request->only(['category_id', 'from', 'to']),
        ]);
    }

    public function financial(Request $request)
    {
        $revenueQuery = Revenue::query();
        $expenseQuery = Expense::query();
        $refundQuery = DB::table('revenue_adjustments')
            ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
            ->where('revenue_adjustments.type', 'refund');

        if ($request->filled('from')) {
            $revenueQuery->whereDate('paid_at', '>=', $request->string('from'));
            $expenseQuery->whereDate('expense_date', '>=', $request->string('from'));
            $refundQuery->whereDate('revenues.paid_at', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $revenueQuery->whereDate('paid_at', '<=', $request->string('to'));
            $expenseQuery->whereDate('expense_date', '<=', $request->string('to'));
            $refundQuery->whereDate('revenues.paid_at', '<=', $request->string('to'));
        }

        $grossRevenue = (float) $revenueQuery->sum('amount');
        $refunds = (float) $refundQuery->sum('revenue_adjustments.amount');
        $totalRevenue = max(0.0, $grossRevenue - $refunds);
        $totalExpense = (float) $expenseQuery->sum('amount');
        $netProfit = $totalRevenue - $totalExpense;

        // Handle PDF Download
        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $html = view('reports.financial-pdf', [
                'totalRevenue' => $totalRevenue,
                'totalExpense' => $totalExpense,
                'netProfit' => $netProfit,
                'filters' => $request->only(['from', 'to']),
            ])->render();

            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            return $pdf->download('financial-report-' . now()->format('Y-m-d') . '.pdf');
        }

        return view('reports.financial', [
            'filters' => $request->only(['from', 'to']),
            'totalRevenue' => $totalRevenue,
            'totalExpense' => $totalExpense,
            'netProfit' => $netProfit,
        ]);
    }

    public function teacherEpf(Request $request)
    {
        return $this->teacherDeductionReport($request, 'EPF');
    }

    public function teacherEtf(Request $request)
    {
        return $this->teacherDeductionReport($request, 'ETF');
    }

    public function studentDue(Request $request)
    {
        $query = Student::query()->with('classRoom');

        if ($request->filled('class_room_id')) {
            $query->where('class_room_id', $request->string('class_room_id'));
        }

        if ($request->filled('q')) {
            $q = '%' . str_replace('%', '\\%', (string) $request->string('q')) . '%';
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', $q)
                    ->orWhere('admission_number', 'like', $q)
                    ->orWhere('phone', 'like', $q)
                    ->orWhere('whatsapp_number', 'like', $q);
            });
        }

        // Default to only active students
        $onlyActive = $request->input('only_active', '1');
        if ($onlyActive === '1') {
            $query->where('active', true);
        }

        $students = $query->orderBy('name')->get();

        $studentIds = $students->pluck('id')->all();
        $categoryIds = $students
            ->map(fn (Student $s) => $s->classRoom?->monthly_fee_revenue_category_id)
            ->filter(fn ($id) => $id !== null)
            ->unique()
            ->values()
            ->all();

        $paidMap = [];
        if (count($studentIds) > 0 && count($categoryIds) > 0) {
            $rows = Revenue::query()
                ->selectRaw('student_id, revenue_category_id, SUM(amount) as total')
                ->whereIn('student_id', $studentIds)
                ->whereIn('revenue_category_id', $categoryIds)
                ->groupBy('student_id', 'revenue_category_id')
                ->get();

            foreach ($rows as $row) {
                $sid = (int) $row->student_id;
                $cid = (int) $row->revenue_category_id;
                $paidMap[$sid][$cid] = (float) $row->total;
            }
        }

        $refundMap = [];
        $waiverMap = [];
        if (count($studentIds) > 0 && count($categoryIds) > 0) {
            $refundRows = DB::table('revenue_adjustments')
                ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
                ->selectRaw('revenues.student_id as student_id, revenues.revenue_category_id as revenue_category_id, SUM(revenue_adjustments.amount) as total')
                ->whereIn('revenues.student_id', $studentIds)
                ->whereIn('revenues.revenue_category_id', $categoryIds)
                ->where('revenue_adjustments.type', 'refund')
                ->groupBy('revenues.student_id', 'revenues.revenue_category_id')
                ->get();

            foreach ($refundRows as $row) {
                $sid = (int) $row->student_id;
                $cid = (int) $row->revenue_category_id;
                $refundMap[$sid][$cid] = (float) $row->total;
            }

            $waiverRows = DB::table('revenue_adjustments')
                ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
                ->selectRaw('revenues.student_id as student_id, revenues.revenue_category_id as revenue_category_id, SUM(revenue_adjustments.amount) as total')
                ->whereIn('revenues.student_id', $studentIds)
                ->whereIn('revenues.revenue_category_id', $categoryIds)
                ->where('revenue_adjustments.type', 'waiver')
                ->groupBy('revenues.student_id', 'revenues.revenue_category_id')
                ->get();

            foreach ($waiverRows as $row) {
                $sid = (int) $row->student_id;
                $cid = (int) $row->revenue_category_id;
                $waiverMap[$sid][$cid] = (float) $row->total;
            }
        }

        $computed = [];
        foreach ($students as $student) {
            $monthlyFee = (float) ($student->monthly_fee ?? 0);
            $monthsDue = 0;
            if ($monthlyFee > 0 && $student->fee_start_date) {
                $start = Carbon::parse($student->fee_start_date)->startOfDay();
                $now = now();
                $monthsDue = $now->lt($start) ? 0 : (int) ($start->diffInMonths($now) + 1);
            } elseif ($monthlyFee > 0) {
                $monthsDue = 1;
            }

            $expectedBase = $monthlyFee * max(0, $monthsDue);
            $catId = $student->classRoom?->monthly_fee_revenue_category_id;
            $paid = 0.0;
            $refunds = 0.0;
            $waivers = 0.0;
            if ($catId) {
                $paid = (float) ($paidMap[(int) $student->id][(int) $catId] ?? 0.0);
                $refunds = (float) ($refundMap[(int) $student->id][(int) $catId] ?? 0.0);
                $waivers = (float) ($waiverMap[(int) $student->id][(int) $catId] ?? 0.0);
            }
            $paidNet = max(0.0, $paid - $refunds);
            $expected = max(0.0, $expectedBase - $waivers);
            $due = max(0.0, $expected - $paidNet);

            $computed[] = [
                'student' => $student,
                'class_room' => $student->classRoom,
                'monthly_fee' => $monthlyFee,
                'months_due' => $monthsDue,
                'expected' => $expected,
                'paid' => $paidNet,
                'due' => $due,
            ];
        }

        // Post-compute filters
        $onlyWithDue = $request->input('only_with_due', '1');
        if ($onlyWithDue === '1') {
            $computed = array_values(array_filter($computed, fn ($r) => (float) $r['due'] > 0));
        }

        $minDue = $request->input('min_due', '');
        if ($minDue !== '' && is_numeric($minDue)) {
            $min = (float) $minDue;
            $computed = array_values(array_filter($computed, fn ($r) => (float) $r['due'] >= $min));
        }

        usort($computed, function ($a, $b) {
            return ($b['due'] <=> $a['due']) ?: strcmp((string) ($a['student']->name ?? ''), (string) ($b['student']->name ?? ''));
        });

        $filters = $request->only(['class_room_id', 'only_active', 'only_with_due', 'min_due', 'q']);

        // PDF Download
        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $totalDue = array_sum(array_map(fn ($r) => (float) $r['due'], $computed));
            $html = view('reports.student-due-pdf', [
                'rows' => $computed,
                'totalDue' => $totalDue,
                'filters' => $filters,
                'classRooms' => ClassRoom::query()->orderBy('name')->get(),
            ])->render();

            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            return $pdf->download('student-due-report-' . now()->format('Y-m-d') . '.pdf');
        }

        // CSV Download
        if ($request->boolean('download')) {
            $this->authorizeDownload($request);
            return response()->streamDownload(function () use ($computed) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Admission No', 'Student', 'Class', 'Monthly Fee', 'Months Due', 'Expected', 'Paid', 'Due']);
                foreach ($computed as $row) {
                    /** @var \App\Models\Student $s */
                    $s = $row['student'];
                    fputcsv($out, [
                        $s->admission_number,
                        $s->name,
                        $row['class_room']?->name,
                        number_format((float) $row['monthly_fee'], 2, '.', ''),
                        (int) $row['months_due'],
                        number_format((float) $row['expected'], 2, '.', ''),
                        number_format((float) $row['paid'], 2, '.', ''),
                        number_format((float) $row['due'], 2, '.', ''),
                    ]);
                }
                fclose($out);
            }, 'student-due-report.csv', ['Content-Type' => 'text/csv']);
        }

        // Paginate computed rows
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $total = count($computed);
        $slice = array_slice($computed, ($page - 1) * $perPage, $perPage);
        $paginator = new LengthAwarePaginator($slice, $total, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        $totalDue = array_sum(array_map(fn ($r) => (float) $r['due'], $computed));
        $totalStudentsWithDue = count(array_filter($computed, fn ($r) => (float) $r['due'] > 0));

        return view('reports.student-due', [
            'items' => $paginator,
            'filters' => $filters,
            'classRooms' => ClassRoom::query()->orderBy('name')->get(),
            'totalDue' => $totalDue,
            'totalStudentsWithDue' => $totalStudentsWithDue,
        ]);
    }

    public function feeCollectionSummary(Request $request)
    {
        $query = Revenue::query();

        if ($request->filled('category_id')) {
            $query->where('revenue_category_id', $request->string('category_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('paid_at', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('paid_at', '<=', $request->string('to'));
        }

        $group = $request->input('group', 'day');
        $filters = $request->only(['category_id', 'from', 'to', 'group']);

        $groupExpr = $group === 'month'
            ? DB::raw("DATE_FORMAT(paid_at, '%Y-%m')")
            : DB::raw("DATE_FORMAT(paid_at, '%Y-%m-%d')");

        $refundsSub = DB::table('revenue_adjustments')
            ->selectRaw('revenue_id, SUM(amount) as refund_amount')
            ->where('type', 'refund')
            ->groupBy('revenue_id');

        $rows = (clone $query)
            ->leftJoinSub($refundsSub, 'refunds', 'refunds.revenue_id', '=', 'revenues.id')
            ->select([
                $groupExpr . ' as grp',
                DB::raw('COUNT(DISTINCT revenues.id) as payments'),
                DB::raw('SUM(revenues.amount) - COALESCE(SUM(refunds.refund_amount), 0) as total_amount'),
            ])
            ->groupBy('grp')
            ->orderBy('grp')
            ->get();

        $totalAmount = (float) $rows->sum('total_amount');
        $totalPayments = (int) $rows->sum('payments');

        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $html = view('reports.fee-collection-summary-pdf', [
                'rows' => $rows,
                'filters' => $filters,
                'totalAmount' => $totalAmount,
                'totalPayments' => $totalPayments,
            ])->render();

            $pdf = Pdf::loadHTML($html)->setPaper('a4');
            return $pdf->download('fee-collection-summary-' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->boolean('download')) {
            $this->authorizeDownload($request);
            return response()->streamDownload(function () use ($rows, $group) {
                $out = fopen('php://output', 'w');
                fputcsv($out, [ucfirst($group), 'Payments', 'Total']);
                foreach ($rows as $r) {
                    fputcsv($out, [$r->grp, (int) $r->payments, number_format((float) $r->total_amount, 2, '.', '')]);
                }
                fclose($out);
            }, 'fee-collection-summary.csv', ['Content-Type' => 'text/csv']);
        }

        return view('reports.fee-collection-summary', [
            'rows' => $rows,
            'filters' => $filters,
            'categories' => RevenueCategory::query()->orderBy('name')->get(),
            'totalAmount' => $totalAmount,
            'totalPayments' => $totalPayments,
        ]);
    }

    public function feeCollectionByClass(Request $request)
    {
        $query = Revenue::query()
            ->leftJoin('students', 'students.id', '=', 'revenues.student_id')
            ->leftJoin('class_rooms', 'class_rooms.id', '=', 'students.class_room_id')
            ->leftJoinSub(
                DB::table('revenue_adjustments')
                    ->selectRaw('revenue_id, SUM(amount) as refund_amount')
                    ->where('type', 'refund')
                    ->groupBy('revenue_id'),
                'refunds',
                'refunds.revenue_id',
                '=',
                'revenues.id'
            )
            ->select([
                DB::raw('COALESCE(class_rooms.name, "No Class") as class_name'),
                DB::raw('COUNT(DISTINCT revenues.id) as payments'),
                DB::raw('SUM(revenues.amount) - COALESCE(SUM(refunds.refund_amount), 0) as total_amount'),
            ]);

        if ($request->filled('category_id')) {
            $query->where('revenues.revenue_category_id', $request->string('category_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('revenues.paid_at', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('revenues.paid_at', '<=', $request->string('to'));
        }
        if ($request->filled('class_room_id')) {
            $query->where('students.class_room_id', $request->string('class_room_id'));
        }

        $filters = $request->only(['category_id', 'from', 'to', 'class_room_id']);
        $rows = $query->groupBy('class_name')->orderBy('class_name')->get();
        $totalAmount = (float) $rows->sum('total_amount');

        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $html = view('reports.fee-collection-by-class-pdf', [
                'rows' => $rows,
                'filters' => $filters,
                'totalAmount' => $totalAmount,
            ])->render();
            $pdf = Pdf::loadHTML($html)->setPaper('a4');
            return $pdf->download('fee-collection-by-class-' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->boolean('download')) {
            $this->authorizeDownload($request);
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Class', 'Payments', 'Total']);
                foreach ($rows as $r) {
                    fputcsv($out, [$r->class_name, (int) $r->payments, number_format((float) $r->total_amount, 2, '.', '')]);
                }
                fclose($out);
            }, 'fee-collection-by-class.csv', ['Content-Type' => 'text/csv']);
        }

        return view('reports.fee-collection-by-class', [
            'rows' => $rows,
            'filters' => $filters,
            'categories' => RevenueCategory::query()->orderBy('name')->get(),
            'classRooms' => ClassRoom::query()->orderBy('name')->get(),
            'totalAmount' => $totalAmount,
        ]);
    }

    public function feeCollectionByCategory(Request $request)
    {
        $query = Revenue::query()
            ->join('revenue_categories', 'revenue_categories.id', '=', 'revenues.revenue_category_id')
            ->leftJoin('students', 'students.id', '=', 'revenues.student_id')
            ->leftJoinSub(
                DB::table('revenue_adjustments')
                    ->selectRaw('revenue_id, SUM(amount) as refund_amount')
                    ->where('type', 'refund')
                    ->groupBy('revenue_id'),
                'refunds',
                'refunds.revenue_id',
                '=',
                'revenues.id'
            )
            ->select([
                'revenue_categories.name as category_name',
                DB::raw('COUNT(DISTINCT revenues.id) as payments'),
                DB::raw('SUM(revenues.amount) - COALESCE(SUM(refunds.refund_amount), 0) as total_amount'),
            ]);

        if ($request->filled('from')) {
            $query->whereDate('revenues.paid_at', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('revenues.paid_at', '<=', $request->string('to'));
        }
        if ($request->filled('class_room_id')) {
            $query->where('students.class_room_id', $request->string('class_room_id'));
        }

        $filters = $request->only(['from', 'to', 'class_room_id']);
        $rows = $query->groupBy('category_name')->orderBy('category_name')->get();
        $totalAmount = (float) $rows->sum('total_amount');

        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $html = view('reports.fee-collection-by-category-pdf', [
                'rows' => $rows,
                'filters' => $filters,
                'totalAmount' => $totalAmount,
            ])->render();
            $pdf = Pdf::loadHTML($html)->setPaper('a4');
            return $pdf->download('fee-collection-by-category-' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->boolean('download')) {
            $this->authorizeDownload($request);
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Category', 'Payments', 'Total']);
                foreach ($rows as $r) {
                    fputcsv($out, [$r->category_name, (int) $r->payments, number_format((float) $r->total_amount, 2, '.', '')]);
                }
                fclose($out);
            }, 'fee-collection-by-category.csv', ['Content-Type' => 'text/csv']);
        }

        return view('reports.fee-collection-by-category', [
            'rows' => $rows,
            'filters' => $filters,
            'classRooms' => ClassRoom::query()->orderBy('name')->get(),
            'totalAmount' => $totalAmount,
        ]);
    }

    public function feeCollectionVsExpected(Request $request)
    {
        $from = $request->input('from_month', now()->format('Y-m'));
        $to = $request->input('to_month', now()->format('Y-m'));
        $onlyActive = $request->input('only_active', '1');
        $filters = $request->only(['from_month', 'to_month', 'only_active']);

        $fromDate = Carbon::parse($from . '-01')->startOfMonth();
        $toDate = Carbon::parse($to . '-01')->startOfMonth();
        if ($toDate->lt($fromDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $studentsQuery = Student::query()->with('classRoom');
        if ($onlyActive === '1') {
            $studentsQuery->where('active', true);
        }
        $students = $studentsQuery->get();
        $studentIds = $students->pluck('id')->all();

        $waiverByMonth = [];
        if (count($studentIds) > 0) {
            $waiverRows = DB::table('revenue_adjustments')
                ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
                ->join('revenue_categories', 'revenue_categories.id', '=', 'revenues.revenue_category_id')
                ->selectRaw('revenue_adjustments.effective_year as y, revenue_adjustments.effective_month as m, SUM(revenue_adjustments.amount) as total')
                ->whereIn('revenues.student_id', $studentIds)
                ->where('revenue_adjustments.type', 'waiver')
                ->where('revenue_categories.payment_type', 'monthly')
                ->whereNotNull('revenue_adjustments.effective_year')
                ->whereNotNull('revenue_adjustments.effective_month')
                ->groupBy('y', 'm')
                ->get();

            foreach ($waiverRows as $row) {
                $key = sprintf('%04d-%02d', (int) $row->y, (int) $row->m);
                $waiverByMonth[$key] = (float) $row->total;
            }
        }

        $allocRows = [];
        if (count($studentIds) > 0) {
            $allocRows = StudentMonthFeeAllocation::query()
                ->select(['year', 'month', DB::raw('SUM(applied_amount) as total_applied')])
                ->whereIn('student_id', $studentIds)
                ->where(function ($q) use ($fromDate, $toDate) {
                    $q->whereRaw('(year > ? OR (year = ? AND month >= ?))', [$fromDate->year, $fromDate->year, $fromDate->month])
                      ->whereRaw('(year < ? OR (year = ? AND month <= ?))', [$toDate->year, $toDate->year, $toDate->month]);
                })
                ->groupBy('year', 'month')
                ->get();
        }

        $collectedMap = [];
        foreach ($allocRows as $r) {
            $key = sprintf('%04d-%02d', (int) $r->year, (int) $r->month);
            $collectedMap[$key] = (float) $r->total_applied;
        }

        $months = [];
        $cursor = $fromDate->copy();
        while ($cursor->lte($toDate)) {
            $key = $cursor->format('Y-m');
            $label = $cursor->format('M Y');
            $endOfMonth = $cursor->copy()->endOfMonth();

            $expected = 0.0;
            foreach ($students as $s) {
                $fee = (float) ($s->monthly_fee ?? 0);
                if ($fee <= 0) {
                    continue;
                }
                if (! $s->fee_start_date) {
                    // If no fee start date, assume expected for all months in range
                    $expected += $fee;
                    continue;
                }
                $start = Carbon::parse($s->fee_start_date)->startOfMonth();
                if ($start->lte($endOfMonth)) {
                    $expected += $fee;
                }
            }

            $collected = (float) ($collectedMap[$key] ?? 0.0);
            $waived = (float) ($waiverByMonth[$key] ?? 0.0);
            $expected = max(0.0, $expected - $waived);
            $due = max(0.0, $expected - $collected);

            $months[] = [
                'month' => $key,
                'label' => $label,
                'expected' => $expected,
                'collected' => $collected,
                'due' => $due,
            ];

            $cursor->addMonthNoOverflow();
        }

        $totals = [
            'expected' => array_sum(array_map(fn ($r) => (float) $r['expected'], $months)),
            'collected' => array_sum(array_map(fn ($r) => (float) $r['collected'], $months)),
            'due' => array_sum(array_map(fn ($r) => (float) $r['due'], $months)),
        ];

        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $html = view('reports.fee-collection-vs-expected-pdf', [
                'rows' => $months,
                'filters' => $filters,
                'totals' => $totals,
            ])->render();
            $pdf = Pdf::loadHTML($html)->setPaper('a4');
            return $pdf->download('collected-vs-expected-' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->boolean('download')) {
            $this->authorizeDownload($request);
            return response()->streamDownload(function () use ($months) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Month', 'Expected', 'Collected', 'Due']);
                foreach ($months as $r) {
                    fputcsv($out, [
                        $r['month'],
                        number_format((float) $r['expected'], 2, '.', ''),
                        number_format((float) $r['collected'], 2, '.', ''),
                        number_format((float) $r['due'], 2, '.', ''),
                    ]);
                }
                fclose($out);
            }, 'collected-vs-expected.csv', ['Content-Type' => 'text/csv']);
        }

        return view('reports.fee-collection-vs-expected', [
            'rows' => $months,
            'filters' => $filters,
            'totals' => $totals,
        ]);
    }

    public function studentDueAging(Request $request)
    {
        $onlyActive = $request->input('only_active', '1');
        $filters = $request->only(['only_active', 'class_room_id']);

        $query = Student::query()->with('classRoom');
        if ($request->filled('class_room_id')) {
            $query->where('class_room_id', $request->string('class_room_id'));
        }
        if ($onlyActive === '1') {
            $query->where('active', true);
        }

        $students = $query->orderBy('name')->get();
        $buckets = [
            '0-30' => ['label' => '0-30 days', 'students' => 0, 'due' => 0.0],
            '31-60' => ['label' => '31-60 days', 'students' => 0, 'due' => 0.0],
            '61-90' => ['label' => '61-90 days', 'students' => 0, 'due' => 0.0],
            '90+' => ['label' => '90+ days', 'students' => 0, 'due' => 0.0],
        ];

        $rows = [];
        foreach ($students as $s) {
            $due = (float) $s->computeMonthlyDue();
            if ($due <= 0) {
                continue;
            }

            $monthsDue = $s->monthlyCyclesCountToNow();
            $paidMonths = $s->monthlyFeePaidCyclesCount();
            $unpaidMonths = max(0, (int) $monthsDue - (int) $paidMonths);

            if ($unpaidMonths <= 1) {
                $bucket = '0-30';
            } elseif ($unpaidMonths === 2) {
                $bucket = '31-60';
            } elseif ($unpaidMonths === 3) {
                $bucket = '61-90';
            } else {
                $bucket = '90+';
            }

            $buckets[$bucket]['students']++;
            $buckets[$bucket]['due'] += $due;

            $rows[] = [
                'student' => $s,
                'class_room' => $s->classRoom,
                'due' => $due,
                'unpaid_months' => $unpaidMonths,
                'bucket' => $buckets[$bucket]['label'],
            ];
        }

        usort($rows, fn ($a, $b) => ($b['due'] <=> $a['due']) ?: strcmp((string) ($a['student']->name ?? ''), (string) ($b['student']->name ?? '')));

        if ($request->boolean('download')) {
            $this->authorizeDownload($request);
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Admission No', 'Student', 'Class', 'Bucket', 'Unpaid Months', 'Due']);
                foreach ($rows as $r) {
                    /** @var \App\Models\Student $s */
                    $s = $r['student'];
                    fputcsv($out, [
                        $s->admission_number,
                        $s->name,
                        $r['class_room']?->name,
                        $r['bucket'],
                        (int) $r['unpaid_months'],
                        number_format((float) $r['due'], 2, '.', ''),
                    ]);
                }
                fclose($out);
            }, 'student-due-aging.csv', ['Content-Type' => 'text/csv']);
        }

        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $html = view('reports.student-due-aging-pdf', [
                'rows' => $rows,
                'buckets' => $buckets,
                'filters' => $filters,
            ])->render();
            $pdf = Pdf::loadHTML($html)->setPaper('a4');
            return $pdf->download('student-due-aging-' . now()->format('Y-m-d') . '.pdf');
        }

        return view('reports.student-due-aging', [
            'rows' => $rows,
            'buckets' => $buckets,
            'filters' => $filters,
            'classRooms' => ClassRoom::query()->orderBy('name')->get(),
        ]);
    }

    public function studentTopDue(Request $request)
    {
        $limit = (int) $request->input('limit', 20);
        $limit = max(1, min(200, $limit));

        $filters = $request->only(['class_room_id', 'only_active', 'limit', 'q']);

        $studentRequest = new Request($request->all());
        $studentRequest->merge([
            'only_with_due' => '1',
        ]);

        // Reuse existing due computation logic by calling studentDue() behavior inline (simplified)
        $query = Student::query()->with('classRoom');

        if ($request->filled('class_room_id')) {
            $query->where('class_room_id', $request->string('class_room_id'));
        }

        if ($request->filled('q')) {
            $q = '%' . str_replace('%', '\\%', (string) $request->string('q')) . '%';
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', $q)
                    ->orWhere('admission_number', 'like', $q)
                    ->orWhere('phone', 'like', $q)
                    ->orWhere('whatsapp_number', 'like', $q);
            });
        }

        $onlyActive = $request->input('only_active', '1');
        if ($onlyActive === '1') {
            $query->where('active', true);
        }

        $students = $query->orderBy('name')->get();
        $computed = [];
        foreach ($students as $student) {
            $due = (float) $student->computeMonthlyDue();
            if ($due <= 0) {
                continue;
            }
            $computed[] = [
                'student' => $student,
                'class_room' => $student->classRoom,
                'due' => $due,
            ];
        }

        usort($computed, fn ($a, $b) => ($b['due'] <=> $a['due']) ?: strcmp((string) ($a['student']->name ?? ''), (string) ($b['student']->name ?? '')));
        $rows = array_slice($computed, 0, $limit);
        $totalDue = array_sum(array_map(fn ($r) => (float) $r['due'], $rows));

        if ($request->boolean('download')) {
            $this->authorizeDownload($request);
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Admission No', 'Student', 'Class', 'Phone', 'WhatsApp', 'Due']);
                foreach ($rows as $r) {
                    /** @var \App\Models\Student $s */
                    $s = $r['student'];
                    fputcsv($out, [
                        $s->admission_number,
                        $s->name,
                        $r['class_room']?->name,
                        $s->phone,
                        $s->whatsapp_number,
                        number_format((float) $r['due'], 2, '.', ''),
                    ]);
                }
                fclose($out);
            }, 'student-top-due.csv', ['Content-Type' => 'text/csv']);
        }

        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $html = view('reports.student-top-due-pdf', [
                'rows' => $rows,
                'filters' => $filters,
                'totalDue' => $totalDue,
            ])->render();
            $pdf = Pdf::loadHTML($html)->setPaper('a4');
            return $pdf->download('student-top-due-' . now()->format('Y-m-d') . '.pdf');
        }

        return view('reports.student-top-due', [
            'rows' => $rows,
            'filters' => $filters,
            'classRooms' => ClassRoom::query()->orderBy('name')->get(),
            'totalDue' => $totalDue,
        ]);
    }

    public function feeDiscounts(Request $request)
    {
        return $this->adjustmentsReport($request, 'waiver');
    }

    public function feeRefunds(Request $request)
    {
        return $this->adjustmentsReport($request, 'refund');
    }

    /**
     * Generic report for revenue adjustments (refunds/waivers)
     */
    private function adjustmentsReport(Request $request, string $type)
    {
        $type = $type === 'waiver' ? 'waiver' : 'refund';

        $query = RevenueAdjustment::query()
            ->with(['revenue.category', 'student.classRoom', 'creator'])
            ->where('type', $type);

        // Filters
        if ($request->filled('category_id')) {
            $query->whereHas('revenue', function ($q) use ($request) {
                $q->where('revenue_category_id', (string) $request->string('category_id'));
            });
        }
        if ($request->filled('class_room_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('class_room_id', (string) $request->string('class_room_id'));
            });
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->string('to'));
        }
        if ($request->filled('q')) {
            $q = '%' . str_replace('%', '\\%', (string) $request->string('q')) . '%';
            $query->where(function ($sub) use ($q) {
                $sub->whereHas('revenue', fn ($r) => $r->where('bill_no', 'like', $q))
                    ->orWhereHas('student', function ($s) use ($q) {
                        $s->where('name', 'like', $q)
                          ->orWhere('admission_number', 'like', $q)
                          ->orWhere('phone', 'like', $q)
                          ->orWhere('whatsapp_number', 'like', $q);
                    });
            });
        }

        $query->orderByDesc('created_at');

        $filters = $request->only(['category_id', 'class_room_id', 'from', 'to', 'q']);
        $totalAmount = (float) (clone $query)->sum('amount');

        // PDF download
        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $rows = $query->get();
            $html = view($type === 'refund' ? 'reports.fee-refunds-pdf' : 'reports.fee-discounts-pdf', [
                'items' => $rows,
                'totalAmount' => $totalAmount,
                'filters' => $filters,
                'categories' => RevenueCategory::query()->orderBy('name')->get(),
                'classRooms' => ClassRoom::query()->orderBy('name')->get(),
            ])->render();

            $pdf = Pdf::loadHTML($html)->setPaper('a4');
            return $pdf->download(($type === 'refund' ? 'refund' : 'waiver') . '-report-' . now()->format('Y-m-d') . '.pdf');
        }

        // CSV download
        if ($request->boolean('download')) {
            $this->authorizeDownload($request);
            $rows = $query->get();
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Date', 'Bill No', 'Student', 'Admission No', 'Class', 'Category', 'Amount', 'Reason', 'By']);
                foreach ($rows as $a) {
                    fputcsv($out, [
                        optional($a->created_at)->format('Y-m-d'),
                        $a->revenue?->bill_no,
                        $a->student?->name,
                        $a->student?->admission_number,
                        $a->student?->classRoom?->name,
                        $a->revenue?->category?->name,
                        number_format((float) $a->amount, 2, '.', ''),
                        $a->reason,
                        $a->creator?->name,
                    ]);
                }
                fclose($out);
            }, ($type === 'refund' ? 'refund' : 'waiver') . '-report.csv', ['Content-Type' => 'text/csv']);
        }

        return view($type === 'refund' ? 'reports.fee-refunds' : 'reports.fee-discounts', [
            'items' => $query->paginate(20)->withQueryString(),
            'filters' => $filters,
            'categories' => RevenueCategory::query()->orderBy('name')->get(),
            'classRooms' => ClassRoom::query()->orderBy('name')->get(),
            'totalAmount' => $totalAmount,
            'type' => $type,
        ]);
    }

    private function teacherDeductionReport(Request $request, string $type)
    {
        $query = TeacherSalaryPayment::query()->with('teacher');

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->string('teacher_id'));
        }

        if ($request->filled('teacher_status')) {
            $status = $request->string('teacher_status');
            if ($status === '1') {
                $query->whereHas('teacher', fn ($q) => $q->where('active', true));
            } elseif ($status === '0') {
                $query->whereHas('teacher', fn ($q) => $q->where('active', false));
            }
        }

        if ($request->filled('from')) {
            $query->whereDate('paid_at', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('paid_at', '<=', $request->string('to'));
        }
        if ($request->filled('payment_month')) {
            $query->where('payment_month', $request->string('payment_month'));
        }

        $query->orderByDesc('paid_at');

        $groupByMonth = $request->boolean('group_by_month');
        $filters = $request->only(['teacher_id', 'teacher_status', 'from', 'to', 'payment_month', 'group_by_month']);
        $teachers = Teacher::query()->orderBy('name')->get();

        $allRows = $query->get();
        $totalType = 0.0;
        $teacherSummary = [];
        $monthTotals = [];
        foreach ($allRows as $p) {
            $amt = $this->deductionAmount($p, $type);
            $totalType += $amt;

            $tid = (int) $p->teacher_id;
            if (! isset($teacherSummary[$tid])) {
                $teacherSummary[$tid] = [
                    'teacher' => $p->teacher,
                    'payments' => 0,
                    'total' => 0.0,
                ];
            }
            $teacherSummary[$tid]['payments']++;
            $teacherSummary[$tid]['total'] += $amt;

            if ($groupByMonth) {
                $monthKey = (string) ($p->payment_month ?: optional($p->paid_at)->format('Y-m'));
                if ($monthKey !== '') {
                    if (! isset($monthTotals[$monthKey])) {
                        $label = $monthKey;
                        try {
                            $label = \Carbon\Carbon::parse($monthKey . '-01')->format('M Y');
                        } catch (\Throwable $e) {
                            // keep raw label
                        }
                        $monthTotals[$monthKey] = [
                            'month' => $monthKey,
                            'label' => $label,
                            'payments' => 0,
                            'total' => 0.0,
                        ];
                    }
                    $monthTotals[$monthKey]['payments']++;
                    $monthTotals[$monthKey]['total'] += $amt;
                }
            }
        }

        if ($groupByMonth) {
            ksort($monthTotals);
        } else {
            $monthTotals = [];
        }

        $teacherSummary = array_values($teacherSummary);
        usort($teacherSummary, fn ($a, $b) => ($b['total'] <=> $a['total']) ?: strcmp((string) ($a['teacher']?->name ?? ''), (string) ($b['teacher']?->name ?? '')));

        // PDF Download
        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $html = view($type === 'EPF' ? 'reports.teacher-epf-pdf' : 'reports.teacher-etf-pdf', [
                'items' => $allRows,
                'totalAmount' => $totalType,
                'filters' => $filters,
                'teachers' => $teachers,
                'teacherSummary' => $teacherSummary,
                'groupByMonth' => $groupByMonth,
                'monthTotals' => array_values($monthTotals),
                'type' => $type,
            ])->render();

            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            return $pdf->download(strtolower($type) . '-report-' . now()->format('Y-m-d') . '.pdf');
        }

        // CSV Download
        if ($request->boolean('download')) {
            $this->authorizeDownload($request);
            return response()->streamDownload(function () use ($allRows, $type) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Receipt No', 'Payment Month', 'Paid At', 'Teacher', 'Basic Salary', $type]);
                foreach ($allRows as $row) {
                    fputcsv($out, [
                        $row->receipt_number,
                        $row->payment_month,
                        optional($row->paid_at)->format('Y-m-d'),
                        $row->teacher?->name,
                        $row->base_salary,
                        number_format($this->deductionAmount($row, $type), 2, '.', ''),
                    ]);
                }
                fclose($out);
            }, strtolower($type) . '-report.csv', ['Content-Type' => 'text/csv']);
        }

        $items = $query->paginate(20)->withQueryString();
        $items->getCollection()->transform(function ($p) use ($type) {
            $p->deduction_amount = $this->deductionAmount($p, $type);
            return $p;
        });

        return view($type === 'EPF' ? 'reports.teacher-epf' : 'reports.teacher-etf', [
            'items' => $items,
            'filters' => $filters,
            'teachers' => $teachers,
            'totalAmount' => $totalType,
            'teacherSummary' => $teacherSummary,
            'groupByMonth' => $groupByMonth,
            'monthTotals' => array_values($monthTotals),
            'type' => $type,
        ]);
    }

    private function deductionAmount(TeacherSalaryPayment $payment, string $type): float
    {
        $deductions = $payment->deductions;
        if (! is_array($deductions)) {
            return 0.0;
        }

        $wanted = strtolower($type);
        $sum = 0.0;
        foreach ($deductions as $d) {
            $reason = strtolower((string) ($d['reason'] ?? ''));
            if ($reason === $wanted) {
                $sum += (float) ($d['amount'] ?? 0);
            }
        }
        return round($sum, 2);
    }
}
