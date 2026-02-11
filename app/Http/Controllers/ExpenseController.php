<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\TeacherSalaryPayment;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Expense::query()->with(['category']);

        $salaryQuery = TeacherSalaryPayment::query()->with(['teacher']);

        if ($request->filled('q')) {
            $raw = (string) $request->string('q');
            $q = '%' . str_replace('%', '\\%', $raw) . '%';
            $query->where(function ($sub) use ($q) {
                $sub->where('notes', 'like', $q)
                    ->orWhereHas('category', function ($c) use ($q) {
                        $c->where('name', 'like', $q);
                    });
            });

            $salaryQuery->where(function ($sub) use ($q) {
                $sub->where('notes', 'like', $q)
                    ->orWhereHas('teacher', function ($t) use ($q) {
                        $t->where('name', 'like', $q);
                    });
            });
        }

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

        return view('expense.index', [
            'items' => $query->orderByDesc('expense_date')->paginate(15)->withQueryString(),
            'salaryPayments' => $salaryQuery->orderByDesc('paid_at')->limit(100)->get(),
            'categories' => ExpenseCategory::query()->orderBy('name')->get(),
            'filters' => $request->only(['category_id', 'from', 'to', 'q']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('expense.create', [
            'categories' => ExpenseCategory::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,cheque'],
            'bank_name' => ['required_if:payment_method,bank_transfer', 'nullable', 'string', 'max:120'],
            'bank_ref_no' => ['nullable', 'string', 'max:120'],
            'cheque_date' => ['required_if:payment_method,cheque', 'nullable', 'date'],
            'cheque_number' => ['required_if:payment_method,cheque', 'nullable', 'string', 'max:100'],
            'cheque_bank' => ['required_if:payment_method,cheque', 'nullable', 'string', 'max:100'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $paymentMethod = $validated['payment_method'] ?? 'cash';
        if (! in_array($paymentMethod, ['cash', 'bank_transfer', 'cheque'], true)) {
            $paymentMethod = 'cash';
        }

        $paymentMeta = null;
        $chequeDate = null;

        if ($paymentMethod === 'bank_transfer') {
            $paymentMeta = [
                'bank' => $validated['bank_name'] ?? null,
                'ref_no' => $validated['bank_ref_no'] ?? null,
            ];
        }

        if ($paymentMethod === 'cheque') {
            $chequeDate = $validated['cheque_date'] ?? null;
            $paymentMeta = [
                'cheque_number' => $validated['cheque_number'] ?? null,
                'bank' => $validated['cheque_bank'] ?? null,
            ];
        }

        $expense = Expense::create([
            'expense_category_id' => (int) $validated['expense_category_id'],
            'amount' => $validated['amount'],
            'payment_method' => $paymentMethod,
            'payment_meta' => $paymentMeta,
            'cheque_date' => $chequeDate,
            'expense_date' => $validated['expense_date'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        app(AuditLogger::class)->log(
            'expense.create',
            $expense,
            'Expense recorded',
            [
                'amount' => (float) $expense->amount,
                'expense_category_id' => $expense->expense_category_id,
                'expense_date' => $expense->expense_date,
            ]
        );

        return redirect()->route('expense.items.index')->with('status', 'Expense recorded.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('expense.items.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $item): View
    {
        return view('expense.edit', [
            'item' => $item->load(['category']),
            'categories' => ExpenseCategory::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $item): RedirectResponse
    {
        $validated = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,cheque'],
            'bank_name' => ['required_if:payment_method,bank_transfer', 'nullable', 'string', 'max:120'],
            'bank_ref_no' => ['nullable', 'string', 'max:120'],
            'cheque_date' => ['required_if:payment_method,cheque', 'nullable', 'date'],
            'cheque_number' => ['required_if:payment_method,cheque', 'nullable', 'string', 'max:100'],
            'cheque_bank' => ['required_if:payment_method,cheque', 'nullable', 'string', 'max:100'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $paymentMethod = $validated['payment_method'] ?? 'cash';
        if (! in_array($paymentMethod, ['cash', 'bank_transfer', 'cheque'], true)) {
            $paymentMethod = 'cash';
        }

        $paymentMeta = null;
        $chequeDate = null;

        if ($paymentMethod === 'bank_transfer') {
            $paymentMeta = [
                'bank' => $validated['bank_name'] ?? null,
                'ref_no' => $validated['bank_ref_no'] ?? null,
            ];
        }

        if ($paymentMethod === 'cheque') {
            $chequeDate = $validated['cheque_date'] ?? null;
            $paymentMeta = [
                'cheque_number' => $validated['cheque_number'] ?? null,
                'bank' => $validated['cheque_bank'] ?? null,
            ];
        }

        $item->update([
            'expense_category_id' => (int) $validated['expense_category_id'],
            'amount' => $validated['amount'],
            'payment_method' => $paymentMethod,
            'payment_meta' => $paymentMeta,
            'cheque_date' => $chequeDate,
            'expense_date' => $validated['expense_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        app(AuditLogger::class)->log(
            'expense.update',
            $item,
            'Expense updated',
            [
                'amount' => (float) $item->amount,
                'expense_category_id' => $item->expense_category_id,
                'expense_date' => $item->expense_date,
            ]
        );

        return back()->with('status', 'Expense updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $item): RedirectResponse
    {
        app(AuditLogger::class)->log(
            'expense.delete',
            $item,
            'Expense deleted',
            [
                'amount' => (float) $item->amount,
                'expense_category_id' => $item->expense_category_id,
                'expense_date' => $item->expense_date,
            ]
        );

        $item->delete();

        return redirect()->route('expense.items.index')->with('status', 'Expense deleted.');
    }
}
