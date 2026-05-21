<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\RevenueAdjustment;
use App\Models\SeminarTeacherPayment;
use App\Models\Student;
use App\Models\StudentMonthFeeAllocation;
use App\Models\ExtraClassTeacherPayment;
use App\Models\Teacher;
use App\Models\TeacherSalaryPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

class ReportController extends Controller
{
    private function canAny(Request $request, array $permissions): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        foreach ($permissions as $perm) {
            if ($user->can($perm)) {
                return true;
            }
        }

        return false;
    }

    private function paymentMethodLabel(?string $method): string
    {
        $method = $method ?: 'cash';
        return match ($method) {
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            default => 'Cash',
        };
    }

    private function authorizeDownload(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->can('reports.download')) {
            abort(403);
        }
    }

    /**
     * Resolve common date presets into from/to strings (Y-m-d).
     * Supported: today, yesterday, this_week, last_week, this_month, last_month, month, custom
     * - preset=month expects month=YYYY-MM
     * - preset=custom uses provided from/to
     *
     * @return array{from:?string,to:?string,from_month:?string,to_month:?string,preset:string}
     */
    private function resolveDatePreset(Request $request): array
    {
        $preset = (string) ($request->query('preset') ?: 'custom');
        $preset = strtolower(trim($preset));

        $from = $request->query('from');
        $to = $request->query('to');
        $month = (string) ($request->query('month') ?: '');

        $now = now();

        $start = null;
        $end = null;

        try {
            if ($preset === 'today') {
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->startOfDay();
            } elseif ($preset === 'yesterday') {
                $start = $now->copy()->subDay()->startOfDay();
                $end = $start->copy();
            } elseif ($preset === 'this_week') {
                $start = $now->copy()->startOfWeek()->startOfDay();
                $end = $now->copy()->endOfWeek()->startOfDay();
            } elseif ($preset === 'last_week') {
                $start = $now->copy()->subWeek()->startOfWeek()->startOfDay();
                $end = $now->copy()->subWeek()->endOfWeek()->startOfDay();
            } elseif ($preset === 'this_month') {
                $start = $now->copy()->startOfMonth()->startOfDay();
                $end = $now->copy()->endOfMonth()->startOfDay();
            } elseif ($preset === 'last_month') {
                $start = $now->copy()->subMonthNoOverflow()->startOfMonth()->startOfDay();
                $end = $now->copy()->subMonthNoOverflow()->endOfMonth()->startOfDay();
            } elseif ($preset === 'month') {
                if ($month !== '') {
                    $m = Carbon::parse($month . '-01');
                    $start = $m->copy()->startOfMonth()->startOfDay();
                    $end = $m->copy()->endOfMonth()->startOfDay();
                }
            } elseif ($preset === 'custom') {
                // handled below
            } else {
                $preset = 'custom';
            }
        } catch (\Throwable $e) {
            $preset = 'custom';
            $start = null;
            $end = null;
        }

        if ($preset === 'custom') {
            $start = null;
            $end = null;
            try {
                if (! empty($from)) {
                    $start = Carbon::parse((string) $from)->startOfDay();
                }
            } catch (\Throwable $e) {
                $start = null;
            }
            try {
                if (! empty($to)) {
                    $end = Carbon::parse((string) $to)->startOfDay();
                }
            } catch (\Throwable $e) {
                $end = null;
            }
        }

        if ($start && $end && $end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $fromStr = $start ? $start->toDateString() : (is_string($from) ? $from : null);
        $toStr = $end ? $end->toDateString() : (is_string($to) ? $to : null);

        $fromMonth = null;
        $toMonth = null;
        try {
            if ($start) {
                $fromMonth = $start->format('Y-m');
            } elseif (is_string($fromStr) && $fromStr !== '') {
                $fromMonth = Carbon::parse($fromStr)->format('Y-m');
            }
            if ($end) {
                $toMonth = $end->format('Y-m');
            } elseif (is_string($toStr) && $toStr !== '') {
                $toMonth = Carbon::parse($toStr)->format('Y-m');
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return [
            'from' => $fromStr,
            'to' => $toStr,
            'from_month' => $fromMonth,
            'to_month' => $toMonth,
            'preset' => $preset,
        ];
    }

    private function sanitizeSpreadsheetValue($value)
    {
        if (is_string($value)) {
            $trimmed = ltrim($value);
            if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@'], true)) {
                return "'" . $value;
            }
        }
        return $value;
    }

    /**
     * Download a table as CSV (download=1) or XLSX (excel=1 or format=xlsx).
     *
     * @param array<int,string> $headers
     * @param iterable<mixed> $rows
     * @param callable $rowMapper fn($row): array<int,mixed>
     */
    private function downloadTable(Request $request, string $baseFilename, array $headers, iterable $rows, callable $rowMapper)
    {
        $wantsXlsx = $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx';
        if (! $wantsXlsx) {
            $filename = $baseFilename . '.csv';
            return response()->streamDownload(function () use ($headers, $rows, $rowMapper) {
                $out = fopen('php://output', 'w');
                fputcsv($out, $headers);
                foreach ($rows as $row) {
                    fputcsv($out, $rowMapper($row));
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        $filename = $baseFilename . '.xlsx';
        return response()->streamDownload(function () use ($headers, $rows, $rowMapper) {
            $sheet = new Spreadsheet();
            $ws = $sheet->getActiveSheet();

            $col = 1;
            foreach ($headers as $h) {
                $ws->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1', $this->sanitizeSpreadsheetValue($h));
                $col++;
            }

            $r = 2;
            foreach ($rows as $row) {
                $values = $rowMapper($row);
                $c = 1;
                foreach ($values as $v) {
                    $ws->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r, $this->sanitizeSpreadsheetValue($v));
                    $c++;
                }
                $r++;
            }

            $writer = new Xlsx($sheet);
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function exports(Request $request): View
    {
        $preset = $this->resolveDatePreset($request);
        return view('reports.exports', [
            'preset' => $preset,
        ]);
    }

    public function downloadAllPdfBundle(Request $request)
    {
        $this->authorizeDownload($request);

        if (! class_exists(ZipArchive::class)) {
            abort(500, 'ZIP extension is not enabled on this server.');
        }

        $preset = $this->resolveDatePreset($request);
        $from = $preset['from'];
        $to = $preset['to'];

        // Build internal requests for each report and capture PDF bytes
        $reports = [
            [
                'name' => 'Revenue Report',
                'perm' => ['reports.revenue.view'],
                'call' => fn () => $this->revenue($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to])),
                'file' => '01-revenue.pdf',
            ],
            [
                'name' => 'Expense Report',
                'perm' => ['reports.expense.view'],
                'call' => fn () => $this->expense($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to, 'include_salary' => (int) $request->boolean('include_salary')])),
                'file' => '02-expense.pdf',
            ],
            [
                'name' => 'All Outflows',
                'perm' => ['reports.outflows.view'],
                'call' => fn () => $this->outflows($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to, 'method' => (string) $request->query('method', 'all')])),
                'file' => '02b-all-outflows.pdf',
            ],
            [
                'name' => 'Financial Summary',
                'perm' => ['reports.financial.view'],
                'call' => fn () => $this->financial($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to])),
                'file' => '03-financial.pdf',
            ],
            [
                'name' => 'Daily Ledger',
                'perm' => ['reports.daily_ledger.view'],
                'call' => fn () => $this->dailyLedger($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to])),
                'file' => '04-daily-ledger.pdf',
            ],
            [
                'name' => 'Cash Transactions',
                'perm' => ['reports.cash_transactions.view'],
                'call' => fn () => $this->cashTransactions($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to])),
                'file' => '05-cash-transactions.pdf',
            ],
            [
                'name' => 'Bank Transactions',
                'perm' => ['reports.bank_transactions.view'],
                'call' => fn () => $this->bankTransactions($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to, 'include_pending_cheques' => (int) $request->boolean('include_pending_cheques')])),
                'file' => '06-bank-transactions.pdf',
            ],
            [
                'name' => 'Cheque History',
                'perm' => ['reports.cheque_history.view'],
                'call' => fn () => $this->chequeHistory($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to, 'status' => (string) $request->query('status', 'all'), 'type' => (string) $request->query('type', 'all')])),
                'file' => '07-cheque-history.pdf',
            ],
            [
                'name' => 'Teacher EPF',
                'perm' => ['reports.teacher_epf.view'],
                'call' => fn () => $this->teacherEpf($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to])),
                'file' => '08-teacher-epf.pdf',
            ],
            [
                'name' => 'Company EPF',
                'perm' => ['reports.company_epf.view'],
                'call' => fn () => $this->companyEpf($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to])),
                'file' => '09-company-epf.pdf',
            ],
            [
                'name' => 'Company ETF',
                'perm' => ['reports.teacher_etf.view'],
                'call' => fn () => $this->teacherEtf($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to])),
                'file' => '10-company-etf.pdf',
            ],
            [
                'name' => 'EPF/ETF Totals',
                'perm' => ['reports.epf_etf_totals.view'],
                'call' => fn () => $this->epfEtfTotals($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to])),
                'file' => '11-epf-etf-totals.pdf',
            ],
            [
                'name' => 'Students Report',
                'perm' => ['reports.view'],
                'call' => fn () => $this->students($this->internalRequest($request, ['pdf' => 1, 'class_room_id' => $request->query('class_room_id')])),
                'file' => '11b-students.pdf',
            ],
            [
                'name' => 'Student Due',
                'perm' => ['reports.student_due.view'],
                'call' => fn () => $this->studentDue($this->internalRequest($request, ['pdf' => 1])),
                'file' => '12-student-due.pdf',
            ],
            [
                'name' => 'Fee Collection Summary',
                'perm' => ['reports.fee_collection_summary.view'],
                'call' => fn () => $this->feeCollectionSummary($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to, 'group' => (string) $request->query('group', 'day')])),
                'file' => '13-fee-collection-summary.pdf',
            ],
            [
                'name' => 'Fee Collection by Class',
                'perm' => ['reports.fee_collection_by_class.view'],
                'call' => fn () => $this->feeCollectionByClass($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to])),
                'file' => '14-fee-collection-by-class.pdf',
            ],
            [
                'name' => 'Fee Collection by Category',
                'perm' => ['reports.fee_collection_by_category.view'],
                'call' => fn () => $this->feeCollectionByCategory($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to])),
                'file' => '15-fee-collection-by-category.pdf',
            ],
            [
                'name' => 'Collected vs Expected',
                'perm' => ['reports.fee_collection_vs_expected.view'],
                'call' => fn () => $this->feeCollectionVsExpected($this->internalRequest($request, [
                    'pdf' => 1,
                    'from_month' => $preset['from_month'] ?: now()->format('Y-m'),
                    'to_month' => $preset['to_month'] ?: now()->format('Y-m'),
                    'only_active' => (string) $request->query('only_active', '1'),
                ])),
                'file' => '16-collected-vs-expected.pdf',
            ],
            [
                'name' => 'Student Due Aging',
                'perm' => ['reports.student_due_aging.view'],
                'call' => fn () => $this->studentDueAging($this->internalRequest($request, ['pdf' => 1, 'only_active' => (string) $request->query('only_active', '1')])),
                'file' => '17-student-due-aging.pdf',
            ],
            [
                'name' => 'Top Due Students',
                'perm' => ['reports.student_top_due.view'],
                'call' => fn () => $this->studentTopDue($this->internalRequest($request, ['pdf' => 1, 'limit' => (int) $request->query('limit', 20)])),
                'file' => '18-top-due-students.pdf',
            ],
            [
                'name' => 'Discount/Waiver Report',
                'perm' => ['reports.fee_discounts.view'],
                'call' => fn () => $this->feeDiscounts($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to])),
                'file' => '19-waivers.pdf',
            ],
            [
                'name' => 'Refund Report',
                'perm' => ['reports.fee_refunds.view'],
                'call' => fn () => $this->feeRefunds($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to])),
                'file' => '20-refunds.pdf',
            ],
            [
                'name' => 'Seminars Collection',
                'perm' => ['reports.seminars_collection.view', 'reports.view'],
                'call' => fn () => $this->seminarsCollection($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to])),
                'file' => '21-seminars-collection.pdf',
            ],
            [
                'name' => 'Extra Classes Collection',
                'perm' => ['reports.extra_classes_collection.view', 'reports.view'],
                'call' => fn () => $this->extraClassesCollection($this->internalRequest($request, ['pdf' => 1, 'from' => $from, 'to' => $to])),
                'file' => '22-extra-classes-collection.pdf',
            ],
        ];

        $zipPath = tempnam(sys_get_temp_dir(), 'reports-');
        if ($zipPath === false) {
            abort(500, 'Unable to create temporary file.');
        }
        // ZipArchive requires a .zip file extension in some environments
        $finalZipPath = $zipPath . '.zip';
        if (! @rename($zipPath, $finalZipPath)) {
            @unlink($zipPath);
            abort(500, 'Unable to initialize ZIP archive file.');
        }

        $zip = new ZipArchive();
        if ($zip->open($finalZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($finalZipPath);
            abort(500, 'Unable to create ZIP archive.');
        }

        $added = 0;
        foreach ($reports as $rep) {
            if (! $this->canAny($request, $rep['perm'])) {
                continue;
            }

            try {
                $response = ($rep['call'])();
                if (! method_exists($response, 'getContent')) {
                    continue;
                }
                $content = (string) $response->getContent();
                if ($content === '' || strncmp($content, '%PDF', 4) !== 0) {
                    continue;
                }
                $zip->addFromString($rep['file'], $content);
                $added++;
            } catch (\Throwable $e) {
                Log::warning('Failed adding report to PDF bundle; continuing with remaining reports.', [
                    'report' => $rep['name'] ?? $rep['file'],
                    'file' => $rep['file'] ?? null,
                    'user_id' => $request->user()?->id,
                    'route' => $request->route()?->getName(),
                    'action' => optional($request->route())->getActionName(),
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }
        $zip->close();

        if ($added === 0) {
            @unlink($finalZipPath);
            abort(403, 'No permitted PDF reports available for bundling.');
        }

        $labelFrom = $from ?: now()->toDateString();
        $labelTo = $to ?: $labelFrom;
        $bundleName = 'reports-' . $labelFrom . '-to-' . $labelTo . '.zip';

        return response()->download($finalZipPath, $bundleName)->deleteFileAfterSend(true);
    }

    private function internalRequest(Request $original, array $query): Request
    {
        $r = Request::create('/', 'GET', $query);
        $r->setUserResolver(fn () => $original->user());
        return $r;
    }

    private function normalizeMethodFilter(Request $request, string $key = 'method'): string
    {
        $methodFilter = (string) $request->string($key);
        if ($methodFilter === '') {
            $methodFilter = 'all';
        }
        if (! in_array($methodFilter, ['all', 'cash', 'bank', 'cheque', 'bank_transfer'], true)) {
            $methodFilter = 'all';
        }
        return $methodFilter;
    }

    private function applyPaymentMethodFilter($query, string $column, string $methodFilter)
    {
        if ($methodFilter === 'all') {
            return $query;
        }
        if ($methodFilter === 'bank') {
            return $query->whereIn($column, ['bank_transfer', 'cheque']);
        }
        if ($methodFilter === 'cash') {
            return $query->where(function ($q) use ($column) {
                $q->where($column, 'cash')->orWhereNull($column);
            });
        }

        return $query->where($column, $methodFilter);
    }

    private function applyPendingChequeFilter($query, string $methodColumn, string $statusColumn, bool $includePendingCheques)
    {
        if ($includePendingCheques) {
            return $query;
        }

        // Exclude only: payment_method = cheque AND payment_status = pending
        return $query->where(function ($q) use ($methodColumn, $statusColumn) {
            $q->where($methodColumn, '!=', 'cheque')
                ->orWhereNull($methodColumn)
                ->orWhereNull($statusColumn)
                ->orWhere($statusColumn, '!=', 'pending');
        });
    }

    private function applyAccountFilter($query, string $methodColumn, string $account)
    {
        if ($account === 'bank') {
            return $query->whereIn($methodColumn, ['bank_transfer', 'cheque']);
        }

        // cash (and backwards-compatible NULLs)
        return $query->where(function ($q) use ($methodColumn) {
            $q->where($methodColumn, 'cash')->orWhereNull($methodColumn);
        });
    }

    private function sqlEffectiveDate(string $baseDateColumn, string $methodColumn, string $chequeDateColumn, ?string $statusColumn = null): string
    {
        if ($statusColumn !== null && $statusColumn !== '') {
            // For cheques:
            // - pending: count on cheque_date (expected clearing date)
            // - confirmed/returned: count on paid_at (passed/received date)
            return "DATE(CASE WHEN {$methodColumn} = 'cheque' THEN (CASE WHEN {$statusColumn} = 'pending' THEN COALESCE({$chequeDateColumn}, {$baseDateColumn}) ELSE {$baseDateColumn} END) ELSE {$baseDateColumn} END)";
        }

        return "DATE(CASE WHEN {$methodColumn} = 'cheque' THEN COALESCE({$chequeDateColumn}, {$baseDateColumn}) ELSE {$baseDateColumn} END)";
    }

    public function index(): View
    {
        return view('reports.index');
    }

    public function revenue(Request $request)
    {
        $query = Revenue::query()->with(['category', 'student']);

        $isExport = $request->boolean('pdf') || $request->boolean('download');
        $from = $request->input('from');
        $to = $request->input('to');

        // On-screen report: do not auto-filter unless user submitted a range.
        // Exports: default to today's range if user did not submit dates.
        $fromForQuery = $isExport ? ($from ?: now()->toDateString()) : $from;
        $toForQuery = $isExport ? ($to ?: now()->toDateString()) : $to;

        if ($request->filled('category_id')) {
            $query->where('revenue_category_id', $request->string('category_id'));
        }
        if (! empty($fromForQuery)) {
            $query->whereDate('paid_at', '>=', $fromForQuery);
        }
        if (! empty($toForQuery)) {
            $query->whereDate('paid_at', '<=', $toForQuery);
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
                'filters' => [
                    'category_id' => $request->input('category_id'),
                    'from' => $fromForQuery,
                    'to' => $toForQuery,
                ],
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

        // Handle CSV/XLSX Download
        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            $rows = $query->get();

            return $this->downloadTable(
                $request,
                'revenue-report',
                ['Bill No', 'Date', 'Category', 'Student', 'Amount', 'Notes'],
                $rows,
                function ($row) {
                    return [
                        $row->bill_no,
                        optional($row->paid_at)->format('Y-m-d'),
                        $row->category?->name,
                        $row->student?->name,
                        (float) $row->amount,
                        $row->notes,
                    ];
                }
            );
        }

        return view('reports.revenue', [
            'items' => $query->paginate(20)->withQueryString(),
            'categories' => RevenueCategory::query()->orderBy('name')->get(),
            'filters' => [
                'category_id' => $request->input('category_id'),
                'from' => $from ?: now()->toDateString(),
                'to' => $to ?: now()->toDateString(),
            ],
        ]);
    }

    public function expense(Request $request)
    {
        $query = Expense::query()->with(['category']);

        $includeSalary = $request->boolean('include_salary');
        $salaryQuery = TeacherSalaryPayment::query()->with('teacher');

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->string('category_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('expense_date', '>=', $request->string('from'));
            $salaryQuery->whereDate('paid_at', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('expense_date', '<=', $request->string('to'));
            $salaryQuery->whereDate('paid_at', '<=', $request->string('to'));
        }

        $query->orderByDesc('expense_date');

        $expenseTotalCount = (clone $query)->count();
        $expenseTotalAmount = (float) (clone $query)->sum('amount');

        $salaryTotalCount = $includeSalary ? (clone $salaryQuery)->count() : 0;
        $salaryTotalAmount = $includeSalary ? (float) (clone $salaryQuery)->sum('amount') : 0.0;

        $combinedTotalCount = $expenseTotalCount + $salaryTotalCount;
        $combinedTotalAmount = $expenseTotalAmount + $salaryTotalAmount;
        $combinedAvgAmount = $combinedTotalCount > 0 ? ($combinedTotalAmount / $combinedTotalCount) : 0.0;

        // Handle PDF Download
        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $rows = $query->get();
            $salaryRows = $includeSalary ? $salaryQuery->get() : collect();
            $totalAmount = (float) $rows->sum('amount') + (float) $salaryRows->sum('amount');
            
            $html = view('reports.expense-pdf', [
                'items' => $rows,
                'salaryPayments' => $salaryRows,
                'includeSalary' => $includeSalary,
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

        // Handle CSV/XLSX Download
        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            if ($includeSalary) {
                $expenseRows = $query->get()->map(function ($e) {
                    return [
                        optional($e->expense_date)->format('Y-m-d'),
                        'Expense',
                        $e->category?->name,
                        '—',
                        (float) $e->amount,
                        $e->notes,
                    ];
                });

                $salaryRows = $salaryQuery->get()->map(function ($p) {
                    return [
                        optional($p->paid_at)->format('Y-m-d'),
                        'Salary',
                        'Salary Payment',
                        $p->teacher?->name,
                        (float) $p->amount,
                        $p->notes,
                    ];
                });

                $rows = $expenseRows->concat($salaryRows);
            } else {
                $rows = $query->get();
            }

            return $this->downloadTable(
                $request,
                'expense-report',
                $includeSalary ? ['Date', 'Type', 'Category', 'Party', 'Amount', 'Notes'] : ['Date', 'Category', 'Amount', 'Notes'],
                $rows,
                function ($row) {
                    if (is_array($row)) {
                        return $row;
                    }

                    return [
                        optional($row->expense_date)->format('Y-m-d'),
                        $row->category?->name,
                        (float) $row->amount,
                        $row->notes,
                    ];
                }
            );
        }

        $salaryPayments = $includeSalary
            ? $salaryQuery->orderByDesc('paid_at')->limit(500)->get()
            : collect();

        return view('reports.expense', [
            'items' => $query->paginate(20)->withQueryString(),
            'salaryPayments' => $salaryPayments,
            'includeSalary' => $includeSalary,
            'categories' => ExpenseCategory::query()->orderBy('name')->get(),
            'filters' => $request->only(['category_id', 'from', 'to']),
            'summary' => [
                'total_count' => $combinedTotalCount,
                'total_amount' => $combinedTotalAmount,
                'avg_amount' => $combinedAvgAmount,
                'expense_amount' => $expenseTotalAmount,
                'salary_amount' => $salaryTotalAmount,
            ],
        ]);
    }

    public function financial(Request $request)
    {
        // Date range (default to today)
        $start = $request->filled('from') ? \Carbon\Carbon::parse((string) $request->string('from'))->startOfDay() : now()->startOfDay();
        $end = $request->filled('to') ? \Carbon\Carbon::parse((string) $request->string('to'))->startOfDay() : $start->copy();

        $groupDaily = $request->boolean('daily'); // default off => aggregated

        $methodFilter = $this->normalizeMethodFilter($request, 'method');

        $applyPaymentFilter = function ($query, string $column) use ($methodFilter) {
            return $this->applyPaymentMethodFilter($query, $column, $methodFilter);
        };

        $isBankMethod = function (?string $method): bool {
            return in_array($method ?? 'cash', ['bank_transfer', 'cheque'], true);
        };

        // Opening balance (B.B.F) = Net revenue - expenses before start date
        $grossBeforeQ = Revenue::query()->whereDate('paid_at', '<', $start->toDateString());
        $grossBeforeQ = $applyPaymentFilter($grossBeforeQ, 'payment_method');
        $grossBefore = (float) $grossBeforeQ->sum('amount');

        $refundBeforeQ = DB::table('revenue_adjustments')
            ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
            ->where('revenue_adjustments.type', 'refund')
            ->whereDate('revenues.paid_at', '<', $start->toDateString());
        $refundBeforeQ = $applyPaymentFilter($refundBeforeQ, 'revenues.payment_method');
        $refundBefore = (float) $refundBeforeQ->sum('revenue_adjustments.amount');

        $expenseBeforeQ = Expense::query()->whereDate('expense_date', '<', $start->toDateString());
        $expenseBeforeQ = $applyPaymentFilter($expenseBeforeQ, 'payment_method');
        $expenseBefore = (float) $expenseBeforeQ->sum('amount');
        $openingBalance = ($grossBefore - $refundBefore) - $expenseBefore; // can be negative

        $days = [];
        if ($groupDaily) {
            // Per-day detailed ledger
            $cursor = $start->copy();
            $runningOpening = $openingBalance;
            while ($cursor->lte($end)) {
                $dateStr = $cursor->toDateString();

                $incomeQuery = Revenue::query()
                    ->leftJoin('students', 'students.id', '=', 'revenues.student_id')
                    ->leftJoin('revenue_categories', 'revenue_categories.id', '=', 'revenues.revenue_category_id')
                    ->whereDate('revenues.paid_at', $dateStr)
                    ->orderBy('revenues.paid_at')
                    ->select([
                        'revenues.id', 'revenues.bill_no', 'revenues.amount', 'revenues.notes',
                        'students.name as student_name', 'revenue_categories.name as category_name',
                        'revenues.payment_method',
                    ])
                    ;
                $incomeQuery = $applyPaymentFilter($incomeQuery, 'revenues.payment_method');

                $incomes = $incomeQuery->get()
                    ->map(function ($r) use ($isBankMethod) {
                        $desc = $r->student_name ? $r->student_name : ($r->category_name ?: 'Income');
                        if (!empty($r->notes)) { $desc .= ' - ' . $r->notes; }

                        $pm = (string) ($r->payment_method ?? 'cash');
                        $isBank = $isBankMethod($pm);
                        if ($isBank) { $desc .= ' (BANK)'; }

                        return [
                            'ref' => $r->bill_no ?: '—',
                            'description' => $desc,
                            'amount' => (float) $r->amount,
                            'payment_method' => $pm,
                            'account' => $isBank ? 'bank' : 'cash',
                        ];
                    })
                    ->all();

                $refundsQuery = DB::table('revenue_adjustments')
                    ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
                    ->where('revenue_adjustments.type', 'refund')
                    ->whereDate('revenues.paid_at', $dateStr)
                    ->select(['revenue_adjustments.amount', 'revenues.bill_no', 'revenues.notes', 'revenues.payment_method']);
                $refundsQuery = $applyPaymentFilter($refundsQuery, 'revenues.payment_method');

                $refunds = $refundsQuery->get()
                    ->map(function ($r) use ($isBankMethod) {
                        $pm = (string) ($r->payment_method ?? 'cash');
                        $isBank = $isBankMethod($pm);
                        $desc = 'Refund';
                        if ($isBank) { $desc .= ' (BANK)'; }
                        return [
                            'ref' => $r->bill_no ?: '—',
                            'description' => $desc,
                            'amount' => -1 * (float) $r->amount,
                            'payment_method' => $pm,
                            'account' => $isBank ? 'bank' : 'cash',
                        ];
                    })
                    ->all();

                $expenseQuery = Expense::query()
                    ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
                    ->whereDate('expenses.expense_date', $dateStr)
                    ->orderBy('expenses.expense_date')
                    ->select(['expenses.id', 'expenses.amount', 'expenses.notes', 'expenses.payment_method', 'expense_categories.name as category_name'])
                    ;
                $expenseQuery = $applyPaymentFilter($expenseQuery, 'expenses.payment_method');

                $expenses = $expenseQuery->get()
                    ->map(function ($e) use ($isBankMethod) {
                        $desc = $e->category_name ?: 'Expense';
                        if (!empty($e->notes)) { $desc .= ' - ' . $e->notes; }

                        $pm = (string) ($e->payment_method ?? 'cash');
                        $isBank = $isBankMethod($pm);
                        if ($isBank) { $desc .= ' (BANK)'; }

                        return [
                            'description' => $desc,
                            'amount' => (float) $e->amount,
                            'payment_method' => $pm,
                            'account' => $isBank ? 'bank' : 'cash',
                        ];
                    })
                    ->all();

                $incomeCash = array_sum(array_map(fn ($i) => ($i['account'] ?? 'cash') === 'cash' ? (float) $i['amount'] : 0.0, $incomes));
                $incomeBank = array_sum(array_map(fn ($i) => ($i['account'] ?? 'cash') === 'bank' ? (float) $i['amount'] : 0.0, $incomes));

                $refundOutCash = array_sum(array_map(fn ($i) => ($i['account'] ?? 'cash') === 'cash' ? abs((float) $i['amount']) : 0.0, $refunds));
                $refundOutBank = array_sum(array_map(fn ($i) => ($i['account'] ?? 'cash') === 'bank' ? abs((float) $i['amount']) : 0.0, $refunds));

                $expenseCash = array_sum(array_map(fn ($i) => ($i['account'] ?? 'cash') === 'cash' ? (float) $i['amount'] : 0.0, $expenses)) + $refundOutCash;
                $expenseBank = array_sum(array_map(fn ($i) => ($i['account'] ?? 'cash') === 'bank' ? (float) $i['amount'] : 0.0, $expenses)) + $refundOutBank;

                $incomeTotal = array_sum(array_map(fn ($i) => (float) $i['amount'], array_merge($incomes, $refunds)));
                $expenseTotal = array_sum(array_map(fn ($c) => (float) $c['amount'], $expenses));
                $closing = $runningOpening + $incomeTotal - $expenseTotal;

                $bbfDate = $cursor->copy()->subDay()->format('M d, Y');

                $days[] = [
                    'date' => $cursor->copy(),
                    'opening' => $runningOpening,
                    'debits' => array_merge([
                        ['ref' => 'B.B.F', 'description' => "Balance Brought Forward (As of $bbfDate)", 'amount' => $runningOpening],
                    ], $incomes, $refunds),
                    'credits' => $expenses,
                    'income_cash' => $incomeCash,
                    'income_bank' => $incomeBank,
                    'expense_cash' => $expenseCash,
                    'expense_bank' => $expenseBank,
                    'income_total' => $incomeTotal,
                    'expense_total' => $expenseTotal,
                    'closing' => $closing,
                ];

                $runningOpening = $closing;
                $cursor->addDay();
            }
        } else {
            // Aggregated ledger for whole range
            $revenueQ = Revenue::query()
                ->whereDate('paid_at', '>=', $start->toDateString())
                ->whereDate('paid_at', '<=', $end->toDateString())
                ;
            $revenueQ = $applyPaymentFilter($revenueQ, 'payment_method');
            $revenueSum = (float) $revenueQ->sum('amount');

            $refundQ = DB::table('revenue_adjustments')
                ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
                ->where('revenue_adjustments.type', 'refund')
                ->whereDate('revenues.paid_at', '>=', $start->toDateString())
                ->whereDate('revenues.paid_at', '<=', $end->toDateString())
                ;
            $refundQ = $applyPaymentFilter($refundQ, 'revenues.payment_method');
            $refundSum = (float) $refundQ->sum('revenue_adjustments.amount');

            $expenseQ = Expense::query()
                ->whereDate('expense_date', '>=', $start->toDateString())
                ->whereDate('expense_date', '<=', $end->toDateString())
                ;
            $expenseQ = $applyPaymentFilter($expenseQ, 'payment_method');
            $expenseSum = (float) $expenseQ->sum('amount');

            $revCashQ = Revenue::query()
                ->whereDate('paid_at', '>=', $start->toDateString())
                ->whereDate('paid_at', '<=', $end->toDateString())
                ->where(function ($q) {
                    $q->where('payment_method', 'cash')->orWhereNull('payment_method');
                });
            $revBankQ = Revenue::query()
                ->whereDate('paid_at', '>=', $start->toDateString())
                ->whereDate('paid_at', '<=', $end->toDateString())
                ->whereIn('payment_method', ['bank_transfer', 'cheque']);

            $refundCashQ = DB::table('revenue_adjustments')
                ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
                ->where('revenue_adjustments.type', 'refund')
                ->whereDate('revenues.paid_at', '>=', $start->toDateString())
                ->whereDate('revenues.paid_at', '<=', $end->toDateString())
                ->where(function ($q) {
                    $q->where('revenues.payment_method', 'cash')->orWhereNull('revenues.payment_method');
                });
            $refundBankQ = DB::table('revenue_adjustments')
                ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
                ->where('revenue_adjustments.type', 'refund')
                ->whereDate('revenues.paid_at', '>=', $start->toDateString())
                ->whereDate('revenues.paid_at', '<=', $end->toDateString())
                ->whereIn('revenues.payment_method', ['bank_transfer', 'cheque']);

            $expCashQ = Expense::query()
                ->whereDate('expense_date', '>=', $start->toDateString())
                ->whereDate('expense_date', '<=', $end->toDateString())
                ->where(function ($q) {
                    $q->where('payment_method', 'cash')->orWhereNull('payment_method');
                });
            $expBankQ = Expense::query()
                ->whereDate('expense_date', '>=', $start->toDateString())
                ->whereDate('expense_date', '<=', $end->toDateString())
                ->whereIn('payment_method', ['bank_transfer', 'cheque']);

            $revCashQ = $applyPaymentFilter($revCashQ, 'payment_method');
            $revBankQ = $applyPaymentFilter($revBankQ, 'payment_method');
            $refundCashQ = $applyPaymentFilter($refundCashQ, 'revenues.payment_method');
            $refundBankQ = $applyPaymentFilter($refundBankQ, 'revenues.payment_method');
            $expCashQ = $applyPaymentFilter($expCashQ, 'payment_method');
            $expBankQ = $applyPaymentFilter($expBankQ, 'payment_method');

            $incomeCash = (float) $revCashQ->sum('amount');
            $incomeBank = (float) $revBankQ->sum('amount');

            $refundOutCash = (float) $refundCashQ->sum('revenue_adjustments.amount');
            $refundOutBank = (float) $refundBankQ->sum('revenue_adjustments.amount');

            $expenseCash = (float) $expCashQ->sum('amount') + $refundOutCash;
            $expenseBank = (float) $expBankQ->sum('amount') + $refundOutBank;

            $incomeTotal = $revenueSum - $refundSum;
            $expenseTotal = $expenseSum;
            $closing = $openingBalance + $incomeTotal - $expenseTotal;

            $bbfDate = $start->copy()->subDay()->format('M d, Y');
            $days[] = [
                'date' => $end->copy(), // used only when displaying single card; header will show range
                'opening' => $openingBalance,
                'debits' => [
                    ['ref' => 'B.B.F', 'description' => "Balance Brought Forward (As of $bbfDate)", 'amount' => $openingBalance],
                    ['ref' => null, 'description' => 'Income (Total)', 'amount' => $revenueSum],
                    ['ref' => null, 'description' => 'Refunds (Total)', 'amount' => -1 * $refundSum],
                ],
                'credits' => [
                    ['description' => 'Expenses (Total)', 'amount' => $expenseSum],
                ],
                'income_cash' => $incomeCash,
                'income_bank' => $incomeBank,
                'expense_cash' => $expenseCash,
                'expense_bank' => $expenseBank,
                'income_total' => $incomeTotal,
                'expense_total' => $expenseTotal,
                'closing' => $closing,
            ];
        }

        $totalRevenueInRange = collect($days)->sum('income_total');
        $totalExpenseInRange = collect($days)->sum('expense_total');

        $settings = app('settings');
        $school = [
            'name' => (string) $settings->get('school.name', config('app.name')),
            'logo' => (string) $settings->get('school.logo', ''),
            'phone' => (string) $settings->get('school.phone', ''),
            'address' => (string) $settings->get('school.address', ''),
        ];

        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $html = view('reports.financial-pdf', [
                'filters' => $request->only(['from', 'to', 'method']) + ['daily' => $groupDaily],
                'days' => $days,
                'school' => $school,
                'totalRevenue' => $totalRevenueInRange,
                'totalExpense' => $totalExpenseInRange,
                'netProfit' => $totalRevenueInRange - $totalExpenseInRange,
                'daily' => $groupDaily,
            ])->render();

            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            return $pdf->download('financial-report-' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);

            $rows = collect($days)->map(function ($d) {
                $date = ($d['date'] instanceof Carbon) ? $d['date']->toDateString() : null;
                return [
                    'date' => $date,
                    'opening' => (float) ($d['opening'] ?? 0.0),
                    'income_total' => (float) ($d['income_total'] ?? 0.0),
                    'expense_total' => (float) ($d['expense_total'] ?? 0.0),
                    'closing' => (float) ($d['closing'] ?? 0.0),
                    'income_cash' => (float) ($d['income_cash'] ?? 0.0),
                    'income_bank' => (float) ($d['income_bank'] ?? 0.0),
                    'expense_cash' => (float) ($d['expense_cash'] ?? 0.0),
                    'expense_bank' => (float) ($d['expense_bank'] ?? 0.0),
                ];
            });

            return $this->downloadTable(
                $request,
                'financial-summary-' . now()->format('Y-m-d'),
                ['Date', 'Opening', 'Income Total', 'Expense Total', 'Closing', 'Income Cash', 'Income Bank', 'Expense Cash', 'Expense Bank'],
                $rows,
                fn ($r) => [
                    $r['date'],
                    $r['opening'],
                    $r['income_total'],
                    $r['expense_total'],
                    $r['closing'],
                    $r['income_cash'],
                    $r['income_bank'],
                    $r['expense_cash'],
                    $r['expense_bank'],
                ]
            );
        }

        return view('reports.financial', [
            'filters' => $request->only(['from', 'to', 'method']) + ['daily' => $groupDaily],
            'days' => $days,
            'school' => $school,
            'totalRevenue' => $totalRevenueInRange,
            'totalExpense' => $totalExpenseInRange,
            'netProfit' => $totalRevenueInRange - $totalExpenseInRange,
            'daily' => $groupDaily,
        ]);
    }

    public function outflows(Request $request)
    {
        $methodFilter = $this->normalizeMethodFilter($request, 'method');

        $from = (string) ($request->input('from') ?: now()->toDateString());
        $to = (string) ($request->input('to') ?: $from);

        try {
            $start = Carbon::parse($from)->startOfDay();
        } catch (\Throwable $e) {
            $start = now()->startOfDay();
            $from = $start->toDateString();
        }
        try {
            $end = Carbon::parse($to)->startOfDay();
        } catch (\Throwable $e) {
            $end = $start->copy();
            $to = $end->toDateString();
        }
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
            [$from, $to] = [$to, $from];
        }

        $expenseEffectiveSql = $this->sqlEffectiveDate('expenses.expense_date', 'expenses.payment_method', 'expenses.cheque_date');

        // Exclude expense rows that are already linked to teacher payout records (we show the payout rows instead)
        $payoutExpenseIds = collect()
            ->merge(
                SeminarTeacherPayment::query()
                    ->whereNotNull('expense_id')
                    ->whereDate('paid_at', '>=', $start->toDateString())
                    ->whereDate('paid_at', '<=', $end->toDateString())
                    ->pluck('expense_id')
            )
            ->merge(
                ExtraClassTeacherPayment::query()
                    ->whereNotNull('expense_id')
                    ->whereDate('paid_at', '>=', $start->toDateString())
                    ->whereDate('paid_at', '<=', $end->toDateString())
                    ->pluck('expense_id')
            )
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        // Base expenses (excluding linked payouts)
        $expQ = Expense::query()
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->whereRaw("{$expenseEffectiveSql} >= ? AND {$expenseEffectiveSql} <= ?", [$start->toDateString(), $end->toDateString()])
            ->select([
                'expenses.id',
                'expenses.expense_date',
                DB::raw("{$expenseEffectiveSql} as effective_date"),
                'expenses.amount',
                'expenses.notes',
                'expenses.payment_method',
                'expenses.cheque_date',
                'expense_categories.name as category_name',
            ]);
        if ($payoutExpenseIds->count() > 0) {
            $expQ->whereNotIn('expenses.id', $payoutExpenseIds->all());
        }
        $expQ = $this->applyPaymentMethodFilter($expQ, 'expenses.payment_method', $methodFilter);

        $expenses = $expQ->get()->map(function ($e) {
            $pm = (string) ($e->payment_method ?? 'cash');
            $category = $e->category_name ?: 'Expense';
            $desc = $category;
            if (! empty($e->notes)) {
                $desc .= ' - ' . $e->notes;
            }
            return [
                'date' => (string) ($e->effective_date ?: optional($e->expense_date)->format('Y-m-d')),
                'type' => 'Expense',
                'category' => $category,
                'party' => '—',
                'method' => $this->paymentMethodLabel($pm),
                'amount' => (float) $e->amount,
                'notes' => (string) ($e->notes ?? ''),
            ];
        });

        // Salary payments
        $salaryQ = TeacherSalaryPayment::query()
            ->leftJoin('teachers', 'teachers.id', '=', 'teacher_salary_payments.teacher_id')
            ->whereDate('teacher_salary_payments.paid_at', '>=', $start->toDateString())
            ->whereDate('teacher_salary_payments.paid_at', '<=', $end->toDateString())
            ->select([
                'teacher_salary_payments.id',
                'teacher_salary_payments.paid_at',
                'teacher_salary_payments.amount',
                'teacher_salary_payments.notes',
                'teacher_salary_payments.payment_method',
                'teachers.name as teacher_name',
            ]);
        $salaryQ = $this->applyPaymentMethodFilter($salaryQ, 'teacher_salary_payments.payment_method', $methodFilter);

        $salary = $salaryQ->get()->map(function ($s) {
            $pm = (string) ($s->payment_method ?? 'cash');
            $teacher = $s->teacher_name ?: '—';
            $desc = 'Salary — ' . $teacher;
            if (! empty($s->notes)) {
                $desc .= ' - ' . $s->notes;
            }
            return [
                'date' => optional($s->paid_at)->format('Y-m-d') ?: '',
                'type' => 'Salary',
                'category' => 'Salary Payment',
                'party' => $teacher,
                'method' => $this->paymentMethodLabel($pm),
                'amount' => (float) $s->amount,
                'notes' => (string) ($s->notes ?? ''),
            ];
        });

        // Seminar teacher payouts (join through expenses for payment method)
        $semEffectiveSql = $this->sqlEffectiveDate('expenses.expense_date', 'expenses.payment_method', 'expenses.cheque_date');
        $semQ = SeminarTeacherPayment::query()
            ->leftJoin('seminars', 'seminars.id', '=', 'seminar_teacher_payments.seminar_id')
            ->leftJoin('expenses', 'expenses.id', '=', 'seminar_teacher_payments.expense_id')
            ->whereRaw("DATE(COALESCE({$semEffectiveSql}, seminar_teacher_payments.paid_at)) >= ? AND DATE(COALESCE({$semEffectiveSql}, seminar_teacher_payments.paid_at)) <= ?", [$start->toDateString(), $end->toDateString()])
            ->select([
                'seminar_teacher_payments.id',
                'seminar_teacher_payments.amount',
                'seminar_teacher_payments.notes',
                'seminar_teacher_payments.paid_at',
                'expenses.payment_method as expense_payment_method',
                DB::raw("{$semEffectiveSql} as effective_date"),
                'seminars.name as seminar_name',
            ]);
        $semQ = $this->applyPaymentMethodFilter($semQ, 'expenses.payment_method', $methodFilter);

        $seminarPayouts = $semQ->get()->map(function ($p) {
            $pm = (string) ($p->expense_payment_method ?? 'cash');
            $name = $p->seminar_name ?: 'Seminar';
            $desc = 'Seminar Teacher Payout — ' . $name;
            if (! empty($p->notes)) {
                $desc .= ' - ' . $p->notes;
            }
            $date = $p->effective_date ?: (optional($p->paid_at)->format('Y-m-d') ?: '');
            return [
                'date' => (string) $date,
                'type' => 'Seminar Payout',
                'category' => 'Visiting Teacher Payment',
                'party' => $name,
                'method' => $this->paymentMethodLabel($pm),
                'amount' => (float) $p->amount,
                'notes' => (string) ($p->notes ?? ''),
            ];
        });

        // Extra class teacher payouts
        $exEffectiveSql = $this->sqlEffectiveDate('expenses.expense_date', 'expenses.payment_method', 'expenses.cheque_date');
        $exQ = ExtraClassTeacherPayment::query()
            ->leftJoin('extra_classes', 'extra_classes.id', '=', 'extra_class_teacher_payments.extra_class_id')
            ->leftJoin('expenses', 'expenses.id', '=', 'extra_class_teacher_payments.expense_id')
            ->whereRaw("DATE(COALESCE({$exEffectiveSql}, extra_class_teacher_payments.paid_at)) >= ? AND DATE(COALESCE({$exEffectiveSql}, extra_class_teacher_payments.paid_at)) <= ?", [$start->toDateString(), $end->toDateString()])
            ->select([
                'extra_class_teacher_payments.id',
                'extra_class_teacher_payments.amount',
                'extra_class_teacher_payments.notes',
                'extra_class_teacher_payments.paid_at',
                'expenses.payment_method as expense_payment_method',
                DB::raw("{$exEffectiveSql} as effective_date"),
                'extra_classes.name as extra_class_name',
            ]);
        $exQ = $this->applyPaymentMethodFilter($exQ, 'expenses.payment_method', $methodFilter);

        $extraPayouts = $exQ->get()->map(function ($p) {
            $pm = (string) ($p->expense_payment_method ?? 'cash');
            $name = $p->extra_class_name ?: 'Extra Class';
            $desc = 'Extra Class Teacher Payout — ' . $name;
            if (! empty($p->notes)) {
                $desc .= ' - ' . $p->notes;
            }
            $date = $p->effective_date ?: (optional($p->paid_at)->format('Y-m-d') ?: '');
            return [
                'date' => (string) $date,
                'type' => 'Extra Class Payout',
                'category' => 'Visiting Teacher Payment',
                'party' => $name,
                'method' => $this->paymentMethodLabel($pm),
                'amount' => (float) $p->amount,
                'notes' => (string) ($p->notes ?? ''),
            ];
        });

        $rows = $expenses
            ->concat($salary)
            ->concat($seminarPayouts)
            ->concat($extraPayouts)
            ->sortBy('date')
            ->values();

        $totalAmount = (float) $rows->sum('amount');

        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $html = view('reports.outflows-pdf', [
                'rows' => $rows,
                'filters' => ['from' => $from, 'to' => $to, 'method' => $methodFilter],
                'totalAmount' => $totalAmount,
            ])->render();

            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            return $pdf->download('all-outflows-' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);

            return $this->downloadTable(
                $request,
                'all-outflows-' . now()->format('Y-m-d'),
                ['Date', 'Type', 'Category', 'Party', 'Method', 'Amount', 'Notes'],
                $rows,
                fn ($r) => [
                    $r['date'] ?? '',
                    $r['type'] ?? '',
                    $r['category'] ?? '',
                    $r['party'] ?? '',
                    $r['method'] ?? '',
                    (float) ($r['amount'] ?? 0),
                    $r['notes'] ?? '',
                ]
            );
        }

        return view('reports.outflows', [
            'rows' => $rows,
            'filters' => ['from' => $from, 'to' => $to, 'method' => $methodFilter],
            'summary' => [
                'total_count' => (int) $rows->count(),
                'total_amount' => $totalAmount,
                'by_type' => $rows->groupBy('type')->map(fn ($g) => (float) $g->sum('amount')),
            ],
        ]);
    }

    public function dailyLedger(Request $request)
    {
        $methodFilter = $this->normalizeMethodFilter($request, 'method');

        $includePendingCheques = $request->boolean('include_pending_cheques');

        $from = (string) ($request->input('from') ?: now()->toDateString());
        $to = (string) ($request->input('to') ?: $from);

        try {
            $start = Carbon::parse($from)->startOfDay();
        } catch (\Throwable $e) {
            $start = now()->startOfDay();
            $from = $start->toDateString();
        }
        try {
            $end = Carbon::parse($to)->startOfDay();
        } catch (\Throwable $e) {
            $end = $start->copy();
            $to = $end->toDateString();
        }
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
            [$from, $to] = [$to, $from];
        }

        $isBankMethod = fn (?string $method): bool => in_array($method ?? 'cash', ['bank_transfer', 'cheque'], true);

        $revenueEffectiveDateSql = $this->sqlEffectiveDate('paid_at', 'payment_method', 'cheque_date', 'payment_status');
        $revenueEffectiveDateSqlQualified = $this->sqlEffectiveDate('revenues.paid_at', 'revenues.payment_method', 'revenues.cheque_date', 'revenues.payment_status');
        $expenseEffectiveDateSqlQualified = $this->sqlEffectiveDate('expenses.expense_date', 'expenses.payment_method', 'expenses.cheque_date');

        $settings = app('settings');

        // Opening balance as of day before $start
        $bbfDate = $start->copy()->subDay();

        $obRecord = \App\Models\OpeningBalance::where('date', $bbfDate->toDateString())->first();
        $baseAmount = $obRecord ? (float) $obRecord->amount : 0.0;

        $openingBalanceCash = in_array($methodFilter, ['all', 'cash'], true) ? $baseAmount : 0.0;
        $openingBalanceBank = 0.0;
        $openingBalance = $openingBalanceCash + $openingBalanceBank;

        // Revenues in range
        $revQ = Revenue::query()
            ->leftJoin('students', 'students.id', '=', 'revenues.student_id')
            ->leftJoin('revenue_categories', 'revenue_categories.id', '=', 'revenues.revenue_category_id')
            ->whereRaw("{$revenueEffectiveDateSqlQualified} >= ? AND {$revenueEffectiveDateSqlQualified} <= ?", [$start->toDateString(), $end->toDateString()])
            ->orderByRaw("CASE WHEN revenues.payment_method = 'cheque' AND revenues.payment_status = 'pending' THEN COALESCE(revenues.cheque_date, revenues.paid_at) WHEN revenues.payment_method = 'cheque' THEN revenues.paid_at ELSE revenues.paid_at END")
            ->orderBy('revenues.id')
            ->select([
                'revenues.id',
                'revenues.bill_no',
                'revenues.paid_at',
                DB::raw("CASE WHEN revenues.payment_method = 'cheque' AND revenues.payment_status = 'pending' THEN COALESCE(revenues.cheque_date, revenues.paid_at) WHEN revenues.payment_method = 'cheque' THEN revenues.paid_at ELSE revenues.paid_at END as effective_at"),
                'revenues.amount',
                'revenues.notes',
                'revenues.cancel_reason',
                'revenues.payment_method',
                'revenues.payment_status',
                'revenues.payment_meta',
                'revenues.cheque_date',
                'revenues.confirmed_at',
                'students.name as student_name',
                'revenue_categories.name as category_name',
            ]);
        $revQ = $this->applyPaymentMethodFilter($revQ, 'revenues.payment_method', $methodFilter);
        $revQ = $this->applyPendingChequeFilter($revQ, 'revenues.payment_method', 'revenues.payment_status', $includePendingCheques);
        $revenues = $revQ->get()->map(function ($r) use ($isBankMethod, $includePendingCheques) {
            $pm = (string) ($r->payment_method ?? 'cash');
            $isBank = $isBankMethod($pm);
            $status = (string) ($r->payment_status ?? 'paid');

            $student = $r->student_name ?: '—';
            $category = $r->category_name ?: 'Income';
            $desc = $student !== '—' ? $student : $category;
            if (! empty($r->notes)) {
                $desc .= ' - ' . $r->notes;
            }
            if ($status === 'cancelled') {
                $cancelDetail = $r->cancel_reason ?: $r->notes;
                $desc = 'Cancelled' . (! empty($cancelDetail) ? ' - ' . $cancelDetail : '');
            }

            $meta = is_string($r->payment_meta) ? json_decode($r->payment_meta, true) : $r->payment_meta;
            if (! is_array($meta)) {
                $meta = [];
            }

            $methodLabel = $this->paymentMethodLabel($pm);
            if ($status === 'cancelled') {
                $methodLabel = 'Cancelled';
            } elseif ($pm === 'cheque') {
                if ($includePendingCheques && $status === 'pending') {
                    $methodLabel .= ' (Pending)';
                } elseif ($status === 'rejected') {
                    $methodLabel .= ' (Returned)';
                } else {
                    $methodLabel .= ' (Passed)';
                }
            }

            return [
                'date' => $r->effective_at ? Carbon::parse($r->effective_at) : ($r->paid_at ? Carbon::parse($r->paid_at) : null),
                'section' => 'revenue',
                'type' => 'Revenue',
                'ref' => $r->bill_no ?: '—',
                'student' => $student,
                'category' => $category,
                'description' => $desc,
                'method' => $methodLabel,
                'account' => $isBank ? 'bank' : 'cash',
                'status' => $status,
                'cheque_date' => $r->cheque_date ? Carbon::parse($r->cheque_date)->toDateString() : null,
                'meta' => $meta,
                'in' => (float) $r->amount,
                'out' => 0.0,
            ];
        });

        // Refunds (outflow) based on adjustment created_at within range
        $refundQ = DB::table('revenue_adjustments')
            ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
            ->leftJoin('students', 'students.id', '=', 'revenues.student_id')
            ->where('revenue_adjustments.type', 'refund')
            ->whereDate('revenue_adjustments.created_at', '>=', $start->toDateString())
            ->whereDate('revenue_adjustments.created_at', '<=', $end->toDateString())
            ->orderBy('revenue_adjustments.created_at')
            ->orderBy('revenue_adjustments.id')
            ->select([
                'revenue_adjustments.id',
                'revenue_adjustments.amount',
                'revenue_adjustments.reason',
                'revenue_adjustments.created_at',
                'revenues.bill_no',
                'revenues.payment_method',
                'revenues.payment_status',
                'revenues.payment_meta',
                'revenues.cheque_date',
                'students.name as student_name',
            ]);
        $refundQ = $this->applyPaymentMethodFilter($refundQ, 'revenues.payment_method', $methodFilter);
        $refundQ = $this->applyPendingChequeFilter($refundQ, 'revenues.payment_method', 'revenues.payment_status', $includePendingCheques);
        $refunds = collect($refundQ->get())->map(function ($r) use ($isBankMethod, $includePendingCheques) {
            $pm = (string) ($r->payment_method ?? 'cash');
            $isBank = $isBankMethod($pm);
            $status = (string) ($r->payment_status ?? 'paid');

            $meta = is_string($r->payment_meta) ? json_decode($r->payment_meta, true) : $r->payment_meta;
            if (! is_array($meta)) {
                $meta = [];
            }

            $methodLabel = $this->paymentMethodLabel($pm);
            if ($pm === 'cheque') {
                if ($includePendingCheques && $status === 'pending') {
                    $methodLabel .= ' (Pending)';
                } elseif ($status === 'rejected') {
                    $methodLabel .= ' (Returned)';
                } else {
                    $methodLabel .= ' (Passed)';
                }
            }

            $student = $r->student_name ?: '—';
            $desc = 'Refund';
            if (! empty($r->reason)) {
                $desc .= ' - ' . $r->reason;
            }
            if ($student !== '—') {
                $desc .= ' (' . $student . ')';
            }

            return [
                'date' => $r->created_at ? Carbon::parse($r->created_at) : null,
                'section' => 'expense',
                'type' => 'Refund',
                'ref' => $r->bill_no ?: '—',
                'student' => $student,
                'category' => 'Refund',
                'description' => $desc,
                'method' => $methodLabel,
                'account' => $isBank ? 'bank' : 'cash',
                'status' => $status,
                'cheque_date' => $r->cheque_date ? Carbon::parse($r->cheque_date)->toDateString() : null,
                'meta' => $meta,
                'in' => 0.0,
                'out' => (float) $r->amount,
            ];
        });

        // Expenses in range
        $expQ = Expense::query()
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->whereRaw("{$expenseEffectiveDateSqlQualified} >= ? AND {$expenseEffectiveDateSqlQualified} <= ?", [$start->toDateString(), $end->toDateString()])
            ->orderByRaw("CASE WHEN expenses.payment_method = 'cheque' THEN COALESCE(expenses.cheque_date, expenses.expense_date) ELSE expenses.expense_date END")
            ->orderBy('expenses.id')
            ->select([
                'expenses.id',
                'expenses.expense_date',
                DB::raw("CASE WHEN expenses.payment_method = 'cheque' THEN COALESCE(expenses.cheque_date, expenses.expense_date) ELSE expenses.expense_date END as effective_date"),
                'expenses.amount',
                'expenses.notes',
                'expenses.payment_method',
                'expenses.payment_meta',
                'expenses.cheque_date',
                'expense_categories.name as category_name',
            ]);
        $expQ = $this->applyPaymentMethodFilter($expQ, 'expenses.payment_method', $methodFilter);
        $expenses = $expQ->get()->map(function ($e) use ($isBankMethod) {
            $pm = (string) ($e->payment_method ?? 'cash');
            $isBank = $isBankMethod($pm);

            $meta = is_string($e->payment_meta) ? json_decode($e->payment_meta, true) : $e->payment_meta;
            if (! is_array($meta)) {
                $meta = [];
            }

            $category = $e->category_name ?: 'Expense';
            $desc = $category;
            if (! empty($e->notes)) {
                $desc .= ' - ' . $e->notes;
            }

            return [
                'date' => $e->effective_date ? Carbon::parse($e->effective_date) : ($e->expense_date ? Carbon::parse($e->expense_date) : null),
                'section' => 'expense',
                'type' => 'Expense',
                'ref' => '—',
                'student' => '—',
                'category' => $category,
                'description' => $desc,
                'method' => $this->paymentMethodLabel($pm),
                'account' => $isBank ? 'bank' : 'cash',
                'status' => null,
                'cheque_date' => $e->cheque_date ? Carbon::parse($e->cheque_date)->toDateString() : null,
                'meta' => $meta,
                'in' => 0.0,
                'out' => (float) $e->amount,
            ];
        });

        // Teacher salary payments in range
        $salQ = TeacherSalaryPayment::query()
            ->leftJoin('teachers', 'teachers.id', '=', 'teacher_salary_payments.teacher_id')
            ->whereDate('teacher_salary_payments.paid_at', '>=', $start->toDateString())
            ->whereDate('teacher_salary_payments.paid_at', '<=', $end->toDateString())
            ->orderBy('teacher_salary_payments.paid_at')
            ->orderBy('teacher_salary_payments.id')
            ->select([
                'teacher_salary_payments.id',
                'teacher_salary_payments.paid_at',
                'teacher_salary_payments.payment_month',
                'teacher_salary_payments.amount',
                'teacher_salary_payments.payment_method',
                'teacher_salary_payments.bank_name',
                'teacher_salary_payments.bank_branch',
                'teacher_salary_payments.bank_account_no',
                'teacher_salary_payments.notes',
                'teachers.name as teacher_name',
            ]);
        $salQ = $this->applyPaymentMethodFilter($salQ, 'teacher_salary_payments.payment_method', $methodFilter);
        $salaryExpenses = $salQ->get()->map(function ($s) use ($isBankMethod) {
            $pm = (string) ($s->payment_method ?? 'cash');
            $isBank = $isBankMethod($pm);

            $teacher = $s->teacher_name ?: '—';
            $desc = 'Salary — ' . $teacher;
            if (! empty($s->payment_month)) {
                try {
                    $desc .= ' | Paid: ' . Carbon::parse($s->payment_month . '-01')->format('M');
                } catch (\Throwable $e) {
                    $desc .= ' | Paid: ' . $s->payment_month;
                }
            }
            if (! empty($s->notes)) {
                $desc .= ' - ' . $s->notes;
            }

            $meta = [];
            if (! empty($s->bank_name) || ! empty($s->bank_branch) || ! empty($s->bank_account_no)) {
                $meta = [
                    'bank' => $s->bank_name,
                    'branch' => $s->bank_branch,
                    'account_no' => $s->bank_account_no,
                ];
            }

            return [
                'date' => $s->paid_at ? Carbon::parse($s->paid_at) : null,
                'section' => 'expense',
                'type' => 'Salary',
                'ref' => 'SAL-' . $s->id,
                'student' => '—',
                'category' => 'Salary Payment',
                'description' => $desc,
                'method' => $this->paymentMethodLabel($pm),
                'account' => $isBank ? 'bank' : 'cash',
                'status' => null,
                'cheque_date' => null,
                'meta' => $meta,
                'in' => 0.0,
                'out' => (float) $s->amount,
            ];
        });

        $allExpenses = $refunds->concat($expenses)->concat($salaryExpenses)->sortBy(function ($r) {
            return ($r['date'] instanceof Carbon) ? $r['date']->timestamp : 0;
        })->values();

        $totalIn = (float) $revenues->sum('in');
        $totalOut = (float) $allExpenses->sum('out');
        $closingBalance = $openingBalance + $totalIn - $totalOut;

        $totalInCash = (float) $revenues->where('account', 'cash')->sum('in');
        $totalInBank = (float) $revenues->where('account', 'bank')->sum('in');
        $totalOutCash = (float) $allExpenses->where('account', 'cash')->sum('out');
        $totalOutBank = (float) $allExpenses->where('account', 'bank')->sum('out');

        $closingBalanceCash = $openingBalanceCash + $totalInCash - $totalOutCash;
        $closingBalanceBank = $openingBalanceBank + $totalInBank - $totalOutBank;

        $filters = [
            'from' => $from,
            'to' => $to,
            'method' => $methodFilter,
            'include_pending_cheques' => $includePendingCheques,
        ];

        $school = [
            'name' => (string) $settings->get('school.name', config('app.name')),
            'logo' => (string) $settings->get('school.logo', ''),
            'phone' => (string) $settings->get('school.phone', ''),
            'address' => (string) $settings->get('school.address', ''),
        ];

        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $html = view('reports.daily-ledger-pdf', [
                'filters' => $filters,
                'school' => $school,
                'bbfDate' => $bbfDate,
                'openingBalance' => $openingBalance,
                'openingBalanceCash' => $openingBalanceCash,
                'openingBalanceBank' => $openingBalanceBank,
                'revenues' => $revenues,
                'expenses' => $allExpenses,
                'totalIn' => $totalIn,
                'totalOut' => $totalOut,
                'closingBalance' => $closingBalance,
                'closingBalanceCash' => $closingBalanceCash,
                'closingBalanceBank' => $closingBalanceBank,
            ])->render();

            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            return $pdf->download('daily-ledger-' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->boolean('excel') || $request->boolean('download') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);

            $rows = [];
            $rows[] = ['Section' => 'Meta', 'A' => 'Daily Ledger', 'B' => null, 'C' => null, 'D' => null, 'E' => null, 'F' => null, 'G' => null, 'H' => null, 'I' => null, 'J' => null];
            $rows[] = ['Section' => 'Meta', 'A' => 'Opening Balance As of', 'B' => $bbfDate->toDateString(), 'C' => (float) $openingBalance];
            $rows[] = ['Section' => 'Meta', 'A' => 'Opening Cash Balance', 'B' => null, 'C' => (float) $openingBalanceCash];
            $rows[] = ['Section' => 'Meta', 'A' => 'Opening Bank Balance', 'B' => null, 'C' => (float) $openingBalanceBank];

            foreach ($revenues as $r) {
                $rows[] = [
                    'Section' => 'Revenue',
                    'Date' => $r['date']?->format('Y-m-d'),
                    'Ref' => $r['ref'],
                    'Student' => $r['student'],
                    'Category' => $r['category'],
                    'Description' => $r['description'],
                    'Method' => $r['method'],
                    'Status' => $r['status'],
                    'Cheque Date' => $r['cheque_date'],
                    'Amount In' => (float) $r['in'],
                    'Amount Out' => 0.0,
                ];
            }
            foreach ($allExpenses as $r) {
                $rows[] = [
                    'Section' => 'Expense',
                    'Date' => $r['date']?->format('Y-m-d'),
                    'Ref' => $r['ref'],
                    'Student' => $r['student'],
                    'Category' => $r['category'],
                    'Description' => $r['description'],
                    'Method' => $r['method'],
                    'Status' => $r['status'],
                    'Cheque Date' => $r['cheque_date'],
                    'Amount In' => 0.0,
                    'Amount Out' => (float) $r['out'],
                ];
            }

            return $this->downloadTable(
                $request,
                'daily-ledger-' . now()->format('Y-m-d'),
                ['Section', 'Date', 'Ref', 'Student', 'Category', 'Description', 'Method', 'Status', 'Cheque Date', 'Amount In', 'Amount Out'],
                $rows,
                fn ($row) => [
                    $row['Section'] ?? null,
                    $row['Date'] ?? ($row['A'] ?? null),
                    $row['Ref'] ?? ($row['B'] ?? null),
                    $row['Student'] ?? null,
                    $row['Category'] ?? null,
                    $row['Description'] ?? ($row['C'] ?? null),
                    $row['Method'] ?? null,
                    $row['Status'] ?? null,
                    $row['Cheque Date'] ?? null,
                    $row['Amount In'] ?? null,
                    $row['Amount Out'] ?? null,
                ]
            );
        }

        return view('reports.daily-ledger', [
            'filters' => $filters,
            'bbfDate' => $bbfDate,
            'openingBalance' => $openingBalance,
            'openingBalanceCash' => $openingBalanceCash,
            'openingBalanceBank' => $openingBalanceBank,
            'revenues' => $revenues,
            'expenses' => $allExpenses,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'closingBalance' => $closingBalance,
            'closingBalanceCash' => $closingBalanceCash,
            'closingBalanceBank' => $closingBalanceBank,
        ]);
    }

    public function cashTransactions(Request $request)
    {
        $from = (string) ($request->input('from') ?: now()->toDateString());
        $to = (string) ($request->input('to') ?: now()->toDateString());

        $rows = collect();

        $revenues = Revenue::query()
            ->with(['category', 'student'])
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->where(function ($q) {
                $q->where('payment_method', 'cash')->orWhereNull('payment_method');
            })
            ->orderByDesc('paid_at')
            ->get();

        foreach ($revenues as $r) {
            $desc = $r->student?->name ?: ($r->category?->name ?: 'Income');
            if (! empty($r->notes)) {
                $desc .= ' - ' . $r->notes;
            }
            if (($r->payment_status ?? null) === 'cancelled') {
                $cancelDetail = $r->cancel_reason ?: $r->notes;
                $desc = 'Cancelled' . (! empty($cancelDetail) ? ' - ' . $cancelDetail : '');
            }
            $rows->push([
                'date' => $r->paid_at ? Carbon::parse($r->paid_at) : null,
                'type' => 'Income',
                'ref' => $r->bill_no ?: '—',
                'description' => $desc,
                'method' => (($r->payment_status ?? null) === 'cancelled') ? 'Cancelled' : $this->paymentMethodLabel($r->payment_method),
                'status' => (string) ($r->payment_status ?? ''),
                'in' => (float) $r->amount,
                'out' => 0.0,
            ]);
        }

        $refunds = DB::table('revenue_adjustments')
            ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
            ->where('revenue_adjustments.type', 'refund')
            ->whereDate('revenues.paid_at', '>=', $from)
            ->whereDate('revenues.paid_at', '<=', $to)
            ->where(function ($q) {
                $q->where('revenues.payment_method', 'cash')->orWhereNull('revenues.payment_method');
            })
            ->orderByDesc('revenues.paid_at')
            ->select([
                'revenues.paid_at as paid_at',
                'revenues.bill_no as bill_no',
                'revenues.notes as notes',
                'revenues.payment_method as payment_method',
                'revenue_adjustments.amount as amount',
            ])
            ->get();

        foreach ($refunds as $r) {
            $desc = 'Refund';
            if (! empty($r->notes)) {
                $desc .= ' - ' . $r->notes;
            }
            $rows->push([
                'date' => $r->paid_at ? Carbon::parse($r->paid_at) : null,
                'type' => 'Refund',
                'ref' => $r->bill_no ?: '—',
                'description' => $desc,
                'method' => $this->paymentMethodLabel($r->payment_method),
                'in' => 0.0,
                'out' => (float) $r->amount,
            ]);
        }

        $expenses = Expense::query()
            ->with(['category'])
            ->whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->where(function ($q) {
                $q->where('payment_method', 'cash')->orWhereNull('payment_method');
            })
            ->orderByDesc('expense_date')
            ->get();

        foreach ($expenses as $e) {
            $desc = $e->category?->name ?: 'Expense';
            if (! empty($e->notes)) {
                $desc .= ' - ' . $e->notes;
            }
            $rows->push([
                'date' => $e->expense_date ? Carbon::parse($e->expense_date) : null,
                'type' => 'Expense',
                'ref' => (string) $e->id,
                'description' => $desc,
                'method' => $this->paymentMethodLabel($e->payment_method),
                'in' => 0.0,
                'out' => (float) $e->amount,
            ]);
        }

        $rows = $rows
            ->sortByDesc(fn ($r) => $r['date']?->timestamp ?? 0)
            ->values();

        $totalIn = (float) $rows->sum('in');
        $totalOut = (float) $rows->sum('out');

        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $html = view('reports.transactions-pdf', [
                'title' => 'Cash Transactions',
                'account' => 'cash',
                'rows' => $rows,
                'filters' => ['from' => $from, 'to' => $to],
                'totalIn' => $totalIn,
                'totalOut' => $totalOut,
            ])->render();
            $pdf = Pdf::loadHTML($html)->setPaper('a4');
            return $pdf->download('cash-transactions-' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            return $this->downloadTable(
                $request,
                'cash-transactions-' . now()->format('Y-m-d'),
                ['Date', 'Type', 'Ref', 'Description', 'Method', 'Amount In', 'Amount Out'],
                $rows,
                fn ($r) => [
                    $r['date']?->format('Y-m-d'),
                    $r['type'],
                    $r['ref'],
                    $r['description'],
                    $r['method'],
                    (float) $r['in'],
                    (float) $r['out'],
                ]
            );
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 50;
        $paged = $rows->forPage($page, $perPage)->values();
        $paginator = new LengthAwarePaginator(
            $paged,
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('reports.transactions', [
            'title' => 'Cash Transactions',
            'account' => 'cash',
            'items' => $paginator,
            'filters' => ['from' => $from, 'to' => $to],
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
        ]);
    }

    public function bankTransactions(Request $request)
    {
        $from = (string) ($request->input('from') ?: now()->toDateString());
        $to = (string) ($request->input('to') ?: now()->toDateString());

        $includePendingCheques = $request->boolean('include_pending_cheques');

        $rows = collect();

        $revenueEffectiveDateSql = $this->sqlEffectiveDate('paid_at', 'payment_method', 'cheque_date', 'payment_status');
        $revenueEffectiveDateSqlQualified = $this->sqlEffectiveDate('revenues.paid_at', 'revenues.payment_method', 'revenues.cheque_date', 'revenues.payment_status');
        $expenseEffectiveDateSql = $this->sqlEffectiveDate('expense_date', 'payment_method', 'cheque_date');

        $revQ = Revenue::query()
            ->with(['category', 'student'])
            ->whereRaw("{$revenueEffectiveDateSql} >= ? AND {$revenueEffectiveDateSql} <= ?", [$from, $to])
            ->whereIn('payment_method', ['bank_transfer', 'cheque'])
            ->orderByRaw("CASE WHEN payment_method = 'cheque' AND payment_status = 'pending' THEN COALESCE(cheque_date, paid_at) WHEN payment_method = 'cheque' THEN paid_at ELSE paid_at END DESC");

        $revQ = $this->applyPendingChequeFilter($revQ, 'payment_method', 'payment_status', $includePendingCheques);
        $revenues = $revQ->get();

        foreach ($revenues as $r) {
            $desc = $r->student?->name ?: ($r->category?->name ?: 'Income');
            if (! empty($r->notes)) {
                $desc .= ' - ' . $r->notes;
            }
            if (($r->payment_status ?? null) === 'cancelled') {
                $cancelDetail = $r->cancel_reason ?: $r->notes;
                $desc = 'Cancelled' . (! empty($cancelDetail) ? ' - ' . $cancelDetail : '');
            }

            $effectiveDate = ($r->payment_method === 'cheque') ? ($r->cheque_date ?: $r->paid_at) : $r->paid_at;
            $rows->push([
                'date' => $effectiveDate ? Carbon::parse($effectiveDate) : null,
                'type' => 'Income',
                'ref' => $r->bill_no ?: '—',
                'description' => $desc,
                'method' => (($r->payment_status ?? null) === 'cancelled')
                    ? 'Cancelled'
                    : (($r->payment_method === 'cheque')
                    ? ($this->paymentMethodLabel($r->payment_method) . (($includePendingCheques && ($r->payment_status ?? null) === 'pending') ? ' (Pending)' : ((($r->payment_status ?? null) === 'rejected') ? ' (Returned)' : ' (Passed)')))
                    : $this->paymentMethodLabel($r->payment_method)),
                'status' => (string) ($r->payment_status ?? ''),
                'in' => (float) $r->amount,
                'out' => 0.0,
            ]);
        }

        $refundQ = DB::table('revenue_adjustments')
            ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
            ->where('revenue_adjustments.type', 'refund')
            ->whereRaw("{$revenueEffectiveDateSqlQualified} >= ? AND {$revenueEffectiveDateSqlQualified} <= ?", [$from, $to])
            ->whereIn('revenues.payment_method', ['bank_transfer', 'cheque'])
            ->orderByRaw("CASE WHEN revenues.payment_method = 'cheque' AND revenues.payment_status = 'pending' THEN COALESCE(revenues.cheque_date, revenues.paid_at) WHEN revenues.payment_method = 'cheque' THEN revenues.paid_at ELSE revenues.paid_at END DESC")
            ->select([
                'revenues.paid_at as paid_at',
                'revenues.cheque_date as cheque_date',
                'revenues.bill_no as bill_no',
                'revenues.notes as notes',
                'revenues.payment_method as payment_method',
                'revenues.payment_status as payment_status',
                'revenues.confirmed_at as confirmed_at',
                'revenue_adjustments.amount as amount',
            ]);

        $refundQ = $this->applyPendingChequeFilter($refundQ, 'revenues.payment_method', 'revenues.payment_status', $includePendingCheques);
        $refunds = $refundQ->get();

        foreach ($refunds as $r) {
            $desc = 'Refund';
            if (! empty($r->notes)) {
                $desc .= ' - ' . $r->notes;
            }

            $effectiveDate = ($r->payment_method === 'cheque') ? ($r->cheque_date ?: $r->paid_at) : $r->paid_at;
            $rows->push([
                'date' => $effectiveDate ? Carbon::parse($effectiveDate) : null,
                'type' => 'Refund',
                'ref' => $r->bill_no ?: '—',
                'description' => $desc,
                'method' => ($r->payment_method === 'cheque')
                    ? ($this->paymentMethodLabel($r->payment_method) . (($includePendingCheques && ($r->payment_status ?? null) === 'pending') ? ' (Pending)' : ((($r->payment_status ?? null) === 'rejected') ? ' (Returned)' : ' (Passed)')))
                    : $this->paymentMethodLabel($r->payment_method),
                'in' => 0.0,
                'out' => (float) $r->amount,
            ]);
        }

        $expenses = Expense::query()
            ->with(['category'])
            ->whereRaw("{$expenseEffectiveDateSql} >= ? AND {$expenseEffectiveDateSql} <= ?", [$from, $to])
            ->whereIn('payment_method', ['bank_transfer', 'cheque'])
            ->orderByRaw("CASE WHEN payment_method = 'cheque' THEN COALESCE(cheque_date, expense_date) ELSE expense_date END DESC")
            ->get();

        foreach ($expenses as $e) {
            $desc = $e->category?->name ?: 'Expense';
            if (! empty($e->notes)) {
                $desc .= ' - ' . $e->notes;
            }

            $effectiveDate = ($e->payment_method === 'cheque') ? ($e->cheque_date ?: $e->expense_date) : $e->expense_date;
            $rows->push([
                'date' => $effectiveDate ? Carbon::parse($effectiveDate) : null,
                'type' => 'Expense',
                'ref' => (string) $e->id,
                'description' => $desc,
                'method' => $this->paymentMethodLabel($e->payment_method),
                'in' => 0.0,
                'out' => (float) $e->amount,
            ]);
        }

        $rows = $rows
            ->sortByDesc(fn ($r) => $r['date']?->timestamp ?? 0)
            ->values();

        $totalIn = (float) $rows->sum('in');
        $totalOut = (float) $rows->sum('out');

        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $html = view('reports.transactions-pdf', [
                'title' => 'Bank Transactions',
                'account' => 'bank',
                'rows' => $rows,
                'filters' => ['from' => $from, 'to' => $to, 'include_pending_cheques' => $includePendingCheques],
                'totalIn' => $totalIn,
                'totalOut' => $totalOut,
            ])->render();
            $pdf = Pdf::loadHTML($html)->setPaper('a4');
            return $pdf->download('bank-transactions-' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            return $this->downloadTable(
                $request,
                'bank-transactions-' . now()->format('Y-m-d'),
                ['Date', 'Type', 'Ref', 'Description', 'Method', 'Amount In', 'Amount Out'],
                $rows,
                fn ($r) => [
                    $r['date']?->format('Y-m-d'),
                    $r['type'],
                    $r['ref'],
                    $r['description'],
                    $r['method'],
                    (float) $r['in'],
                    (float) $r['out'],
                ]
            );
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 50;
        $paged = $rows->forPage($page, $perPage)->values();
        $paginator = new LengthAwarePaginator(
            $paged,
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('reports.transactions', [
            'title' => 'Bank Transactions',
            'account' => 'bank',
            'items' => $paginator,
            'filters' => ['from' => $from, 'to' => $to, 'include_pending_cheques' => $includePendingCheques],
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
        ]);
    }

    public function chequeHistory(Request $request)
    {
        $from = (string) ($request->input('from') ?: now()->startOfMonth()->toDateString());
        $to = (string) ($request->input('to') ?: now()->toDateString());

        $includePendingCheques = $request->has('include_pending_cheques')
            ? $request->boolean('include_pending_cheques')
            : true;

        $status = (string) ($request->input('status') ?: 'all');
        if (! in_array($status, ['passed', 'pending', 'all'], true)) {
            $status = 'all';
        }

        $type = (string) ($request->input('type') ?: 'all');
        if (! in_array($type, ['all', 'income', 'expense'], true)) {
            $type = 'all';
        }

        $rows = collect();

        $revenueChequeDateSql = "DATE(COALESCE(cheque_date, paid_at))";
        $expenseChequeDateSql = "DATE(COALESCE(cheque_date, expense_date))";

        if ($type !== 'expense') {
            $revQ = Revenue::query()
                ->with(['category', 'student'])
                ->where('payment_method', 'cheque')
                ->whereRaw("{$revenueChequeDateSql} >= ? AND {$revenueChequeDateSql} <= ?", [$from, $to])
                ->orderByRaw("COALESCE(cheque_date, paid_at) DESC")
                ->orderByDesc('id');

            if ($status === 'pending') {
                $revQ->where('payment_status', 'pending');
            } elseif ($status === 'passed') {
                $revQ->where(function ($q) {
                    $q->whereNull('payment_status')->orWhere('payment_status', '!=', 'pending');
                });
            }

            $revQ = $this->applyPendingChequeFilter($revQ, 'payment_method', 'payment_status', $includePendingCheques || $status === 'pending' || $status === 'all');
            $revenues = $revQ->get();

            foreach ($revenues as $r) {
                $meta = is_string($r->payment_meta) ? json_decode($r->payment_meta, true) : $r->payment_meta;
                if (! is_array($meta)) {
                    $meta = [];
                }

                $chequeNo = (string) ($meta['cheque_no'] ?? $meta['cheque_number'] ?? '');
                $bankName = (string) ($meta['bank'] ?? $meta['bank_name'] ?? '');

                $effectiveDate = $r->cheque_date ?: $r->paid_at;
                $desc = $r->student?->name ?: ($r->category?->name ?: 'Income');
                if (! empty($r->notes)) {
                    $desc .= ' - ' . $r->notes;
                }

                $rows->push([
                    'date' => $effectiveDate ? Carbon::parse($effectiveDate) : null,
                    'direction' => 'In',
                    'ref' => $r->bill_no ?: '—',
                    'party' => $r->student?->name ?: '—',
                    'description' => $desc,
                    'cheque_no' => $chequeNo,
                    'bank' => $bankName,
                    'status' => (string) ($r->payment_status ?? 'paid'),
                    'cheque_date' => $r->cheque_date ? Carbon::parse($r->cheque_date)->toDateString() : null,
                    'passed_date' => (($r->payment_status ?? null) === 'pending') ? null : ($r->paid_at ? Carbon::parse($r->paid_at)->toDateString() : null),
                    'in' => (float) $r->amount,
                    'out' => 0.0,
                ]);
            }
        }

        if ($type !== 'income') {
            $expQ = Expense::query()
                ->with(['category'])
                ->where('payment_method', 'cheque')
                ->whereRaw("{$expenseChequeDateSql} >= ? AND {$expenseChequeDateSql} <= ?", [$from, $to])
                ->orderByRaw("COALESCE(cheque_date, expense_date) DESC")
                ->orderByDesc('id');

            $expenses = $expQ->get();

            foreach ($expenses as $e) {
                $meta = is_string($e->payment_meta) ? json_decode($e->payment_meta, true) : $e->payment_meta;
                if (! is_array($meta)) {
                    $meta = [];
                }

                $chequeNo = (string) ($meta['cheque_no'] ?? $meta['cheque_number'] ?? '');
                $bankName = (string) ($meta['bank'] ?? $meta['bank_name'] ?? '');

                $effectiveDate = $e->cheque_date ?: $e->expense_date;
                $desc = $e->category?->name ?: 'Expense';
                if (! empty($e->notes)) {
                    $desc .= ' - ' . $e->notes;
                }

                $rows->push([
                    'date' => $effectiveDate ? Carbon::parse($effectiveDate) : null,
                    'direction' => 'Out',
                    'ref' => (string) $e->id,
                    'party' => '—',
                    'description' => $desc,
                    'cheque_no' => $chequeNo,
                    'bank' => $bankName,
                    'status' => '—',
                    'cheque_date' => $e->cheque_date ? Carbon::parse($e->cheque_date)->toDateString() : null,
                    'passed_date' => null,
                    'in' => 0.0,
                    'out' => (float) $e->amount,
                ]);
            }
        }

        $rows = $rows
            ->sortByDesc(fn ($r) => $r['date']?->timestamp ?? 0)
            ->values();

        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $html = view('reports.cheque-history-pdf', [
                'title' => 'Cheque History',
                'rows' => $rows,
                'filters' => [
                    'from' => $from,
                    'to' => $to,
                    'status' => $status,
                    'type' => $type,
                    'include_pending_cheques' => $includePendingCheques,
                ],
            ])->render();
            $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
            return $pdf->download('cheque-history-' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            return $this->downloadTable(
                $request,
                'cheque-history-' . now()->format('Y-m-d'),
                ['Date', 'Direction', 'Ref', 'Party', 'Description', 'Cheque No', 'Bank', 'Status', 'Cheque Date', 'Passed Date', 'In', 'Out'],
                $rows,
                fn ($r) => [
                    $r['date']?->format('Y-m-d'),
                    $r['direction'],
                    $r['ref'],
                    $r['party'],
                    $r['description'],
                    $r['cheque_no'],
                    $r['bank'],
                    $r['status'],
                    $r['cheque_date'],
                    $r['passed_date'],
                    (float) $r['in'],
                    (float) $r['out'],
                ]
            );
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 50;
        $paged = $rows->forPage($page, $perPage)->values();
        $paginator = new LengthAwarePaginator(
            $paged,
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('reports.cheque-history', [
            'title' => 'Cheque History',
            'items' => $paginator,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'status' => $status,
                'type' => $type,
                'include_pending_cheques' => $includePendingCheques,
            ],
        ]);
    }

    public function teacherEpf(Request $request)
    {
        return $this->salaryContributionReport($request, 'employee_epf');
    }

    public function teacherEtf(Request $request)
    {
        // Note: ETF is an employer (company) contribution (not deducted from teacher salary)
        return $this->salaryContributionReport($request, 'employer_etf');
    }

    public function companyEpf(Request $request)
    {
        return $this->salaryContributionReport($request, 'employer_epf');
    }

    public function epfEtfTotals(Request $request)
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
        $totals = [
            'employee_epf' => 0.0,
            'employer_epf' => 0.0,
            'employer_etf' => 0.0,
        ];

        $teacherTotals = [];

        $monthTotals = [];
        foreach ($allRows as $p) {
            $employee = $this->contributionAmount($p, 'employee_epf');
            $companyEpf = $this->contributionAmount($p, 'employer_epf');
            $companyEtf = $this->contributionAmount($p, 'employer_etf');

            $totals['employee_epf'] += $employee;
            $totals['employer_epf'] += $companyEpf;
            $totals['employer_etf'] += $companyEtf;

            $tid = (int) $p->teacher_id;
            if (! isset($teacherTotals[$tid])) {
                $teacherTotals[$tid] = [
                    'teacher' => $p->teacher,
                    'payments' => 0,
                    'employee_epf' => 0.0,
                    'employer_epf' => 0.0,
                    'employer_etf' => 0.0,
                    'total' => 0.0,
                ];
            }
            $teacherTotals[$tid]['payments']++;
            $teacherTotals[$tid]['employee_epf'] += $employee;
            $teacherTotals[$tid]['employer_epf'] += $companyEpf;
            $teacherTotals[$tid]['employer_etf'] += $companyEtf;
            $teacherTotals[$tid]['total'] += ($employee + $companyEpf + $companyEtf);

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
                            'employee_epf' => 0.0,
                            'employer_epf' => 0.0,
                            'employer_etf' => 0.0,
                        ];
                    }
                    $monthTotals[$monthKey]['payments']++;
                    $monthTotals[$monthKey]['employee_epf'] += $employee;
                    $monthTotals[$monthKey]['employer_epf'] += $companyEpf;
                    $monthTotals[$monthKey]['employer_etf'] += $companyEtf;
                }
            }
        }
        if ($groupByMonth) {
            ksort($monthTotals);
        } else {
            $monthTotals = [];
        }

        $teacherTotals = array_values($teacherTotals);
        usort($teacherTotals, fn ($a, $b) => ($b['total'] <=> $a['total']) ?: strcmp((string) ($a['teacher']?->name ?? ''), (string) ($b['teacher']?->name ?? '')));

        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $html = view('reports.epf-etf-totals-pdf', [
                'filters' => $filters,
                'totals' => [
                    'employee_epf' => round($totals['employee_epf'], 2),
                    'employer_epf' => round($totals['employer_epf'], 2),
                    'employer_etf' => round($totals['employer_etf'], 2),
                    'grand_total' => round($totals['employee_epf'] + $totals['employer_epf'] + $totals['employer_etf'], 2),
                ],
                'groupByMonth' => $groupByMonth,
                'monthTotals' => array_values($monthTotals),
                'teacherTotals' => $teacherTotals,
            ])->render();

            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4', 'landscape')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            return $pdf->download('epf-etf-totals-report-' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);

            $exportRows = [];
            foreach ($teacherTotals as $r) {
                $exportRows[] = [
                    'group' => 'Teacher',
                    'key' => (string) ($r['teacher']?->name ?? '—'),
                    'payments' => (int) ($r['payments'] ?? 0),
                    'employee_epf' => (float) ($r['employee_epf'] ?? 0.0),
                    'employer_epf' => (float) ($r['employer_epf'] ?? 0.0),
                    'employer_etf' => (float) ($r['employer_etf'] ?? 0.0),
                    'total' => (float) ($r['total'] ?? 0.0),
                ];
            }

            foreach (array_values($monthTotals) as $r) {
                $exportRows[] = [
                    'group' => 'Month',
                    'key' => (string) ($r['label'] ?? $r['month'] ?? '—'),
                    'payments' => (int) ($r['payments'] ?? 0),
                    'employee_epf' => (float) ($r['employee_epf'] ?? 0.0),
                    'employer_epf' => (float) ($r['employer_epf'] ?? 0.0),
                    'employer_etf' => (float) ($r['employer_etf'] ?? 0.0),
                    'total' => (float) (($r['employee_epf'] ?? 0.0) + ($r['employer_epf'] ?? 0.0) + ($r['employer_etf'] ?? 0.0)),
                ];
            }

            return $this->downloadTable(
                $request,
                'epf-etf-totals-' . now()->format('Y-m-d'),
                ['Group', 'Teacher/Month', 'Payments', 'Employee EPF', 'Employer EPF', 'Employer ETF', 'Total'],
                $exportRows,
                fn ($r) => [
                    $r['group'],
                    $r['key'],
                    $r['payments'],
                    $r['employee_epf'],
                    $r['employer_epf'],
                    $r['employer_etf'],
                    $r['total'],
                ]
            );
        }

        return view('reports.epf-etf-totals', [
            'items' => $allRows,
            'filters' => $filters,
            'teachers' => $teachers,
            'totals' => [
                'employee_epf' => round($totals['employee_epf'], 2),
                'employer_epf' => round($totals['employer_epf'], 2),
                'employer_etf' => round($totals['employer_etf'], 2),
                'grand_total' => round($totals['employee_epf'] + $totals['employer_epf'] + $totals['employer_etf'], 2),
            ],
            'groupByMonth' => $groupByMonth,
            'monthTotals' => array_values($monthTotals),
            'teacherTotals' => $teacherTotals,
        ]);
    }

    public function seminarsCollection(Request $request)
    {
        // Aggregate seminar payments
        $query = DB::table('seminar_students')
            ->join('seminars', 'seminars.id', '=', 'seminar_students.seminar_id')
            ->join('students', 'students.id', '=', 'seminar_students.student_id');

        if ($request->filled('from')) {
            $query->whereDate('seminars.date', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('seminars.date', '<=', $request->string('to'));
        }
        if ($request->filled('class_room_id')) {
            $query->where('seminars.class_room_id', $request->string('class_room_id'));
        }
        if ($request->filled('visiting_teacher_id')) {
            $query->where('seminars.visiting_teacher_id', $request->string('visiting_teacher_id'));
        }
        if ($request->filled('q')) {
            $query->where('seminars.name', 'like', '%' . $request->string('q') . '%');
        }

        // Sum teacher payout actually paid for seminars
        $teacherPaidSub = DB::table('seminar_teacher_payments')
            ->selectRaw('seminar_id, SUM(amount) as teacher_paid')
            ->groupBy('seminar_id');

        $base = $query
            ->leftJoinSub($teacherPaidSub, 'spaid', 'spaid.seminar_id', '=', 'seminars.id')
            ->selectRaw(
                'seminars.id as seminar_id, seminars.name as seminar_name, seminars.date as date,'.
                ' COUNT(seminar_students.id) as total,'.
                ' SUM(CASE WHEN seminar_students.paid = 1 THEN 1 ELSE 0 END) as paid_count,'.
                ' SUM(COALESCE(seminar_students.amount, seminars.fee_per_student)) as expected,'.
                ' SUM(CASE WHEN seminar_students.paid = 1 THEN COALESCE(seminar_students.amount, seminars.fee_per_student) ELSE 0 END) as collected,'.
                ' (SUM(COALESCE(seminar_students.amount, seminars.fee_per_student)) - SUM(CASE WHEN seminar_students.paid = 1 THEN COALESCE(seminar_students.amount, seminars.fee_per_student) ELSE 0 END)) as due_amount,'.
                ' seminars.teacher_payment as teacher_payment,'.
                ' COALESCE(spaid.teacher_paid, 0) as teacher_paid,'.
                ' (seminars.teacher_payment - COALESCE(spaid.teacher_paid, 0)) as teacher_due,'.
                ' (SUM(CASE WHEN seminar_students.paid = 1 THEN COALESCE(seminar_students.amount, seminars.fee_per_student) ELSE 0 END) - seminars.teacher_payment) as net_margin'
            )
            ->groupBy('seminars.id', 'seminars.name', 'seminars.date', 'seminars.fee_per_student', 'seminars.teacher_payment', 'spaid.teacher_paid')
            ->orderByDesc('seminars.date');

        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $all = (clone $base)->get();
            $html = view('reports.seminars-collection-pdf', [
                'rows' => $all,
                'filters' => $request->only(['from','to','class_room_id','visiting_teacher_id','q']),
            ])->render();
            $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
            return $pdf->download('seminars-collection-' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            $all = (clone $base)->get();
            return $this->downloadTable(
                $request,
                'seminars-collection-' . now()->format('Y-m-d'),
                ['Date', 'Seminar', 'Students', 'Paid', 'Expected', 'Collected', 'Due', 'Teacher Payment', 'Teacher Paid', 'Teacher Due', 'Net Margin'],
                $all,
                fn ($r) => [
                    (string) $r->date,
                    (string) $r->seminar_name,
                    (int) $r->total,
                    (int) $r->paid_count,
                    (float) $r->expected,
                    (float) $r->collected,
                    (float) $r->due_amount,
                    (float) $r->teacher_payment,
                    (float) $r->teacher_paid,
                    (float) $r->teacher_due,
                    (float) $r->net_margin,
                ]
            );
        }

        $rows = (clone $base)
            ->paginate(25)
            ->withQueryString();

        return view('reports.seminars-collection', [
            'rows' => $rows,
            'filters' => $request->only(['from','to','class_room_id','visiting_teacher_id','q']),
            'classRooms' => \App\Models\ClassRoom::query()->orderBy('name')->get(),
            'visitingTeachers' => \App\Models\VisitingTeacher::query()->orderBy('name')->get(),
        ]);
    }

    public function extraClassesCollection(Request $request)
    {
        // Aggregate extra class payments
        $query = DB::table('extra_class_students')
            ->join('extra_classes', 'extra_classes.id', '=', 'extra_class_students.extra_class_id')
            ->join('students', 'students.id', '=', 'extra_class_students.student_id');

        if ($request->filled('from')) {
            $query->whereDate('extra_classes.date', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('extra_classes.date', '<=', $request->string('to'));
        }
        if ($request->filled('class_room_id')) {
            $query->where('extra_classes.class_room_id', $request->string('class_room_id'));
        }
        if ($request->filled('visiting_teacher_id')) {
            $query->where('extra_classes.visiting_teacher_id', $request->string('visiting_teacher_id'));
        }
        if ($request->filled('type')) {
            $query->where('extra_classes.payment_type', $request->string('type'));
        }
        if ($request->filled('q')) {
            $query->where('extra_classes.name', 'like', '%' . $request->string('q') . '%');
        }

        $base = $query
            ->selectRaw(
                'extra_classes.id as class_id, extra_classes.name as class_name, extra_classes.date as date, extra_classes.payment_type as type,'.
                ' COUNT(extra_class_students.id) as total,'.
                ' SUM(CASE WHEN extra_class_students.paid = 1 THEN 1 ELSE 0 END) as paid_count,'.
                ' SUM(COALESCE(extra_class_students.amount, extra_classes.fee)) as expected,'.
                ' SUM(CASE WHEN extra_class_students.paid = 1 THEN COALESCE(extra_class_students.amount, extra_classes.fee) ELSE 0 END) as collected,'.
                ' (SUM(COALESCE(extra_class_students.amount, extra_classes.fee)) - SUM(CASE WHEN extra_class_students.paid = 1 THEN COALESCE(extra_class_students.amount, extra_classes.fee) ELSE 0 END)) as due_amount,'.
                ' COALESCE(extra_classes.teacher_payment, 0) as teacher_payment,'.
                ' (SUM(CASE WHEN extra_class_students.paid = 1 THEN COALESCE(extra_class_students.amount, extra_classes.fee) ELSE 0 END) - COALESCE(extra_classes.teacher_payment, 0)) as net_margin'
            )
            ->groupBy('extra_classes.id', 'extra_classes.name', 'extra_classes.date', 'extra_classes.payment_type', 'extra_classes.fee', 'extra_classes.teacher_payment')
            ->orderByDesc('extra_classes.date');

        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $all = (clone $base)->get();
            $html = view('reports.extra-classes-collection-pdf', [
                'rows' => $all,
                'filters' => $request->only(['from','to','class_room_id','visiting_teacher_id','type','q']),
            ])->render();
            $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
            return $pdf->download('extra-classes-collection-' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            $all = (clone $base)->get();
            return $this->downloadTable(
                $request,
                'extra-classes-collection-' . now()->format('Y-m-d'),
                ['Date', 'Class', 'Type', 'Students', 'Paid', 'Expected', 'Collected', 'Due', 'Teacher Payment', 'Net Margin'],
                $all,
                fn ($r) => [
                    (string) $r->date,
                    (string) $r->class_name,
                    (string) $r->type,
                    (int) $r->total,
                    (int) $r->paid_count,
                    (float) $r->expected,
                    (float) $r->collected,
                    (float) $r->due_amount,
                    (float) $r->teacher_payment,
                    (float) $r->net_margin,
                ]
            );
        }

        $rows = (clone $base)
            ->paginate(25)
            ->withQueryString();

        return view('reports.extra-classes-collection', [
            'rows' => $rows,
            'filters' => $request->only(['from','to','class_room_id','visiting_teacher_id','type','q']),
            'classRooms' => \App\Models\ClassRoom::query()->orderBy('name')->get(),
            'visitingTeachers' => \App\Models\VisitingTeacher::query()->orderBy('name')->get(),
        ]);
    }

    public function students(Request $request)
    {
        $query = Student::query()->with(['classRoom']);

        if ($request->filled('class_room_id')) {
            $query->where('class_room_id', $request->input('class_room_id'));
        }

        // Handle PDF Download
        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $rows = $query->orderBy('name')->get();
            
            $html = view('reports.students-pdf', [
                'items' => $rows,
                'filters' => [
                    'class_room_id' => $request->input('class_room_id'),
                ],
                'classRooms' => ClassRoom::query()->orderBy('name')->get(),
            ])->render();

            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4', 'landscape')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            return $pdf->download('students-report-' . now()->format('Y-m-d') . '.pdf');
        }

        // Handle CSV/XLSX Download
        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            $rows = $query->orderBy('name')->get();

            return $this->downloadTable(
                $request,
                'students-report',
                ['Admission No', 'Name', 'Phone', 'Grade/Class', 'Joined Date', 'Status'],
                $rows,
                function ($row) {
                    return [
                        $row->admission_number ?? $row->id,
                        $row->name,
                        $row->phone,
                        $row->classRoom?->name,
                        optional($row->joining_date)->format('Y-m-d'),
                        $row->active ? 'Active' : 'Inactive',
                    ];
                }
            );
        }

        return view('reports.students', [
            'items' => $query->orderBy('name')->paginate(20)->withQueryString(),
            'classRooms' => ClassRoom::query()->orderBy('name')->get(),
            'filters' => [
                'class_room_id' => $request->input('class_room_id'),
            ],
        ]);
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
            ->map(fn (Student $s) => $s->monthlyFeeCategoryId())
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
            $catId = $student->monthlyFeeCategoryId();
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

        // CSV/XLSX Download
        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            return $this->downloadTable(
                $request,
                'student-due-report',
                ['Admission No', 'Student', 'Class', 'Monthly Fee', 'Months Due', 'Expected', 'Paid', 'Due'],
                $computed,
                function ($row) {
                    /** @var \App\Models\Student $s */
                    $s = $row['student'];
                    return [
                        $s->admission_number,
                        $s->name,
                        $row['class_room']?->name,
                        (float) $row['monthly_fee'],
                        (int) $row['months_due'],
                        (float) $row['expected'],
                        (float) $row['paid'],
                        (float) $row['due'],
                    ];
                }
            );
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

        $groupSql = $group === 'month'
            ? "DATE_FORMAT(paid_at, '%Y-%m')"
            : "DATE_FORMAT(paid_at, '%Y-%m-%d')";

        $refundsSub = DB::table('revenue_adjustments')
            ->selectRaw('revenue_id, SUM(amount) as refund_amount')
            ->where('type', 'refund')
            ->groupBy('revenue_id');

        $rows = (clone $query)
            ->leftJoinSub($refundsSub, 'refunds', 'refunds.revenue_id', '=', 'revenues.id')
            ->select([
                DB::raw($groupSql . ' as grp'),
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

        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            return $this->downloadTable(
                $request,
                'fee-collection-summary',
                [ucfirst((string) $group), 'Payments', 'Total'],
                $rows,
                fn ($r) => [$r->grp, (int) $r->payments, (float) $r->total_amount]
            );
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

        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            return $this->downloadTable(
                $request,
                'fee-collection-by-class',
                ['Class', 'Payments', 'Total'],
                $rows,
                fn ($r) => [$r->class_name, (int) $r->payments, (float) $r->total_amount]
            );
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

        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            return $this->downloadTable(
                $request,
                'fee-collection-by-category',
                ['Category', 'Payments', 'Total'],
                $rows,
                fn ($r) => [$r->category_name, (int) $r->payments, (float) $r->total_amount]
            );
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
                ->whereNotNull('revenue_categories.interval_months')
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

        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            return $this->downloadTable(
                $request,
                'collected-vs-expected',
                ['Month', 'Expected', 'Collected', 'Due'],
                $months,
                fn ($r) => [$r['month'], (float) $r['expected'], (float) $r['collected'], (float) $r['due']]
            );
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

        /** @var \App\Models\Student[] $students */
        $students = $query->orderBy('name')->get()->all();
        $buckets = [
            '0-30' => ['label' => '0-30 days', 'students' => 0, 'due' => 0.0],
            '31-60' => ['label' => '31-60 days', 'students' => 0, 'due' => 0.0],
            '61-90' => ['label' => '61-90 days', 'students' => 0, 'due' => 0.0],
            '90+' => ['label' => '90+ days', 'students' => 0, 'due' => 0.0],
        ];

        $rows = [];
        foreach ($students as $s) {
            /** @var \App\Models\Student $s */
            $due = (float) ($s->due_amount ?? 0);
            if ($due <= 0) {
                continue;
            }

            $monthlyFee = (float) ($s->monthly_fee ?? 0);
            $unpaidMonths = $monthlyFee > 0
                ? (int) ceil($due / $monthlyFee)
                : 0;

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

        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            return $this->downloadTable(
                $request,
                'student-due-aging',
                ['Admission No', 'Student', 'Class', 'Bucket', 'Unpaid Months', 'Due'],
                $rows,
                function ($r) {
                    /** @var \App\Models\Student $s */
                    $s = $r['student'];
                    return [
                        $s->admission_number,
                        $s->name,
                        $r['class_room']?->name,
                        $r['bucket'],
                        (int) $r['unpaid_months'],
                        (float) $r['due'],
                    ];
                }
            );
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

        /** @var \App\Models\Student[] $students */
        $students = $query->orderBy('name')->get()->all();
        $computed = [];
        foreach ($students as $student) {
            /** @var \App\Models\Student $student */
            $due = (float) ($student->due_amount ?? 0);
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

        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            return $this->downloadTable(
                $request,
                'student-top-due',
                ['Admission No', 'Student', 'Class', 'Phone', 'WhatsApp', 'Due'],
                $rows,
                function ($r) {
                    /** @var \App\Models\Student $s */
                    $s = $r['student'];
                    return [
                        $s->admission_number,
                        $s->name,
                        $r['class_room']?->name,
                        $s->phone,
                        $s->whatsapp_number,
                        (float) $r['due'],
                    ];
                }
            );
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

        // Default monthly range: start from day 1 if no explicit range provided
        $defaultFrom = now()->startOfMonth()->toDateString();
        $from = (string) ($request->string('from') ?: $defaultFrom);
        $to = (string) ($request->string('to') ?: '');

        // Ensure date filters are applied consistently
        $query->whereDate('created_at', '>=', $from);
        if ($to !== '') {
            $query->whereDate('created_at', '<=', $to);
        }

        $query->orderByDesc('created_at');

        $filters = [
            'category_id' => $request->string('category_id'),
            'class_room_id' => $request->string('class_room_id'),
            'from' => $from,
            'to' => $to !== '' ? $to : null,
            'q' => $request->string('q'),
        ];
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

        // CSV/XLSX download
        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            $rows = $query->get();

            $base = ($type === 'refund' ? 'refund' : 'waiver') . '-report';
            return $this->downloadTable(
                $request,
                $base,
                ['Date', 'Bill No', 'Student', 'Admission No', 'Class', 'Category', 'Amount', 'Reason', 'By'],
                $rows,
                fn ($a) => [
                    optional($a->created_at)->format('Y-m-d'),
                    $a->revenue?->bill_no,
                    $a->student?->name,
                    $a->student?->admission_number,
                    $a->student?->classRoom?->name,
                    $a->revenue?->category?->name,
                    (float) $a->amount,
                    $a->reason,
                    $a->creator?->name,
                ]
            );
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

    private function salaryContributionReport(Request $request, string $kind)
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
            $amt = $this->contributionAmount($p, $kind);
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
        [$viewName, $pdfViewName, $routeName, $typeLabel] = $this->contributionViewMeta($kind);
        $filePrefix = match ($kind) {
            'employee_epf' => 'teacher-epf',
            'employer_epf' => 'company-epf',
            'employer_etf' => 'company-etf',
            default => strtolower($typeLabel),
        };

        if ($request->boolean('pdf')) {
            $this->authorizeDownload($request);
            $html = view($pdfViewName, [
                'items' => $allRows,
                'totalAmount' => $totalType,
                'filters' => $filters,
                'teachers' => $teachers,
                'teacherSummary' => $teacherSummary,
                'groupByMonth' => $groupByMonth,
                'monthTotals' => array_values($monthTotals),
                'type' => $typeLabel,
                'kind' => $kind,
            ])->render();

            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            return $pdf->download($filePrefix . '-report-' . now()->format('Y-m-d') . '.pdf');
        }

        // CSV/XLSX Download
        if ($request->boolean('download') || $request->boolean('excel') || strtolower((string) $request->query('format')) === 'xlsx') {
            $this->authorizeDownload($request);
            return $this->downloadTable(
                $request,
                $filePrefix . '-report',
                ['Receipt No', 'Payment Month', 'Paid At', 'Teacher', 'Basic Salary', $typeLabel],
                $allRows,
                fn ($row) => [
                    $row->receipt_number,
                    $row->payment_month,
                    optional($row->paid_at)->format('Y-m-d'),
                    $row->teacher?->name,
                    (float) $row->base_salary,
                    (float) $this->contributionAmount($row, $kind),
                ]
            );
        }

        $items = $query->paginate(20)->withQueryString();
        $items->getCollection()->transform(function ($p) use ($kind) {
            $p->deduction_amount = $this->contributionAmount($p, $kind);
            return $p;
        });

        return view($viewName, [
            'items' => $items,
            'filters' => $filters,
            'teachers' => $teachers,
            'totalAmount' => $totalType,
            'teacherSummary' => $teacherSummary,
            'groupByMonth' => $groupByMonth,
            'monthTotals' => array_values($monthTotals),
            'type' => $typeLabel,
            'routeName' => $routeName,
            'kind' => $kind,
        ]);
    }

    /**
     * @return array{0:string,1:string,2:string,3:string}
     */
    private function contributionViewMeta(string $kind): array
    {
        return match ($kind) {
            'employee_epf' => ['reports.teacher-epf', 'reports.teacher-epf-pdf', 'reports.teacher_epf', 'EPF'],
            'employer_epf' => ['reports.company-epf', 'reports.company-epf-pdf', 'reports.company_epf', 'EPF'],
            'employer_etf' => ['reports.teacher-etf', 'reports.teacher-etf-pdf', 'reports.teacher_etf', 'ETF'],
            default => ['reports.teacher-epf', 'reports.teacher-epf-pdf', 'reports.teacher_epf', 'EPF'],
        };
    }

    private function contributionAmount(TeacherSalaryPayment $payment, string $kind): float
    {
        if ($kind === 'employee_epf') {
            if ($payment->employee_epf_amount !== null) {
                return round((float) $payment->employee_epf_amount, 2);
            }
            return $this->deductionAmount($payment, 'EPF');
        }

        if ($kind === 'employer_etf') {
            if ($payment->employer_etf_amount !== null) {
                return round((float) $payment->employer_etf_amount, 2);
            }
            // Back-compat: older records stored ETF as a deduction
            return $this->deductionAmount($payment, 'ETF');
        }

        if ($kind === 'employer_epf') {
            if ($payment->employer_epf_amount !== null) {
                return round((float) $payment->employer_epf_amount, 2);
            }
            return 0.0;
        }

        return 0.0;
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
