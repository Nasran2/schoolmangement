<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
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

    public function financial(Request $request): View
    {
        $revenueQuery = Revenue::query();
        $expenseQuery = Expense::query();

        if ($request->filled('from')) {
            $revenueQuery->whereDate('paid_at', '>=', $request->string('from'));
            $expenseQuery->whereDate('expense_date', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $revenueQuery->whereDate('paid_at', '<=', $request->string('to'));
            $expenseQuery->whereDate('expense_date', '<=', $request->string('to'));
        }

        $totalRevenue = (float) $revenueQuery->sum('amount');
        $totalExpense = (float) $expenseQuery->sum('amount');
        $netProfit = $totalRevenue - $totalExpense;

        // Handle PDF Download
        if ($request->boolean('pdf')) {
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
}
