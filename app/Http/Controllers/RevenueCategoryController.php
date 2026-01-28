<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\Student;
use App\Services\Revenue\RevenueCategoryScheduleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class RevenueCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('revenue.categories.index', [
            'categories' => RevenueCategory::query()->orderBy('name')->get(),
            'classRooms' => ClassRoom::query()
                ->orderByRaw('level is null')
                ->orderBy('level')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('revenue.categories.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100', 'unique:revenue_categories,name'],
            'payment_type' => ['required', 'in:monthly,one_time,yearly,2_months,3_months,6_months,custom_months'],
            'interval_months' => ['nullable', 'integer', 'min:1', 'max:24'],
            'first_due_date' => ['nullable', 'date'],
            'reminder_days_before' => ['nullable', 'integer', 'min:0', 'max:60'],
            'default_amount' => ['nullable', 'numeric', 'min:0.01'],
            'applies_to_all' => ['required', 'in:0,1'],
            'class_room_ids' => ['array'],
            'class_room_ids.*' => ['integer', 'exists:class_rooms,id'],
            'class_room_amounts' => ['array'],
            'class_room_amounts.*' => ['nullable', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'in:0,1'],
        ]);

        $validator->after(function ($v) use ($request) {
            $appliesToAll = ($request->input('applies_to_all', '1') === '1');
            $ids = (array) $request->input('class_room_ids', []);
            if (! $appliesToAll && count($ids) === 0) {
                $v->errors()->add('class_room_ids', 'Select at least one class room or choose Applies to all classes.');
            }

            $type = (string) $request->input('payment_type', '');
            if ($type === 'custom_months') {
                $n = (int) $request->input('interval_months', 0);
                if ($n < 1) {
                    $v->errors()->add('interval_months', 'Interval months is required for Custom (Every N Months).');
                }
            }

            $isRecurring = in_array($type, ['monthly','2_months','3_months','6_months','yearly','custom_months'], true);
            if ($isRecurring) {
                if (! $request->filled('first_due_date')) {
                    $v->errors()->add('first_due_date', 'First due date is required for recurring categories.');
                }
                if (! $request->filled('default_amount')) {
                    $v->errors()->add('default_amount', 'Amount per student is required for recurring categories.');
                }
            }
        });

        $validated = $validator->validate();

        $appliesToAll = ($validated['applies_to_all'] ?? '1') === '1';

        $type = (string) $validated['payment_type'];
        $intervalMonths = null;
        if ($type === 'custom_months') {
            $intervalMonths = isset($validated['interval_months']) ? (int) $validated['interval_months'] : null;
        } else {
            $intervalMonths = match ($type) {
                'monthly' => 1,
                '2_months' => 2,
                '3_months' => 3,
                '6_months' => 6,
                'yearly' => 12,
                default => null,
            };
        }

        $category = RevenueCategory::create([
            'name' => $validated['name'],
            'payment_type' => $validated['payment_type'],
            'interval_months' => $intervalMonths,
            'first_due_date' => $validated['first_due_date'] ?? null,
            'reminder_days_before' => isset($validated['reminder_days_before']) ? (int) $validated['reminder_days_before'] : 5,
            'default_amount' => $validated['default_amount'] ?? null,
            'applies_to_all' => $appliesToAll,
            'description' => $validated['description'] ?? null,
            'active' => ($validated['active'] ?? '1') === '1',
        ]);

        $ids = array_map('intval', $validated['class_room_ids'] ?? []);
        $amounts = (array) ($validated['class_room_amounts'] ?? []);
        $sync = [];
        foreach ($ids as $id) {
            $raw = $amounts[$id] ?? $amounts[(string) $id] ?? null;
            $sync[$id] = ['amount' => $raw !== null ? (float) $raw : null];
        }
        $category->classRooms()->sync($appliesToAll ? $sync : $sync);

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $category->id,
                'name' => $category->name,
                'payment_type' => $category->payment_type,
                'interval_months' => $category->interval_months,
                'first_due_date' => optional($category->first_due_date)->toDateString(),
                'reminder_days_before' => (int) ($category->reminder_days_before ?? 5),
                'default_amount' => $category->default_amount,
            ], 201);
        }

        return redirect()->route('revenue.categories.index')->with('status', 'Revenue category created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, RevenueCategory $category, RevenueCategoryScheduleService $schedule): View
    {
        $category->load('classRooms');

        $classRoomsQuery = ClassRoom::query()
            ->orderByRaw('level is null')
            ->orderBy('level')
            ->orderBy('name');

        $applicableClassRooms = $category->applies_to_all
            ? $classRoomsQuery->get()
            : $classRoomsQuery->whereIn('id', $category->classRooms->pluck('id'))->get();

        // Amount overrides only apply to attached class rooms
        $amountOverrides = $category->classRooms
            ->mapWithKeys(fn ($cr) => [(int) $cr->id => ($cr->pivot?->amount !== null ? (float) $cr->pivot->amount : null)])
            ->toArray();

        $dueParam = $request->query('due');
        $cycle = null;
        if ($dueParam) {
            try {
                $cycle = $schedule->cycleForDueDate($category, Carbon::parse($dueParam));
            } catch (\Throwable $e) {
                $cycle = null;
            }
        }
        if (! $cycle) {
            $cycle = $schedule->currentCycle($category, now());
        }

        $cycleStart = $cycle['start'] ?? null;
        $cycleDue = $cycle['due'] ?? null;

        $studentCounts = Student::query()
            ->where('active', true)
            ->whereIn('class_room_id', $applicableClassRooms->pluck('id'))
            ->selectRaw('class_room_id, count(*) as total')
            ->groupBy('class_room_id')
            ->pluck('total', 'class_room_id');

        $paidCounts = collect();
        $paidAmounts = collect();
        if ($cycleStart && $cycleDue) {
            $rows = Revenue::query()
                ->join('students', 'students.id', '=', 'revenues.student_id')
                ->where('revenues.revenue_category_id', $category->id)
                ->whereNotNull('revenues.student_id')
                ->whereBetween('revenues.paid_at', [$cycleStart->copy()->startOfDay(), $cycleDue->copy()->endOfDay()])
                ->selectRaw('students.class_room_id as class_room_id, count(distinct revenues.student_id) as paid_students, sum(revenues.amount) as paid_amount')
                ->groupBy('students.class_room_id')
                ->get();

            $paidCounts = $rows->pluck('paid_students', 'class_room_id');
            $paidAmounts = $rows->pluck('paid_amount', 'class_room_id');
        }

        // Cycle history (latest -> older)
        $history = [];
        if ($category->intervalMonths()) {
            $historyCycles = $schedule->recentCycles($category, 6, $cycleDue ?: now());
            foreach ($historyCycles as $c) {
                $s = $c['start'];
                $d = $c['due'];
                $rows = Revenue::query()
                    ->join('students', 'students.id', '=', 'revenues.student_id')
                    ->where('revenues.revenue_category_id', $category->id)
                    ->whereNotNull('revenues.student_id')
                    ->whereBetween('revenues.paid_at', [$s->copy()->startOfDay(), $d->copy()->endOfDay()])
                    ->selectRaw('students.class_room_id as class_room_id, count(distinct revenues.student_id) as paid_students, sum(revenues.amount) as paid_amount')
                    ->groupBy('students.class_room_id')
                    ->get();
                $pc = $rows->pluck('paid_students', 'class_room_id');
                $pa = $rows->pluck('paid_amount', 'class_room_id');

                $expected = 0.0;
                $totalStudents = 0;
                $paidStudents = 0;
                foreach ($applicableClassRooms as $cr) {
                    $classTotal = (int) ($studentCounts[$cr->id] ?? 0);
                    $totalStudents += $classTotal;
                    $paidStudents += (int) ($pc[$cr->id] ?? 0);

                    $amt = $amountOverrides[$cr->id] ?? ($category->default_amount !== null ? (float) $category->default_amount : null);
                    if ($amt !== null) {
                        $expected += $classTotal * $amt;
                    }
                }

                $history[] = [
                    'cycle' => $c,
                    'total_students' => $totalStudents,
                    'paid_students' => $paidStudents,
                    'expected_amount' => $expected,
                    'paid_amount' => (float) $pa->sum(fn ($v) => (float) ($v ?? 0)),
                ];
            }
        }

        return view('revenue.categories.show', [
            'category' => $category,
            'classRooms' => $applicableClassRooms,
            'cycle' => $cycle,
            'amountOverrides' => $amountOverrides,
            'studentCounts' => $studentCounts,
            'paidCounts' => $paidCounts,
            'paidAmounts' => $paidAmounts,
            'history' => $history,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RevenueCategory $category): View
    {
        return view('revenue.categories.edit', [
            'category' => $category,
            'classRooms' => ClassRoom::query()
                ->orderByRaw('level is null')
                ->orderBy('level')
                ->orderBy('name')
                ->get(),
            'selectedClassRoomIds' => $category->classRooms()->pluck('class_rooms.id')->all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RevenueCategory $category): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100', 'unique:revenue_categories,name,'.$category->id],
            'payment_type' => ['required', 'in:monthly,one_time,yearly,2_months,3_months,6_months,custom_months'],
            'interval_months' => ['nullable', 'integer', 'min:1', 'max:24'],
            'first_due_date' => ['nullable', 'date'],
            'reminder_days_before' => ['nullable', 'integer', 'min:0', 'max:60'],
            'default_amount' => ['nullable', 'numeric', 'min:0.01'],
            'applies_to_all' => ['required', 'in:0,1'],
            'class_room_ids' => ['array'],
            'class_room_ids.*' => ['integer', 'exists:class_rooms,id'],
            'class_room_amounts' => ['array'],
            'class_room_amounts.*' => ['nullable', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'in:0,1'],
        ]);

        $validator->after(function ($v) use ($request) {
            $appliesToAll = ($request->input('applies_to_all', '1') === '1');
            $ids = (array) $request->input('class_room_ids', []);
            if (! $appliesToAll && count($ids) === 0) {
                $v->errors()->add('class_room_ids', 'Select at least one class room or choose Applies to all classes.');
            }

            $type = (string) $request->input('payment_type', '');
            if ($type === 'custom_months') {
                $n = (int) $request->input('interval_months', 0);
                if ($n < 1) {
                    $v->errors()->add('interval_months', 'Interval months is required for Custom (Every N Months).');
                }
            }

            $isRecurring = in_array($type, ['monthly','2_months','3_months','6_months','yearly','custom_months'], true);
            if ($isRecurring) {
                if (! $request->filled('first_due_date')) {
                    $v->errors()->add('first_due_date', 'First due date is required for recurring categories.');
                }
                if (! $request->filled('default_amount')) {
                    $v->errors()->add('default_amount', 'Amount per student is required for recurring categories.');
                }
            }
        });

        $validated = $validator->validate();

        $appliesToAll = ($validated['applies_to_all'] ?? '1') === '1';

        $type = (string) $validated['payment_type'];
        $intervalMonths = null;
        if ($type === 'custom_months') {
            $intervalMonths = isset($validated['interval_months']) ? (int) $validated['interval_months'] : null;
        } else {
            $intervalMonths = match ($type) {
                'monthly' => 1,
                '2_months' => 2,
                '3_months' => 3,
                '6_months' => 6,
                'yearly' => 12,
                default => null,
            };
        }

        $category->update([
            'name' => $validated['name'],
            'payment_type' => $validated['payment_type'],
            'interval_months' => $intervalMonths,
            'first_due_date' => $validated['first_due_date'] ?? null,
            'reminder_days_before' => isset($validated['reminder_days_before']) ? (int) $validated['reminder_days_before'] : (int) ($category->reminder_days_before ?? 5),
            'default_amount' => $validated['default_amount'] ?? null,
            'applies_to_all' => $appliesToAll,
            'description' => $validated['description'] ?? null,
            'active' => ($validated['active'] ?? '1') === '1',
        ]);

        $ids = array_map('intval', $validated['class_room_ids'] ?? []);
        $amounts = (array) ($validated['class_room_amounts'] ?? []);
        $sync = [];
        foreach ($ids as $id) {
            $raw = $amounts[$id] ?? $amounts[(string) $id] ?? null;
            $sync[$id] = ['amount' => $raw !== null ? (float) $raw : null];
        }
        $category->classRooms()->sync($appliesToAll ? $sync : $sync);

        return back()->with('status', 'Revenue category updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RevenueCategory $category): RedirectResponse
    {
        RevenueCategory::destroy($category->id);

        return redirect()->route('revenue.categories.index')->with('status', 'Revenue category deleted.');
    }
}
