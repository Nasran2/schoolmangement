<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\RevenueCategory;
use App\Services\Billing\ClassFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClassRoomController extends Controller
{
    private function feeRules(Request $request): array
    {
        $rules = [];
        foreach (['exam', 'facility'] as $type) {
            $rules[$type.'_fee'] = ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'];
            $rules[$type.'_fee_start_date'] = [Rule::requiredIf(fn () => (float) $request->input($type.'_fee', 0) > 0), 'nullable', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before:2100-01-01'];
        }

        $rules['computer_fee'] = ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'];
        $rules['exam_terms'] = [Rule::requiredIf(fn () => (float) $request->input('computer_fee', 0) > 0), 'nullable', 'array', 'size:3'];
        $rules['exam_terms.*.start'] = ['required_with:exam_terms', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before:2100-01-01'];
        $rules['exam_terms.*.end'] = ['required_with:exam_terms', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before:2100-01-01'];

        return $rules;
    }

    private function nextAvailableLevel(): int
    {
        $levels = ClassRoom::query()
            ->whereNotNull('level')
            ->pluck('level')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->sort()
            ->values();

        $expected = 0;
        foreach ($levels as $level) {
            if ($level < 0) {
                continue;
            }
            if ($level === $expected) {
                $expected++;

                continue;
            }
            if ($level > $expected) {
                break;
            }
        }

        return $expected;
    }

    public function index(Request $request): View
    {
        $query = ClassRoom::query()
            ->withCount('students')
            ->orderByRaw('level is null')
            ->orderBy('level')
            ->orderBy('name');

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where('name', 'like', "%{$q}%");
        }

        return view('classrooms.index', [
            'items' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function show(ClassRoom $classroom): View
    {
        $students = $classroom->students()
            ->orderBy('name')
            ->get()
            ->map(function ($student) {
                $student->setAttribute('computed_due_amount', (float) ($student->due_amount ?? 0));

                return $student;
            });

        return view('classrooms.show', [
            'classroom' => $classroom,
            'students' => $students,
        ]);
    }

    public function create(): View
    {
        return view('classrooms.create', [
            'monthlyCategories' => RevenueCategory::query()
                ->where('active', true)
                ->whereNull('class_fee_type')
                ->where(fn ($q) => $q->where('interval_months', 1)->orWhere('payment_type', 'monthly'))
                ->orderBy('name')
                ->get(),
            'suggestedLevel' => $this->nextAvailableLevel(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->feeRules($request) + [
            'name' => ['required', 'string', 'max:255', 'unique:class_rooms,name'],
            'level' => ['nullable', 'integer', 'min:0', 'unique:class_rooms,level'],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'monthly_fee_revenue_category_id' => ['nullable', 'integer', Rule::exists('revenue_categories', 'id')->whereNull('class_fee_type')],
        ]);

        $data['active'] = (bool) ($data['active'] ?? false);
        $data['monthly_fee'] = $data['monthly_fee'] ?? 0;
        if (empty($data['monthly_fee_revenue_category_id'])) {
            $data['monthly_fee_revenue_category_id'] = RevenueCategory::query()
                ->where('active', true)
                ->where(function ($q) {
                    $q->where('name', 'Monthly Fee')
                        ->orWhere('payment_type', 'monthly');
                })
                ->orderByRaw("CASE WHEN name = 'Monthly Fee' THEN 0 ELSE 1 END")
                ->value('id');
        }

        if (! array_key_exists('level', $data) || $data['level'] === null) {
            $data['level'] = $this->nextAvailableLevel();
        }

        DB::transaction(function () use ($data) {
            $class = ClassRoom::create(Arr::except($data, ['exam_fee', 'exam_fee_start_date', 'exam_terms', 'computer_fee', 'facility_fee', 'facility_fee_start_date']));
            app(ClassFeeService::class)->configure($class, $data);
        });

        return redirect()->route('classrooms.index')->with('status', 'Class room created.');
    }

    public function edit(ClassRoom $classroom): View
    {
        return view('classrooms.edit', [
            'item' => $classroom,
            'classFeeValues' => app(ClassFeeService::class)->formValues($classroom),
            'monthlyCategories' => RevenueCategory::query()
                ->where('active', true)
                ->whereNull('class_fee_type')
                ->where(fn ($q) => $q->where('interval_months', 1)->orWhere('payment_type', 'monthly'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, ClassRoom $classroom): RedirectResponse
    {
        $data = $request->validate($this->feeRules($request) + [
            'name' => ['required', 'string', 'max:255', 'unique:class_rooms,name,'.$classroom->id],
            'level' => ['nullable', 'integer', 'min:0', 'unique:class_rooms,level,'.$classroom->id],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'monthly_fee_revenue_category_id' => ['nullable', 'integer', Rule::exists('revenue_categories', 'id')->whereNull('class_fee_type')],
        ]);

        $data['active'] = (bool) ($data['active'] ?? false);
        $data['monthly_fee'] = $data['monthly_fee'] ?? 0;
        if (empty($data['monthly_fee_revenue_category_id'])) {
            $data['monthly_fee_revenue_category_id'] = RevenueCategory::query()
                ->where('active', true)
                ->where(function ($q) {
                    $q->where('name', 'Monthly Fee')
                        ->orWhere('payment_type', 'monthly');
                })
                ->orderByRaw("CASE WHEN name = 'Monthly Fee' THEN 0 ELSE 1 END")
                ->value('id');
        }

        DB::transaction(function () use ($classroom, $data) {
            ClassRoom::whereKey($classroom->id)->lockForUpdate()->firstOrFail();
            $classroom->update(Arr::except($data, ['exam_fee', 'exam_fee_start_date', 'exam_terms', 'computer_fee', 'facility_fee', 'facility_fee_start_date']));
            app(ClassFeeService::class)->configure($classroom, $data);
        });

        return redirect()->route('classrooms.index')->with('status', 'Class room updated.');
    }

    public function destroy(ClassRoom $classroom): RedirectResponse
    {
        if (\App\Models\ClassFeePaymentHistory::whereHas('classFeeSchedule', fn ($q) => $q->where('class_room_id', $classroom->id))->exists() || \App\Models\Revenue::whereHas('classFeeSchedule', fn ($q) => $q->where('class_room_id', $classroom->id))->exists()) {
            return back()->withErrors(['classroom' => 'This class has term or facility fee receipts. Deactivate it to preserve its fee history.']);
        }
        $classroom->delete();

        return redirect()->route('classrooms.index')->with('status', 'Class room deleted.');
    }
}
