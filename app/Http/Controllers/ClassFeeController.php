<?php

namespace App\Http\Controllers;

use App\Models\ClassFeeSchedule;
use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\Student;
use App\Services\Billing\ClassFeePaymentService;
use App\Services\Billing\ClassFeeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ClassFeeController extends Controller
{
    public function index(Request $request, ClassFeeService $fees): View|Response
    {
        if ($request->boolean('pdf')) {
            abort_unless($request->user()->can('reports.download'), 403);
        }
        $filters = $request->validate([
            'type' => ['nullable', 'in:exam,facility'],
            'class_room_id' => ['nullable', 'integer', 'exists:class_rooms,id'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2099'],
            'status' => ['nullable', 'in:all,paid,unpaid,partial,pending'],
            'q' => ['nullable', 'string', 'max:100'],
            'include_inactive' => ['nullable', 'boolean'],
            'all_years' => ['nullable', 'boolean'],
            'include_facility' => ['nullable', 'boolean'],
            'exam_selection' => ['nullable', 'boolean'],
            'exam_dates' => ['nullable', 'array', 'max:2000'],
            'exam_dates.*' => ['required', 'date_format:Y-m-d', 'distinct'],
            'selection_scope' => ['nullable', 'string', 'max:100'],
            'student_id' => ['nullable', 'integer'],
            'cycle' => ['nullable', 'string', 'regex:/^\d+:\d{4}-\d{2}-\d{2}$/'],
        ]);
        $type = $filters['type'] ?? 'exam';
        $year = (int) ($filters['year'] ?? today()->year);
        $includeFacility = $type === 'facility' || (bool) ($filters['include_facility'] ?? true);
        $classes = ClassRoom::query()->with('feeSchedules')->orderBy('level')->orderBy('name')->get();
        $class = isset($filters['class_room_id']) ? $classes->firstWhere('id', $filters['class_room_id']) : null;
        $scope = implode(':', [$type, $class?->id ?? 'all', $year, (int) $request->boolean('all_years')]);
        $rulesFor = fn ($option) => $option->feeSchedules->filter(fn ($rule) => $rule->fee_type === $type || ($type === 'exam' && $includeFacility && $rule->fee_type === 'facility'));
        $fromFor = fn ($rules) => $request->boolean('all_years') && $rules->isNotEmpty()
            ? $rules->min('first_due_date')->copy() : Carbon::create($year, 1, 1);
        $to = Carbon::create($request->boolean('all_years') ? today()->year : $year, 12, 31);
        $availableRules = ($class ? collect([$class]) : $classes)->flatMap(fn ($option) => $rulesFor($option));
        $examCycles = $type === 'exam' ? $fees->cycles($availableRules->where('fee_type', 'exam'), $fromFor($availableRules), $to) : collect();
        $examOptions = $examCycles->map(fn ($cycle) => $cycle['due']->toDateString())->unique()->sort()->values();
        $examLabels = $examCycles->mapWithKeys(fn ($cycle) => [$cycle['due']->toDateString() => (isset($cycle['term']) ? 'Term '.$cycle['term'].' · ' : '').$cycle['due']->format('d M Y')
            .(isset($cycle['end']) ? ' – '.$cycle['end']->format('d M Y') : '')]);
        $explicitSelection = $request->boolean('exam_selection') || $request->has('exam_dates');
        // Changing class/year starts with all its installments checked; an explicitly empty selection stays empty.
        $sameScope = empty($filters['selection_scope']) || $filters['selection_scope'] === $scope;
        $selectedExamDates = $explicitSelection && $sameScope
            ? $examOptions->intersect($filters['exam_dates'] ?? [])->values()->all() : $examOptions->all();
        $selectionQuery = [
            'include_facility' => (int) $includeFacility, 'exam_selection' => 1, 'exam_dates' => $selectedExamDates,
        ];
        $filters = array_replace($filters, $selectionQuery, ['selection_scope' => $scope]);
        $schedules = $class ? $rulesFor($class) : collect();
        $allStudents = Student::with('promotionHistories')
            ->when($class, fn ($q) => $q->where(fn ($q) => $q->where('class_room_id', $class->id)
                ->orWhereHas('promotionHistories', fn ($q) => $q->where('from_class_room_id', $class->id)->orWhere('to_class_room_id', $class->id))))
            ->when(! $request->boolean('include_inactive'), fn ($q) => $q->where('active', true)->where('alumni', false))
            ->orderBy('name')->get();
        $membersFor = fn ($option) => $allStudents->filter(fn ($student) => (int) $student->class_room_id === (int) $option->id
            || $student->promotionHistories->contains(fn ($move) => (int) $move->from_class_room_id === (int) $option->id || (int) $move->to_class_room_id === (int) $option->id));
        $students = $class ? $membersFor($class) : collect();
        $tracker = $fees->tracker($schedules, $students, $fromFor($schedules), $to, $selectedExamDates);
        $allRows = $tracker['rows'];
        $tracker = $this->filterTracker($tracker, $filters);
        $classSummaries = collect();
        if (! $class) {
            foreach ($classes as $option) {
                $rules = $rulesFor($option);
                if ($rules->isEmpty()) {
                    continue;
                }
                $members = $membersFor($option);
                $summary = $this->filterTracker($fees->tracker($rules, $members, $fromFor($rules), $to, $selectedExamDates), $filters);
                if ($summary['rows']->isEmpty() && ($members->isNotEmpty() || ! empty($filters['q']) || ($filters['status'] ?? 'all') !== 'all')) {
                    continue;
                }
                $classSummaries->push(['class' => $option] + $summary);
            }
            $tracker['paid'] = (float) $classSummaries->sum('paid');
            $tracker['due'] = (float) $classSummaries->sum('due');
            $tracker['unpaid_students'] = $classSummaries->flatMap(fn ($summary) => $summary['rows']->where('due', '>', 0)->pluck('student.id'))->unique()->count();
        }
        $selectedStudentId = old('student_id', $filters['student_id'] ?? null);
        $selectedStudent = is_numeric($selectedStudentId) ? $students->firstWhere('id', (int) $selectedStudentId) : null;
        $selectedCycleKey = old('cycle', $filters['cycle'] ?? null);
        $selectedCycle = is_string($selectedCycleKey) ? $tracker['cycles']->get($selectedCycleKey) : null;
        $selectedStatus = $selectedStudent && $selectedCycle
            ? $allRows->firstWhere('student.id', $selectedStudent->id)['installments'][$selectedCycle['key']] : null;
        $rows = $tracker['rows'];
        $unassigned = $selectedStudent ? Revenue::query()->where('student_id', $selectedStudent->id)
            ->whereHas('category', fn ($q) => $q->where('class_fee_type', $selectedCycle['schedule']->fee_type ?? $type))
            ->whereNull('class_fee_schedule_id')->whereNull('cancelled_at')
            ->where(fn ($q) => $q->whereNull('payment_status')->orWhereIn('payment_status', ['confirmed', 'paid', 'hold', 'pending']))
            ->orderByDesc('paid_at')->get() : collect();
        $reportTitle = $type === 'exam' && $includeFacility ? 'Exam & Facility Fees' : ClassFeeSchedule::TYPES[$type];
        $data = compact('type', 'year', 'classes', 'class', 'classSummaries', 'tracker', 'rows', 'students', 'selectedStudent', 'selectedCycle', 'selectedStatus', 'unassigned', 'examOptions', 'examLabels', 'selectedExamDates', 'includeFacility', 'selectionQuery', 'reportTitle') + [
            'filters' => $filters, 'submissionId' => (string) Str::uuid(),
        ];
        if ($request->boolean('pdf')) {
            $sections = $class ? collect([['class' => $class] + $tracker]) : $classSummaries;
            $pdf = Pdf::loadView('class-fees.pdf', $data + [
                'sections' => $sections,
                'schoolName' => app('settings')->get('school.name', config('app.name')),
                'schoolAddress' => app('settings')->get('school.address', ''),
            ])->setPaper('a4', 'landscape')->setOption('isRemoteEnabled', false);
            $pdf->render();
            $pdf->getDomPDF()->getCanvas()->page_text(715, 570, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 8, [0.4, 0.45, 0.5]);

            return $pdf->download('class-fees-'.($filters['status'] ?? 'all').'-'.($request->boolean('all_years') ? 'all-years' : $year).'.pdf');
        }

        return view('class-fees.index', $data);
    }

    private function filterTracker(array $tracker, array $filters): array
    {
        $rows = $tracker['cycles']->isEmpty() ? collect() : $tracker['rows']->filter(fn ($row) => $this->matchesFilters($row, $filters));
        $tracker['rows'] = $rows;
        $tracker['paid'] = round((float) $rows->sum('paid'), 2);
        $tracker['due'] = round((float) $rows->sum('due'), 2);
        $tracker['unpaid_students'] = $rows->where('due', '>', 0)->count();

        return $tracker;
    }

    private function matchesFilters(array $row, array $filters): bool
    {
        $query = mb_strtolower($filters['q'] ?? '');
        if ($query !== '' && ! str_contains(mb_strtolower($row['student']->name.' '.$row['student']->admission_number), $query)) {
            return false;
        }

        return match ($filters['status'] ?? 'all') {
            'unpaid' => $row['due'] > 0,
            'paid' => $row['due_count'] > 0 && $row['due'] < 0.01,
            'partial' => $row['due'] > 0 && $row['paid'] > 0,
            'pending' => $row['pending'] > 0,
            default => true,
        };
    }

    public function store(Request $request, ClassRoom $classroom, string $type, ClassFeePaymentService $payments): RedirectResponse
    {
        abort_unless(isset(ClassFeeSchedule::TYPES[$type]), 404);
        $data = $request->validate([
            'submission_id' => ['required', 'uuid'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'cycle' => ['required', 'string', 'regex:/^\d+:\d{4}-\d{2}-\d{2}$/'],
            'record_revenue' => ['nullable', 'boolean'],
            'existing_revenue_id' => ['nullable', 'integer', 'exists:revenues,id'],
            'amount' => ['required_without:existing_revenue_id', 'nullable', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'paid_at' => ['required_without:existing_revenue_id', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,cheque'],
            'bank_name' => ['required_if:payment_method,bank_transfer', 'nullable', 'string', 'max:120'],
            'bank_ref_no' => ['nullable', 'string', 'max:120'],
            'cheque_date' => ['required_if:payment_method,cheque', 'nullable', 'date_format:Y-m-d'],
            'cheque_number' => ['required_if:payment_method,cheque', 'nullable', 'string', 'max:100'],
            'cheque_bank' => ['required_if:payment_method,cheque', 'nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $revenue = $payments->collect($classroom, $type, $data, $request->user());

        return redirect()->route('class-fees.index', [
            'type' => $type, 'class_room_id' => $classroom->id, 'year' => $revenue->class_fee_due_date->year,
            'student_id' => $data['student_id'], 'cycle' => $data['cycle'],
        ])->with('status', $revenue instanceof Revenue ? 'Fee recorded for the selected installment. Bill: '.$revenue->bill_no : 'Historical payment recorded. Revenue and ledger were not increased.')
            ->with('class_fee_receipt_id', $revenue instanceof Revenue ? $revenue->id : null);
    }
}
