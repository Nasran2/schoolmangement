<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\TeacherSalaryPayment;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Teacher::query();

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', $q)
                    ->orWhere('phone', 'like', $q)
                    ->orWhere('assigned_classes', 'like', $q);
            });
        }

        return view('teachers.index', [
            'teachers' => $query->orderBy('name')->paginate(15)->withQueryString(),
            'filters' => $request->only(['q']),
        ]);
    }

    /**
     * Lightweight teacher search for selectors (name or phone).
     */
    public function search(Request $request): JsonResponse
    {
        $query = Teacher::query();

        if ($request->filled('q')) {
            $term = '%' . $request->string('q') . '%';
            $query->where(function ($sub) use ($term) {
                $sub->where('name', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        $teachers = $query
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'phone', 'salary_amount', 'active']);

        return response()->json($teachers);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $classrooms = \App\Models\ClassRoom::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $componentTypes = $this->getComponentTypes();

        return view('teachers.create', [
            'classrooms' => $classrooms,
            'componentTypes' => $componentTypes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'joining_date' => ['nullable', 'date'],
            'payment_start_date' => ['nullable', 'date'],
            'assigned_classes_hidden' => ['nullable', 'string'],
            'salary_amount' => ['required', 'numeric', 'min:0.01'],
            'salary_components' => ['nullable', 'array'],
            'salary_components.*.type' => ['required', 'string'],
            'salary_components.*.amount' => ['required', 'numeric', 'min:0'],
            'epf_enabled' => ['nullable', 'in:0,1'],
            'etf_enabled' => ['nullable', 'in:0,1'],
            'active' => ['nullable', 'in:0,1'],
        ]);

        $teacher = Teacher::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'joining_date' => $validated['joining_date'] ?? null,
            'payment_start_date' => $validated['payment_start_date'] ?? null,
            'assigned_classes' => $validated['assigned_classes_hidden'] ?? null,
            'salary_amount' => $validated['salary_amount'],
            'salary_components' => $validated['salary_components'] ?? null,
            'epf_enabled' => ($validated['epf_enabled'] ?? '1') === '1',
            'etf_enabled' => ($validated['etf_enabled'] ?? '1') === '1',
            'active' => ($validated['active'] ?? '1') === '1',
        ]);

        return redirect()->route('teachers.show', $teacher)->with('status', 'Teacher created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Teacher $teacher): View
    {
        $payments = TeacherSalaryPayment::query()
            ->where('teacher_id', $teacher->id)
            ->orderByDesc('paid_at')
            ->paginate(15);

        return view('teachers.show', [
            'teacher' => $teacher,
            'payments' => $payments,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Teacher $teacher): View
    {
        $classrooms = \App\Models\ClassRoom::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $componentTypes = $this->getComponentTypes();

        return view('teachers.edit', [
            'teacher' => $teacher,
            'classrooms' => $classrooms,
            'componentTypes' => $componentTypes,
        ]);
    }

    /**
     * @return array<int,string>
     */
    private function getComponentTypes(): array
    {
        $defaults = [
            'Basic Salary',
            'House Allowance',
            'Transport Allowance',
            'Medical Allowance',
            'Incentive',
            'Bonus',
            'Special Allowance',
            'Other',
        ];

        $settings = app(SettingsService::class);
        $raw = $settings->get('salary_component_types', '');
        $decoded = json_decode((string) $raw, true);

        if (is_array($decoded) && count($decoded) > 0) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }

        return $defaults;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'joining_date' => ['nullable', 'date'],
            'payment_start_date' => ['nullable', 'date'],
            'assigned_classes_hidden' => ['nullable', 'string'],
            'salary_amount' => ['required', 'numeric', 'min:0.01'],
            'salary_components' => ['nullable', 'array'],
            'salary_components.*.type' => ['required', 'string'],
            'salary_components.*.amount' => ['required', 'numeric', 'min:0'],
            'epf_enabled' => ['nullable', 'in:0,1'],
            'etf_enabled' => ['nullable', 'in:0,1'],
            'active' => ['nullable', 'in:0,1'],
        ]);

        $teacher->update([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'joining_date' => $validated['joining_date'] ?? null,
            'payment_start_date' => $validated['payment_start_date'] ?? null,
            'assigned_classes' => $validated['assigned_classes_hidden'] ?? null,
            'salary_amount' => $validated['salary_amount'],
            'salary_components' => $validated['salary_components'] ?? null,
            'epf_enabled' => ($validated['epf_enabled'] ?? ($teacher->epf_enabled ? '1' : '0')) === '1',
            'etf_enabled' => ($validated['etf_enabled'] ?? ($teacher->etf_enabled ? '1' : '0')) === '1',
            'active' => ($validated['active'] ?? '1') === '1',
        ]);

        return back()->with('status', 'Teacher updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Teacher $teacher): RedirectResponse
    {
        $teacher->delete();

        return redirect()->route('teachers.index')->with('status', 'Teacher deleted.');
    }
}
