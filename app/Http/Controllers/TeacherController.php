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

        $teachers = (clone $query)->orderBy('name')->paginate(15)->withQueryString();

        $totalTeachers = (clone $query)->count();
        $activeTeachers = (clone $query)->where('active', true)->count();

        $today = now();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $paidTeacherIds = TeacherSalaryPayment::query()
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->distinct()
            ->pluck('teacher_id')
            ->filter()
            ->values();

        $dueQuery = (clone $query)
            ->where('active', true)
            ->when($paidTeacherIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $paidTeacherIds));

        $teachersSalaryDueCount = (clone $dueQuery)->count();
        $totalSalaryPayable = (float) (clone $dueQuery)->sum('salary_amount');

        return view('teachers.index', [
            'teachers' => $teachers,
            'filters' => $request->only(['q']),
            'totalTeachers' => $totalTeachers,
            'activeTeachers' => $activeTeachers,
            'teachersSalaryDueCount' => $teachersSalaryDueCount,
            'totalSalaryPayable' => $totalSalaryPayable,
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

        $salaryHistory = \App\Models\AuditLog::query()
            ->where('auditable_type', get_class($teacher))
            ->where('auditable_id', $teacher->id)
            ->where('action', 'teacher.salary_updated')
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        $paymentUpdates = \App\Models\AuditLog::query()
            ->where('action', 'salary_payment.updated')
            ->where('auditable_type', \App\Models\TeacherSalaryPayment::class)
            ->whereIn('auditable_id', $teacher->salaryPayments()->pluck('id'))
            ->with(['user', 'auditable'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('teachers.show', [
            'teacher' => $teacher,
            'payments' => $payments,
            'salaryHistory' => $salaryHistory,
            'paymentUpdates' => $paymentUpdates,
            'componentTypes' => $this->getComponentTypes(),
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
     * Quickly update only the teacher's base monthly salary from the details page.
     */
    public function updateSalary(Request $request, Teacher $teacher): RedirectResponse
    {
        $validated = $request->validate([
            'salary_amount' => ['nullable', 'numeric', 'min:0'],
            'salary_components' => ['nullable', 'array'],
            'salary_components.*.type' => ['required_with:salary_components', 'string', 'max:120'],
            'salary_components.*.amount' => ['required_with:salary_components', 'numeric', 'min:0'],
        ]);

        $before = [
            'amount' => (float) ($teacher->salary_amount ?? 0),
            'components' => $teacher->salary_components,
        ];

        $components = $validated['salary_components'] ?? null;
        $newAmount = $validated['salary_amount'] ?? null;

        if (is_array($components) && count($components) > 0) {
            $total = 0.0;
            $normalized = [];
            foreach ($components as $c) {
                $type = (string) ($c['type'] ?? '');
                $amount = (float) ($c['amount'] ?? 0);
                if ($type === '' && $amount <= 0) { continue; }
                $normalized[] = ['type' => $type, 'amount' => $amount];
                $total += $amount;
            }
            $components = $normalized;
            // If components provided, salary is the sum
            $newAmount = $total;
        }

        // Fallback: if nothing provided, keep existing
        if ($newAmount === null) {
            $newAmount = (float) ($teacher->salary_amount ?? 0);
        }

        $teacher->update([
            'salary_amount' => (float) $newAmount,
            'salary_components' => $components,
        ]);

        try {
            app(\App\Services\AuditLogger::class)->log(
                'teacher.salary_updated',
                $teacher,
                'Teacher monthly salary updated',
                [
                    'before' => $before,
                    'after' => [
                        'amount' => (float) $teacher->salary_amount,
                        'components' => $teacher->salary_components,
                    ],
                ]
            );
        } catch (\Throwable $e) {
            // non-blocking
        }

        return back()->with('status', 'Monthly salary updated to Rs '.number_format((float)$teacher->salary_amount, 2));
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
