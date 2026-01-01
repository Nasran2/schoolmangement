<?php

namespace App\Http\Controllers;

use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\Setting;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Services\Billing\BillNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RevenueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
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

        return view('revenue.index', [
            'items' => $query->orderByDesc('paid_at')->paginate(15)->withQueryString(),
            'categories' => RevenueCategory::query()->orderBy('name')->get(),
            'filters' => $request->only(['category_id', 'from', 'to']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $selectedStudentId = $request->query('student_id') ?: null;
        $selectedStudent = null;
        if ($selectedStudentId) {
            $selectedStudent = Student::query()->with('classRoom')->find($selectedStudentId);
        }

        $categoriesQuery = RevenueCategory::query()->where('active', true);
        $monthlyCatId = $selectedStudent?->classRoom?->monthly_fee_revenue_category_id;
        if ($selectedStudent?->class_room_id) {
            $classRoomId = (int) $selectedStudent->class_room_id;
            $categoriesQuery->where(function ($q) use ($classRoomId) {
                $q->where('applies_to_all', true)
                    ->orWhereHas('classRooms', function ($q2) use ($classRoomId) {
                        $q2->where('class_rooms.id', $classRoomId);
                    });
            });
        }

        // Auto-select monthly fee category if coming from Quick Monthly Payment
        $preselectedCategoryId = null;
        if ($request->query('quick') === 'monthly') {
            $monthlyCategory = RevenueCategory::query()
                ->where('active', true)
                ->where('payment_type', 'monthly')
                ->orderBy('name')
                ->first();
            $preselectedCategoryId = $monthlyCategory?->id;
        }

        return view('revenue.create', [
            'categories' => $categoriesQuery->orderBy('name')->get(),
            'students' => Student::query()->where('active', true)->orderBy('name')->get(),
            'selectedStudentId' => $selectedStudentId,
            'selectedStudent' => $selectedStudent,
            'autogenerate' => app('settings')->get('billing.revenue.autogenerate', '1') === '1',
            'monthlyCatId' => $monthlyCatId,
            'preselectedCategoryId' => $preselectedCategoryId,
            'classRooms' => ClassRoom::query()
                ->orderByRaw('level is null')
                ->orderBy('level')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, BillNumberService $billNumbers): RedirectResponse
    {
        $validated = $request->validate([
            'revenue_category_id' => ['required', 'exists:revenue_categories,id'],
            'student_id' => ['nullable', 'exists:students,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'bill_no' => ['nullable', 'string', 'max:50', 'unique:revenues,bill_no'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! empty($validated['student_id'])) {
            $student = Student::query()->with('classRoom')->find((int) $validated['student_id']);
            $category = RevenueCategory::query()->with('classRooms')->find((int) $validated['revenue_category_id']);
            if ($student && $student->class_room_id && $category) {
                $allowed = $category->applies_to_all || $category->classRooms->contains('id', (int) $student->class_room_id);
                if (! $allowed) {
                    return back()->withInput()->withErrors([
                        'revenue_category_id' => 'This category is not applicable to the selected student class.',
                    ]);
                }
            }
        }

        $billNo = $validated['bill_no'] ?? null;
        if (! $billNo) {
            $billNo = $billNumbers->nextRevenueBillNumber() ?: null;
        }

        // Notes: only save what the user typed; do not auto-generate
        $notes = $validated['notes'] ?? null;

        $revenue = Revenue::create([
            'bill_no' => $billNo,
            'revenue_category_id' => (int) $validated['revenue_category_id'],
            'student_id' => $validated['student_id'] ? (int) $validated['student_id'] : null,
            'amount' => $validated['amount'],
            'paid_at' => $validated['paid_at'],
            'notes' => $notes,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('revenue.items.receipt', $revenue->id)
            ->with('status', 'Revenue recorded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('revenue.items.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Revenue $item): View
    {
        $selectedStudent = $item->student_id ? Student::query()->with('classRoom')->find($item->student_id) : null;

        $categoriesQuery = RevenueCategory::query()->where('active', true);
        if ($selectedStudent?->class_room_id) {
            $classRoomId = (int) $selectedStudent->class_room_id;
            $categoriesQuery->where(function ($q) use ($classRoomId, $item) {
                $q->where('applies_to_all', true)
                    ->orWhere('id', $item->revenue_category_id)
                    ->orWhereHas('classRooms', function ($q2) use ($classRoomId) {
                        $q2->where('class_rooms.id', $classRoomId);
                    });
            });
        }

        return view('revenue.edit', [
            'item' => $item->load(['category', 'student']),
            'categories' => $categoriesQuery->orderBy('name')->get(),
            'students' => Student::query()->where('active', true)->orderBy('name')->get(),
            'classRooms' => \App\Models\ClassRoom::query()
                ->orderByRaw('level is null')
                ->orderBy('level')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Revenue $item): RedirectResponse
    {
        $validated = $request->validate([
            'revenue_category_id' => ['required', 'exists:revenue_categories,id'],
            'student_id' => ['nullable', 'exists:students,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'bill_no' => ['nullable', 'string', 'max:50', 'unique:revenues,bill_no,'.$item->id],
            'notes' => ['nullable', 'string'],
        ]);

        if (! empty($validated['student_id'])) {
            $student = Student::query()->with('classRoom')->find((int) $validated['student_id']);
            $category = RevenueCategory::query()->with('classRooms')->find((int) $validated['revenue_category_id']);
            if ($student && $student->class_room_id && $category) {
                $allowed = $category->applies_to_all || $category->classRooms->contains('id', (int) $student->class_room_id);
                if (! $allowed) {
                    return back()->withInput()->withErrors([
                        'revenue_category_id' => 'This category is not applicable to the selected student class.',
                    ]);
                }
            }
        }

        $item->update([
            'bill_no' => $validated['bill_no'] ?? null,
            'revenue_category_id' => (int) $validated['revenue_category_id'],
            'student_id' => $validated['student_id'] ? (int) $validated['student_id'] : null,
            'amount' => $validated['amount'],
            'paid_at' => $validated['paid_at'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('status', 'Revenue updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Revenue $item): RedirectResponse
    {
        $item->delete();

        return redirect()->route('revenue.items.index')->with('status', 'Revenue deleted.');
    }

    /**
     * Display the receipt for the specified revenue.
     */
    public function receipt(Revenue $item): View
    {
        $item->load(['student.classRoom', 'category']);

        $settings = app('settings');
        $schoolInfo = [
            'name' => $settings->get('school.name', config('app.name')),
            'address' => $settings->get('school.address', ''),
            'phone' => $settings->get('school.phone', ''),
            'email' => $settings->get('school.email', ''),
        ];

        $autoPrint = $settings->get('receipt.auto_print', '0') === '1';

        return view('revenue.receipt', [
            'revenue' => $item,
            'schoolInfo' => $schoolInfo,
            'autoPrint' => $autoPrint,
        ]);
    }
}

