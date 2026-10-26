<?php

namespace App\Http\Controllers;

use App\Models\ClassFeeSchedule;
use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\Student;
use App\Services\Billing\ClassFeePaymentService;
use App\Services\Billing\ClassFeeReversalService;
use App\Services\Billing\ClassFeeService;
use App\Support\ClassFeeRegisterFilters;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ClassFeeController extends Controller
{
    public function index(Request $request, ClassFeeService $fees, ClassFeeReversalService $reversals): View|Response
    {
        if ($request->boolean('pdf') || $request->input('download') === 'csv') {
            abort_unless($request->user()->can('reports.download'), 403);
        }
        $filters = $request->validate(ClassFeeRegisterFilters::rules() + [
            'student_id' => ['nullable', 'integer'],
            'cycle' => ['nullable', 'string', 'regex:/^\d+:\d{4}-\d{2}-\d{2}$/'],
            'fragment' => ['nullable', 'boolean'], 'action_panel' => ['nullable', 'in:pay,unpaid'],
            'page' => ['nullable', 'integer', 'min:1', 'max:200000'], 'download' => ['nullable', 'in:csv'],
        ]);
        $studentList = ($filters['view'] ?? 'overview') === 'students';
        if ($request->filled('action_panel')) {
            abort_unless($studentList && $request->user()->can($filters['action_panel'] === 'pay' ? 'revenue.add' : 'revenue.delete'), 403);
        }
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
            'include_former' => (int) $request->boolean('include_former'),
        ];
        $filters = array_replace($filters, $selectionQuery, ['selection_scope' => $scope]);
        $schedules = $class ? $rulesFor($class) : collect();
        $allStudents = Student::with('promotionHistories')
            ->when($class, fn ($q) => $q->where(fn ($q) => $q->where('class_room_id', $class->id)
                ->when($request->boolean('include_former'), fn ($q) => $q->orWhereHas('promotionHistories', fn ($q) => $q->where('from_class_room_id', $class->id)->orWhere('to_class_room_id', $class->id)))))
            ->when(! $request->boolean('include_inactive'), fn ($q) => $q->where('active', true)->where('alumni', false))
            ->orderBy('name')->get();
        if ($studentList && ! empty($filters['q'])) {
            $needle = mb_strtolower($filters['q']);
            $allStudents = $allStudents->filter(fn ($student) => str_contains(mb_strtolower($student->name.' '.$student->admission_number), $needle));
        }
        $membersFor = fn ($option) => $allStudents->filter(fn ($student) => (int) $student->class_room_id === (int) $option->id
            || ($request->boolean('include_former') && $student->promotionHistories->contains(fn ($move) => (int) $move->from_class_room_id === (int) $option->id || (int) $move->to_class_room_id === (int) $option->id)));
        $students = $class ? $membersFor($class) : collect();
        $tracker = $fees->tracker($schedules, $students, $fromFor($schedules), $to, $selectedExamDates);
        $allRows = $tracker['rows'];
        $tracker = $this->filterTracker($tracker, $filters, $studentList);
        $classSummaries = collect();
        if (! $class) {
            foreach ($classes as $option) {
                $rules = $rulesFor($option);
                if ($rules->isEmpty() && ! $studentList) {
                    continue;
                }
                $members = $membersFor($option);
                $summary = $this->filterTracker($fees->tracker($rules, $members, $fromFor($rules), $to, $selectedExamDates), $filters, $studentList);
                if ($summary['rows']->isEmpty() && ($members->isNotEmpty() || ! empty($filters['q']) || ($filters['status'] ?? 'all') !== 'all')) {
                    continue;
                }
                $classSummaries->push(['class' => $option, 'schedules' => $rules] + $summary);
            }
            if ($studentList && $allStudents->whereNull('class_room_id')->isNotEmpty()) {
                $summary = $this->filterTracker($fees->tracker(collect(), $allStudents->whereNull('class_room_id'), $fromFor(collect()), $to), $filters, true);
                if ($summary['rows']->isNotEmpty()) {
                    $classSummaries->push(['class' => null, 'schedules' => collect()] + $summary);
                }
            }
            $tracker['paid'] = (float) $classSummaries->sum('paid');
            $tracker['due'] = (float) $classSummaries->sum('due');
            $tracker['unpaid_students'] = $classSummaries->flatMap(fn ($summary) => $summary['rows']->where('due', '>', 0)->pluck('student.id'))->unique()->count();
        }
        $dueBreakdown = $fees->dueBreakdown($class ? collect([['schedules' => $schedules] + $tracker]) : $classSummaries);
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
        $sections = $class ? collect([['class' => $class, 'schedules' => $schedules] + $tracker]) : $classSummaries;
        $registerQuery = ClassFeeRegisterFilters::query($filters);
        $studentCount = $sections->flatMap(fn ($section) => $section['rows']->pluck('student.id'))->unique()->count();
        $entries = $sections->flatMap(fn ($section, $index) => $section['rows']->map(fn ($row) => ['section' => $index, 'row' => $row]))->values();
        $perPage = (int) ($filters['per_page'] ?? 25);
        $page = min((int) ($filters['page'] ?? 1), max(1, (int) ceil($entries->count() / $perPage)));
        $paginator = new LengthAwarePaginator($entries->forPage($page, $perPage)->values(), $entries->count(), $perPage, $page,
            ['path' => route('class-fees.index'), 'query' => $registerQuery]);
        $displaySections = $sections->map(function ($section, $index) use ($paginator) {
            $section['rows'] = $paginator->getCollection()->where('section', $index)->pluck('row');

            return $section;
        })->filter(fn ($section) => $section['rows']->isNotEmpty());
        $reportTitle = $type === 'exam' && $includeFacility ? 'Exam & Facility Fees' : ClassFeeSchedule::TYPES[$type];
        $data = compact('type', 'year', 'classes', 'class', 'classSummaries', 'tracker', 'rows', 'students', 'selectedStudent', 'selectedCycle', 'selectedStatus', 'unassigned', 'examOptions', 'examLabels', 'selectedExamDates', 'includeFacility', 'selectionQuery', 'reportTitle', 'studentList', 'sections', 'registerQuery', 'studentCount', 'paginator', 'displaySections') + [
            'filters' => $filters, 'submissionId' => (string) Str::uuid(), 'dueBreakdown' => $dueBreakdown,
        ];
        if ($request->filled('action_panel')) {
            abort_unless($selectedStudent && $selectedCycle && $selectedStatus, 404);
            $preview = $reversals->preview($selectedStatus['receipts']);

            return view('class-fees.partials.student-action', $data + ['action' => $filters['action_panel'], 'preview' => $preview]);
        }
        if ($request->input('download') === 'csv') {
            return $this->csv($sections);
        }
        if ($request->boolean('pdf')) {
            $pdf = Pdf::loadView('class-fees.pdf', $data + [
                'sections' => $sections,
                'schoolName' => app('settings')->get('school.name', config('app.name')),
                'schoolAddress' => app('settings')->get('school.address', ''),
            ])->setPaper('a4', 'landscape')->setOption('isRemoteEnabled', false);
            $pdf->render();
            $pdf->getDomPDF()->getCanvas()->page_text(715, 570, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 8, [0.4, 0.45, 0.5]);

            return $pdf->download('class-fees-'.($filters['status'] ?? 'all').'-'.($request->boolean('all_years') ? 'all-years' : $year).'.pdf');
        }

        if ($studentList && $request->boolean('fragment')) {
            return response()->json([
                'html' => view('class-fees.partials.students-results', $data)->render(),
                'terms_html' => view('class-fees.partials.exam-selection', $data)->render(),
                'scope' => $filters['selection_scope'], 'url' => route('class-fees.index', $registerQuery + ['page' => $page]),
                'count' => $studentCount,
            ]);
        }

        return view($studentList ? 'class-fees.students' : 'class-fees.index', $data);
    }

    private function csv($sections): Response
    {
        return response()->streamDownload(function () use ($sections) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            $safe = fn ($value) => is_string($value) && preg_match('/^[=+@\-\t\r]/', $value) ? "'".$value : $value;
            $write = fn ($values) => fputcsv($stream, array_map($safe, $values), ',', '"', '');
            $write(['Student', 'Admission number', 'Class', 'Fee', 'Term', 'Starting / due date', 'Ending date', 'Fee amount', 'Paid', 'Pending', 'Waived', 'Remaining', 'Due now', 'Status']);
            foreach ($sections as $section) {
                foreach ($section['rows'] as $row) {
                    $identity = [$row['student']->name, $row['student']->admission_number, $section['class']?->name ?? 'No class assigned'];
                    if ($section['cycles']->isEmpty()) {
                        $write(array_merge($identity, ['', '', '', '', 0, 0, 0, 0, 0, 0, 'No selected fees']));
                    }
                    foreach ($section['cycles'] as $key => $cycle) {
                        $status = $row['installments'][$key];
                        $write(array_merge($identity, [ClassFeeSchedule::TYPES[$cycle['schedule']->fee_type], $cycle['term'] ?? '',
                            $cycle['due']->toDateString(), isset($cycle['end']) ? $cycle['end']->toDateString() : '',
                            $status['eligible'] ? $cycle['amount'] : 0, $status['paid'], $status['pending'], $status['waived'],
                            $status['remaining'], $status['due'], $status['status']]));
                    }
                }
            }
            fclose($stream);
        }, 'class-fee-students.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filterTracker(array $tracker, array $filters, bool $keepEmpty = false): array
    {
        $rows = $tracker['cycles']->isEmpty() && ! $keepEmpty ? collect() : $tracker['rows']->filter(fn ($row) => $this->matchesFilters($row, $filters));
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

    public function store(Request $request, ClassRoom $classroom, string $type, ClassFeePaymentService $payments): RedirectResponse|JsonResponse
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
            'cheque_student_name' => ['nullable', 'string', 'max:120'],
            'bill_no' => ['nullable', 'string', 'max:50'],
            'view_receipt' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'return_to' => ['nullable', 'in:collection,students'],
            'search_class_room_id' => ['nullable', 'integer', 'exists:class_rooms,id'],
            'search_q' => ['nullable', 'string', 'max:100'],
            'search_year' => ['nullable', 'integer', 'min:2000', 'max:2099'],
            'include_inactive' => ['nullable', 'boolean'],
            'include_former' => ['nullable', 'boolean'],
            'all_years' => ['nullable', 'boolean'],
        ] + ClassFeeRegisterFilters::returnRules());
        if (($data['return_to'] ?? null) === 'collection') {
            $data['record_revenue'] = true;
        }
        $revenue = $payments->collect($classroom, $type, $data, $request->user());

        if (($data['return_to'] ?? null) === 'students') {
            $message = $revenue instanceof Revenue ? 'Payment recorded. Bill: '.$revenue->bill_no : 'Historical payment recorded without adding income.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'receipt_url' => $revenue instanceof Revenue ? route('revenue.items.receipt', $revenue) : null]);
            }

            return redirect()->route('class-fees.index', array_replace(ClassFeeRegisterFilters::query($data['return_filters'] ?? []), ['view' => 'students']))
                ->with('status', $message);
        }

        $destination = ($data['return_to'] ?? null) === 'collection' ? 'revenue.items.create' : 'class-fees.index';
        $parameters = $destination === 'revenue.items.create' ? [
            'quick' => $type, 'class_room_id' => $data['search_class_room_id'] ?? null,
            'q' => $data['search_q'] ?? null, 'year' => $data['search_year'] ?? $revenue->class_fee_due_date->year,
            'student_id' => $data['student_id'], 'cycle' => $data['cycle'],
            'include_inactive' => $request->boolean('include_inactive'), 'all_years' => $request->boolean('all_years'),
        ] : [
            'type' => $type, 'class_room_id' => $classroom->id, 'year' => $revenue->class_fee_due_date->year,
            'student_id' => $data['student_id'], 'cycle' => $data['cycle'],
            'include_former' => $request->boolean('include_former') || (int) $revenue->student->class_room_id !== (int) $classroom->id,
        ];

        if ($request->boolean('view_receipt') && $revenue instanceof Revenue) {
            return redirect()->route('revenue.items.receipt', $revenue)
                ->with('fee_collection_return_url', route($destination, $parameters));
        }

        return redirect()->route($destination, $parameters)
            ->with('status', $revenue instanceof Revenue ? 'Fee recorded for the selected installment. Bill: '.$revenue->bill_no : 'Historical payment recorded. Revenue and ledger were not increased.')
            ->with('class_fee_receipt_id', $revenue instanceof Revenue ? $revenue->id : null);
    }
}
