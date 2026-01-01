<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('expense.categories.index', [
            'categories' => ExpenseCategory::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('expense.categories.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:expense_categories,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'in:0,1'],
        ]);

        ExpenseCategory::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'active' => ($validated['active'] ?? '1') === '1',
        ]);

        return redirect()->route('expense.categories.index')->with('status', 'Expense category created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('expense.categories.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExpenseCategory $category): View
    {
        return view('expense.categories.edit', ['category' => $category]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExpenseCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:expense_categories,name,'.$category->id],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'in:0,1'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'active' => ($validated['active'] ?? '1') === '1',
        ]);

        return back()->with('status', 'Expense category updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExpenseCategory $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('expense.categories.index')->with('status', 'Expense category deleted.');
    }
}
