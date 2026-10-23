<?php

namespace App\Http\Controllers;

use App\Models\ClassFeeSchedule;
use App\Models\ClassRoom;
use App\Models\RevenueCategory;
use App\Models\Student;
use App\Services\Billing\BillNumberService;
use App\Services\Billing\ClassFeeService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClassFeeCollectionController extends Controller
{
    public function show(Request $request, string $type, ClassFeeService $fees): View|JsonResponse
    {
        abort_unless(isset(ClassFeeSchedule::TYPES[$type]), 404);
        $filters = $request->validate([
            'class_room_id' => ['nullable', 'integer', 'exists:class_rooms,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2099'],
            'all_years' => ['nullable', 'boolean'],
            'include_inactive' => ['nullable', 'boolean'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'cycle' => ['nullable', 'string', 'regex:/^\d+:\d{4}-\d{2}-\d{2}$/'],
            'suggest' => ['nullable', 'boolean'],
        ]);
        $year = (int) ($filters['year'] ?? today()->year);
        $filters['all_years'] = $request->boolean('all_years', true);
        $classes = ClassRoom::orderBy('level')->orderBy('name')->get();
        $members = Student::query()->with('classRoom')
            ->when(! $request->boolean('include_inactive'), fn ($query) => $query->where('active', true)->where('alumni', false))
            ->when(! empty($filters['class_room_id']), fn ($query) => $query->where('class_room_id', $filters['class_room_id']));
        $search = trim($filters['q'] ?? '');
        $students = (clone $members)
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('admission_number', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%')))
            ->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();
        if ($request->boolean('suggest')) {
            return response()->json(['results' => $students->take(10)->map(fn ($student) => [
                'id' => $student->id, 'name' => $student->name, 'admission_number' => $student->admission_number,
                'class' => $student->classRoom?->name ?? 'No class assigned',
            ])->values()]);
        }
        $selectedId = $filters['student_id'] ?? ($students->total() === 1 ? $students->first()->id : null);
        $selectedStudent = $selectedId ? (clone $members)->with('promotionHistories')->find($selectedId) : null;
        $installments = collect();
        $selectedCycle = $selectedStatus = $class = null;
        if ($selectedStudent) {
            // A cashier can settle this student's earlier class dues without listing former members in a class roster.
            $classIds = $selectedStudent->promotionHistories->pluck('from_class_room_id')
                ->merge($selectedStudent->promotionHistories->pluck('to_class_room_id'))
                ->push($selectedStudent->class_room_id)->filter()->unique();
            $schedules = ClassFeeSchedule::with('classRoom')->whereIn('class_room_id', $classIds)->where('fee_type', $type)->get();
            $from = $filters['all_years'] && $schedules->isNotEmpty()
                ? $schedules->min('first_due_date')->copy() : Carbon::create($year, 1, 1);
            $tracker = $fees->tracker($schedules, collect([$selectedStudent]), $from, Carbon::create($year, 12, 31));
            foreach ($tracker['rows']->first()['installments'] as $key => $status) {
                if ($status['eligible'] || $status['paid'] > 0 || $status['pending'] > 0) {
                    $installments->put($key, ['cycle' => $tracker['cycles'][$key], 'status' => $status]);
                }
            }
            $installments = $installments->sortBy(fn ($entry) => $entry['cycle']['due']->timestamp);
            $selectedKey = $filters['cycle'] ?? $installments->search(fn ($entry) => $entry['status']['collectable'] > 0);
            $selected = $installments->get($selectedKey);
            if ($selected && $selected['status']['eligible']) {
                $selectedCycle = $selected['cycle'];
                $selectedStatus = $selected['status'];
                $class = $selectedCycle['schedule']->classRoom;
            }
        }

        return view('class-fees.collect', compact('type', 'filters', 'year', 'classes', 'students', 'selectedStudent', 'installments', 'selectedCycle', 'selectedStatus', 'class') + [
            'feeLabel' => ClassFeeSchedule::TYPES[$type], 'submissionId' => (string) Str::uuid(),
            'collectionMode' => true,
            'categories' => RevenueCategory::where('active', true)->orderBy('name')->get(),
            'autogenerate' => app('settings')->get('billing.revenue.autogenerate', '1') === '1',
            'nextBillNumberPreview' => app(BillNumberService::class)->peekNextRevenueBillNumber(),
        ]);
    }
}
