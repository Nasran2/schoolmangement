<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\RevenueCategory;
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
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100', 'unique:revenue_categories,name'],
            'payment_type' => ['required', 'in:monthly,one_time,yearly,2_months,3_months,6_months'],
            'applies_to_all' => ['required', 'in:0,1'],
            'class_room_ids' => ['array'],
            'class_room_ids.*' => ['integer', 'exists:class_rooms,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'in:0,1'],
        ]);

        $validator->after(function ($v) use ($request) {
            $appliesToAll = ($request->input('applies_to_all', '1') === '1');
            $ids = (array) $request->input('class_room_ids', []);
            if (! $appliesToAll && count($ids) === 0) {
                $v->errors()->add('class_room_ids', 'Select at least one class room or choose Applies to all classes.');
            }
        });

        $validated = $validator->validate();

        $appliesToAll = ($validated['applies_to_all'] ?? '1') === '1';

        $category = RevenueCategory::create([
            'name' => $validated['name'],
            'payment_type' => $validated['payment_type'],
            'applies_to_all' => $appliesToAll,
            'description' => $validated['description'] ?? null,
            'active' => ($validated['active'] ?? '1') === '1',
        ]);

        $category->classRooms()->sync($appliesToAll ? [] : ($validated['class_room_ids'] ?? []));

        return redirect()->route('revenue.categories.index')->with('status', 'Revenue category created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('revenue.categories.index');
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
            'payment_type' => ['required', 'in:monthly,one_time,yearly,2_months,3_months,6_months'],
            'applies_to_all' => ['required', 'in:0,1'],
            'class_room_ids' => ['array'],
            'class_room_ids.*' => ['integer', 'exists:class_rooms,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'in:0,1'],
        ]);

        $validator->after(function ($v) use ($request) {
            $appliesToAll = ($request->input('applies_to_all', '1') === '1');
            $ids = (array) $request->input('class_room_ids', []);
            if (! $appliesToAll && count($ids) === 0) {
                $v->errors()->add('class_room_ids', 'Select at least one class room or choose Applies to all classes.');
            }
        });

        $validated = $validator->validate();

        $appliesToAll = ($validated['applies_to_all'] ?? '1') === '1';

        $category->update([
            'name' => $validated['name'],
            'payment_type' => $validated['payment_type'],
            'applies_to_all' => $appliesToAll,
            'description' => $validated['description'] ?? null,
            'active' => ($validated['active'] ?? '1') === '1',
        ]);

        $category->classRooms()->sync($appliesToAll ? [] : ($validated['class_room_ids'] ?? []));

        return back()->with('status', 'Revenue category updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RevenueCategory $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('revenue.categories.index')->with('status', 'Revenue category deleted.');
    }
}
