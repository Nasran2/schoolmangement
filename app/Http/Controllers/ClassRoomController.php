<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\RevenueCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassRoomController extends Controller
{
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
                $student->computed_due_amount = $student->computeMonthlyDue();
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
                ->where('payment_type', 'monthly')
                ->orderBy('name')
                ->get(),
            'suggestedLevel' => $this->nextAvailableLevel(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:class_rooms,name'],
            'level' => ['nullable', 'integer', 'min:0', 'unique:class_rooms,level'],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'monthly_fee_revenue_category_id' => ['nullable', 'integer', 'exists:revenue_categories,id'],
        ]);

        $data['active'] = (bool) ($data['active'] ?? false);
        $data['monthly_fee'] = $data['monthly_fee'] ?? 0;

        if (! array_key_exists('level', $data) || $data['level'] === null) {
            $data['level'] = $this->nextAvailableLevel();
        }

        ClassRoom::create($data);

        return redirect()->route('classrooms.index')->with('status', 'Class room created.');
    }

    public function edit(ClassRoom $classroom): View
    {
        return view('classrooms.edit', [
            'item' => $classroom,
            'monthlyCategories' => RevenueCategory::query()
                ->where('active', true)
                ->where('payment_type', 'monthly')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, ClassRoom $classroom): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:class_rooms,name,'.$classroom->id],
            'level' => ['nullable', 'integer', 'min:0', 'unique:class_rooms,level,'.$classroom->id],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'monthly_fee_revenue_category_id' => ['nullable', 'integer', 'exists:revenue_categories,id'],
        ]);

        $data['active'] = (bool) ($data['active'] ?? false);
        $data['monthly_fee'] = $data['monthly_fee'] ?? 0;

        $classroom->update($data);

        return redirect()->route('classrooms.index')->with('status', 'Class room updated.');
    }

    public function destroy(ClassRoom $classroom): RedirectResponse
    {
        $classroom->delete();

        return redirect()->route('classrooms.index')->with('status', 'Class room deleted.');
    }
}
