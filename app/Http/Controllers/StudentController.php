<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Student::query()->with('classRoom');

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', $q)
                    ->orWhere('phone', 'like', $q)
                    ->orWhere('admission_number', 'like', $q)
                    ->orWhere('class', 'like', $q)
                    ->orWhere('year', 'like', $q)
                    ->orWhereHas('classRoom', function ($sub2) use ($q) {
                        $sub2->where('name', 'like', $q);
                    });
            });
        }

        return view('students.index', [
            'students' => $query->orderBy('name')->paginate(15)->withQueryString(),
            'filters' => $request->only(['q']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('students.create', [
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
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admission_number' => ['required', 'string', 'max:50', 'unique:students,admission_number'],
            'name' => ['required', 'string', 'max:120'],
            // Required admission fields
            'first_name' => ['required', 'string', 'max:120'],
            'other_names' => ['nullable', 'string', 'max:255'],
            'name_with_initial' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'parent_address' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date'],
            'use_guardian' => ['nullable', 'boolean'],
            'guardian_name' => ['nullable', 'required_if:use_guardian,1', 'string', 'max:120'],
            'guardian_relationship' => ['nullable', 'required_if:use_guardian,1', 'string', 'max:120'],
            'guardian_phone' => ['nullable', 'required_if:use_guardian,1', 'string', 'max:30'],
            'joining_date' => ['nullable', 'date'],
            'fee_start_date' => ['nullable', 'date'],
            'year' => ['nullable', 'string', 'max:20'],
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'active' => ['nullable', 'in:0,1'],
            // Admission extras
            'religion' => ['required', 'string', 'max:60'],
            'desired_class' => ['required', 'string', 'max:120'],
            'medical_history' => ['nullable', 'string'],
            'long_term_medication' => ['required', 'in:0,1'],
            'learning_disabilities' => ['required', 'in:0,1'],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'previous_grade' => ['nullable', 'string', 'max:120'],
            'siblings' => ['nullable', 'string'],
            'has_siblings_in_college' => ['required', 'in:0,1'],
            'father_name_with_initial' => ['nullable', 'required_if:use_guardian,0', 'string', 'max:120'],
            'father_nic_passport' => ['nullable', 'string', 'max:60'],
            'father_religion' => ['nullable', 'string', 'max:60'],
            'father_nationality' => ['nullable', 'string', 'max:60'],
            'father_occupation' => ['nullable', 'string', 'max:120'],
            'father_phone' => ['nullable', 'string', 'max:30'],
            'father_whatsapp' => ['nullable', 'string', 'max:30'],
            'father_office_phone' => ['nullable', 'string', 'max:30'],
            'father_emergency_number' => ['nullable', 'string', 'max:30'],
            'mother_name_with_initial' => ['nullable', 'string', 'max:120'],
            'mother_nic_passport' => ['nullable', 'string', 'max:60'],
            'mother_religion' => ['nullable', 'string', 'max:60'],
            'mother_nationality' => ['nullable', 'string', 'max:60'],
            'mother_occupation' => ['nullable', 'string', 'max:120'],
            'mother_phone' => ['nullable', 'string', 'max:30'],
            'mother_whatsapp' => ['nullable', 'string', 'max:30'],
            'mother_office_phone' => ['nullable', 'string', 'max:30'],
            'mother_emergency_number' => ['nullable', 'string', 'max:30'],
            'passport_photo_path' => ['nullable', 'string', 'max:255'],
        ]);

        $yearStart = null;
        $selectedAcademicYear = $request->session()->get(
            'academic_year',
            app('settings')->get('school.academic_year', date('Y').'-'.(date('Y') + 1))
        );
        if (is_string($selectedAcademicYear) && preg_match('/(\d{4})/', $selectedAcademicYear, $m)) {
            $yearStart = (int) $m[1];
        }

        $classRoom = ClassRoom::query()->find($validated['class_room_id']);
        $monthlyFee = (float) ($validated['monthly_fee'] ?? ($classRoom?->monthly_fee ?? 0));

        // Calculate academic year from joining date
        $joiningDate = $validated['joining_date'] ?? date('Y-m-d');
        $year = \Carbon\Carbon::parse($joiningDate)->year;
        // Format as YYYY-YYYY+1 (e.g. 2025-2026)
        $academicYear = $year . '-' . ($year + 1);

        // Calculate due amount based on fee start date
        $dueAmount = $monthlyFee;
        if (!empty($validated['fee_start_date'])) {
            $start = \Carbon\Carbon::parse($validated['fee_start_date'])->startOfDay();
            $now = now();
            if ($now->lt($start)) {
                $months = 0;
            } else {
                $months = $start->diffInMonths($now) + 1; // inclusive of start month
            }
            $dueAmount = $monthlyFee * max(0, $months);
        }

        $student = Student::create([
            'admission_number' => $validated['admission_number'],
            'name' => $validated['name'],
            'first_name' => $validated['first_name'] ?? null,
            'other_names' => $validated['other_names'] ?? null,
            'name_with_initial' => $validated['name_with_initial'] ?? null,
            'address' => $validated['address'] ?? null,
            'parent_address' => $validated['parent_address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'use_guardian' => $validated['use_guardian'] ?? false,
            'guardian_name' => $validated['guardian_name'] ?? null,
            'guardian_relationship' => $validated['guardian_relationship'] ?? null,
            'guardian_phone' => $validated['guardian_phone'] ?? null,
            'joining_date' => $validated['joining_date'] ?? null,
            'fee_start_date' => $validated['fee_start_date'] ?? null,
            'year' => $academicYear,
            'class_room_id' => $validated['class_room_id'],
            'class' => $classRoom?->name,
            'promoted_until_year' => $yearStart,
            'religion' => $validated['religion'] ?? null,
            'desired_class' => $validated['desired_class'] ?? null,
            'medical_history' => $validated['medical_history'] ?? null,
            'long_term_medication' => (bool) ($validated['long_term_medication'] ?? false),
            'learning_disabilities' => (bool) ($validated['learning_disabilities'] ?? false),
            'previous_school' => $validated['previous_school'] ?? null,
            'previous_grade' => $validated['previous_grade'] ?? null,
            'siblings' => $validated['siblings'] ?? null,
            'has_siblings_in_college' => (bool) ($validated['has_siblings_in_college'] ?? false),
            'father_name_with_initial' => $validated['father_name_with_initial'] ?? null,
            'father_nic_passport' => $validated['father_nic_passport'] ?? null,
            'father_religion' => $validated['father_religion'] ?? null,
            'father_nationality' => $validated['father_nationality'] ?? null,
            'father_occupation' => $validated['father_occupation'] ?? null,
            'father_phone' => $validated['father_phone'] ?? null,
            'father_whatsapp' => $validated['father_whatsapp'] ?? null,
            'father_office_phone' => $validated['father_office_phone'] ?? null,
            'father_emergency_number' => $validated['father_emergency_number'] ?? null,
            'mother_name_with_initial' => $validated['mother_name_with_initial'] ?? null,
            'mother_nic_passport' => $validated['mother_nic_passport'] ?? null,
            'mother_religion' => $validated['mother_religion'] ?? null,
            'mother_nationality' => $validated['mother_nationality'] ?? null,
            'mother_occupation' => $validated['mother_occupation'] ?? null,
            'mother_phone' => $validated['mother_phone'] ?? null,
            'mother_whatsapp' => $validated['mother_whatsapp'] ?? null,
            'mother_office_phone' => $validated['mother_office_phone'] ?? null,
            'mother_emergency_number' => $validated['mother_emergency_number'] ?? null,
            'passport_photo_path' => $validated['passport_photo_path'] ?? null,
            'admission_agree' => (bool) ($validated['admission_agree'] ?? false),
            'monthly_fee' => $monthlyFee,
            'due_amount' => $dueAmount,
            'active' => ($validated['active'] ?? '1') === '1',
        ]);

        return redirect()->route('students.show', $student)->with('status', 'Student created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Student $student)
    {
        $payments = Revenue::query()
            ->with(['category'])
            ->where('student_id', $student->id);

        // Optional type filter: monthly | other
        $monthlyCatId = $student->classRoom?->monthly_fee_revenue_category_id;
        $type = $request->string('type');
        if ($type === 'monthly' && $monthlyCatId) {
            $payments->where('revenue_category_id', $monthlyCatId);
        } elseif ($type === 'other' && $monthlyCatId) {
            $payments->where(function ($q) use ($monthlyCatId) {
                $q->whereNull('revenue_category_id')->orWhere('revenue_category_id', '!=', $monthlyCatId);
            });
        }

        if ($request->filled('from')) {
            $payments->whereDate('paid_at', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $payments->whereDate('paid_at', '<=', $request->string('to'));
        }

        $payments = $payments->orderByDesc('paid_at');

        if ($request->boolean('download')) {
            $rows = $payments->get();

            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Bill No', 'Date', 'Category', 'Amount', 'Notes']);
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->bill_no,
                        optional($row->paid_at)->format('Y-m-d'),
                        $row->category?->name,
                        $row->amount,
                        $row->notes,
                    ]);
                }
                fclose($out);
            }, 'student-payments-'.$student->id.'.csv', [
                'Content-Type' => 'text/csv',
            ]);
        }

        // Due breakdown
        $monthlyFee = (float) $student->monthly_fee;
        $monthsDue = 1;
        $cycles = [];
        if ($student->fee_start_date) {
            $start = \Carbon\Carbon::parse($student->fee_start_date)->startOfDay();
            $now = now();
            $monthsDue = $now->lt($start) ? 0 : (int) ($start->diffInMonths($now) + 1); // ensure integer cycles

            for ($i = 0; $i < $monthsDue; $i++) {
                $s = $start->copy()->addMonthsNoOverflow($i);
                $e = $start->copy()->addMonthsNoOverflow($i + 1);
                $cycles[] = [
                    'start' => $s->format('Y-m-d'),
                    'end' => $e->format('Y-m-d'),
                    'inProgress' => $now->betweenIncluded($s, $e),
                ];
            }
            // Normalize months to cycles length to avoid drift
            $monthsDue = count($cycles);
        }
        $expectedDue = $monthlyFee * max(0, (int) $monthsDue);

        $paidMonthlyFee = 0.0;
        if ($monthlyCatId) {
            $paidMonthlyFee = (float) Revenue::query()
                ->where('student_id', $student->id)
                ->where('revenue_category_id', $monthlyCatId)
                ->sum('amount');
        }
        $netDue = max(0.0, ($monthlyFee * max(0, (int) $monthsDue)) - $paidMonthlyFee);

        $history = \App\Models\StudentPromotionHistory::query()
            ->with(['fromClassRoom','toClassRoom','performer'])
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        // Recent activity: last payments + promotions
        $recentRevenues = Revenue::query()
            ->with('category')
            ->where('student_id', $student->id)
            ->orderByDesc('paid_at')
            ->limit(5)
            ->get()
            ->map(function ($p) {
                return [
                    'date' => $p->paid_at ?? $p->created_at,
                    'type' => 'payment',
                    'title' => 'Payment',
                    'subtitle' => ($p->category?->name ?: 'Other').' • '.number_format((float) $p->amount, 2),
                ];
            })->toBase();
        $recentPromotions = \App\Models\StudentPromotionHistory::query()
            ->with(['fromClassRoom','toClassRoom'])
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($h) {
                $dir = $h->action === 'promote' ? 'Promoted' : 'Demoted';
                return [
                    'date' => $h->created_at,
                    'type' => $h->action,
                    'title' => $dir,
                    'subtitle' => ($h->fromClassRoom?->name ?: '—').' → '.($h->toClassRoom?->name ?: '—'),
                ];
            })->toBase();
        $recentActivities = $recentRevenues->merge($recentPromotions)->sortByDesc('date')->values();

        // Progress metrics
        $monthsDueCount = $student->monthlyCyclesCountToNow();
        $monthsPaidCount = $student->monthlyFeePaidCyclesCount();
        $paidPct = ($monthsDueCount > 0) ? round(($monthsPaidCount / $monthsDueCount) * 100) : 0;

        return view('students.show', [
            'student' => $student,
            'payments' => $payments->paginate(15)->withQueryString(),
            'filters' => $request->only(['from', 'to']) + ['type' => $type],
            'dueBreakdown' => [
                'monthlyFee' => $monthlyFee,
                'startDate' => $student->fee_start_date,
                'monthsDue' => $monthsDue,
                'expectedDue' => $expectedDue,
                'paidMonthlyFee' => $paidMonthlyFee,
                'netDue' => $netDue,
                'cycles' => $cycles,
            ],
            'history' => $history,
            'recentActivities' => $recentActivities,
            'progress' => [
                'monthsDueCount' => $monthsDueCount,
                'monthsPaidCount' => $monthsPaidCount,
                'paidPct' => $paidPct,
            ],
        ]);
    }

    /** Download student statement as PDF */
    public function statement(Request $request, Student $student)
    {
        // Reuse the show() computation in a minimal way
        $payments = Revenue::query()->with(['category'])->where('student_id', $student->id);
        if ($request->filled('from')) $payments->whereDate('paid_at', '>=', $request->string('from'));
        if ($request->filled('to')) $payments->whereDate('paid_at', '<=', $request->string('to'));
        $rows = $payments->orderByDesc('paid_at')->get();

        $monthlyFee = (float) $student->monthly_fee;
        $monthsDue = 0;
        $cycles = [];
        if ($student->fee_start_date) {
            $start = \Carbon\Carbon::parse($student->fee_start_date)->startOfDay();
            $now = now();
            $monthsDue = $now->lt($start) ? 0 : (int) ($start->diffInMonths($now) + 1);
            for ($i = 0; $i < $monthsDue; $i++) {
                $s = $start->copy()->addMonthsNoOverflow($i);
                $e = $start->copy()->addMonthsNoOverflow($i + 1);
                $cycles[] = ['start' => $s->format('Y-m-d'), 'end' => $e->format('Y-m-d')];
            }
        }
        $expectedDue = $monthlyFee * max(0, (int) $monthsDue);
        $monthlyCatId = $student->classRoom?->monthly_fee_revenue_category_id;
        $paidMonthlyFee = 0.0;
        if ($monthlyCatId) {
            $paidMonthlyFee = (float) Revenue::query()
                ->where('student_id', $student->id)
                ->where('revenue_category_id', $monthlyCatId)
                ->sum('amount');
        }

        $summary = [
            'expectedDue' => $expectedDue,
            'paidMonthlyFee' => $paidMonthlyFee,
            'netDue' => max(0.0, $expectedDue - $paidMonthlyFee),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('students.statement', [
            'student' => $student,
            'payments' => $rows,
            'summary' => $summary,
            'cycles' => $cycles,
        ]);

        return $pdf->download('student-statement-'.$student->id.'.pdf');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student): View
    {
        return view('students.edit', [
            'student' => $student,
            'classRooms' => ClassRoom::query()
                ->orderByRaw('level is null')
                ->orderBy('level')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'admission_number' => ['required', 'string', 'max:50', 'unique:students,admission_number,'.$student->id],
            'name' => ['required', 'string', 'max:120'],
            // Required admission fields
            'first_name' => ['required', 'string', 'max:120'],
            'other_names' => ['nullable', 'string', 'max:255'],
            'name_with_initial' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'parent_address' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'gender' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date'],
            'use_guardian' => ['nullable', 'boolean'],
            'guardian_name' => ['nullable', 'required_if:use_guardian,1', 'string', 'max:120'],
            'guardian_relationship' => ['nullable', 'required_if:use_guardian,1', 'string', 'max:120'],
            'guardian_phone' => ['nullable', 'required_if:use_guardian,1', 'string', 'max:30'],
            'joining_date' => ['nullable', 'date'],
            'fee_start_date' => ['nullable', 'date'],
            // 'year' input removed
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'active' => ['nullable', 'in:0,1'],
            // Admission extras
            'religion' => ['required', 'string', 'max:60'],
            'desired_class' => ['required', 'string', 'max:120'],
            'medical_history' => ['nullable', 'string'],
            'long_term_medication' => ['required', 'in:0,1'],
            'learning_disabilities' => ['required', 'in:0,1'],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'previous_grade' => ['nullable', 'string', 'max:120'],
            'siblings' => ['nullable', 'string'],
            'has_siblings_in_college' => ['required', 'in:0,1'],
            'father_name_with_initial' => ['nullable', 'required_if:use_guardian,0', 'string', 'max:120'],
            'father_nic_passport' => ['nullable', 'string', 'max:60'],
            'father_religion' => ['nullable', 'string', 'max:60'],
            'father_nationality' => ['nullable', 'string', 'max:60'],
            'father_occupation' => ['nullable', 'string', 'max:120'],
            'father_phone' => ['nullable', 'string', 'max:30'],
            'father_whatsapp' => ['nullable', 'string', 'max:30'],
            'father_office_phone' => ['nullable', 'string', 'max:30'],
            'father_emergency_number' => ['nullable', 'string', 'max:30'],
            'mother_name_with_initial' => ['nullable', 'string', 'max:120'],
            'mother_nic_passport' => ['nullable', 'string', 'max:60'],
            'mother_religion' => ['nullable', 'string', 'max:60'],
            'mother_nationality' => ['nullable', 'string', 'max:60'],
            'mother_occupation' => ['nullable', 'string', 'max:120'],
            'mother_phone' => ['nullable', 'string', 'max:30'],
            'mother_whatsapp' => ['nullable', 'string', 'max:30'],
            'mother_office_phone' => ['nullable', 'string', 'max:30'],
            'mother_emergency_number' => ['nullable', 'string', 'max:30'],
            'passport_photo_path' => ['nullable', 'string', 'max:255'],
        ]);

        $classRoom = ClassRoom::query()->find($validated['class_room_id']);
        $monthlyFee = (float) ($validated['monthly_fee'] ?? ($classRoom?->monthly_fee ?? 0));

        // Recompute due based on fee_start_date
        $dueAmount = $monthlyFee;
        if (!empty($validated['fee_start_date'])) {
            $start = \Carbon\Carbon::parse($validated['fee_start_date'])->startOfDay();
            $now = now();
            if ($now->lt($start)) {
                $months = 0;
            } else {
                $months = $start->diffInMonths($now) + 1;
            }
            $dueAmount = $monthlyFee * max(0, $months);
        }

        $student->update([
            'admission_number' => $validated['admission_number'],
            'name' => $validated['name'],
            'first_name' => $validated['first_name'] ?? $student->first_name,
            'other_names' => $validated['other_names'] ?? $student->other_names,
            'name_with_initial' => $validated['name_with_initial'] ?? $student->name_with_initial,
            'address' => $validated['address'] ?? null,
            'parent_address' => $validated['parent_address'] ?? $student->parent_address,
            'phone' => $validated['phone'] ?? null,
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            'gender' => $validated['gender'] ?? $student->gender,
            'date_of_birth' => $validated['date_of_birth'] ?? $student->date_of_birth,
            'use_guardian' => $validated['use_guardian'] ?? false,
            'guardian_name' => $validated['guardian_name'] ?? null,
            'guardian_relationship' => $validated['guardian_relationship'] ?? null,
            'guardian_phone' => $validated['guardian_phone'] ?? null,
            'joining_date' => $validated['joining_date'] ?? null,
            'fee_start_date' => $validated['fee_start_date'] ?? $student->fee_start_date,
            'year' => isset($validated['joining_date']) 
                ? (\Carbon\Carbon::parse($validated['joining_date'])->year . '-' . (\Carbon\Carbon::parse($validated['joining_date'])->year + 1))
                : $student->year,
            'class_room_id' => $validated['class_room_id'],
            'class' => $classRoom?->name,
            'religion' => $validated['religion'] ?? $student->religion,
            'desired_class' => $validated['desired_class'] ?? $student->desired_class,
            'medical_history' => $validated['medical_history'] ?? $student->medical_history,
            'long_term_medication' => (bool) ($validated['long_term_medication'] ?? $student->long_term_medication),
            'learning_disabilities' => (bool) ($validated['learning_disabilities'] ?? $student->learning_disabilities),
            'previous_school' => $validated['previous_school'] ?? $student->previous_school,
            'previous_grade' => $validated['previous_grade'] ?? $student->previous_grade,
            'siblings' => $validated['siblings'] ?? $student->siblings,
            'has_siblings_in_college' => (bool) ($validated['has_siblings_in_college'] ?? $student->has_siblings_in_college),
            'father_name_with_initial' => $validated['father_name_with_initial'] ?? $student->father_name_with_initial,
            'father_nic_passport' => $validated['father_nic_passport'] ?? $student->father_nic_passport,
            'father_religion' => $validated['father_religion'] ?? $student->father_religion,
            'father_nationality' => $validated['father_nationality'] ?? $student->father_nationality,
            'father_occupation' => $validated['father_occupation'] ?? $student->father_occupation,
            'father_phone' => $validated['father_phone'] ?? $student->father_phone,
            'father_whatsapp' => $validated['father_whatsapp'] ?? $student->father_whatsapp,
            'father_office_phone' => $validated['father_office_phone'] ?? $student->father_office_phone,
            'father_emergency_number' => $validated['father_emergency_number'] ?? $student->father_emergency_number,
            'mother_name_with_initial' => $validated['mother_name_with_initial'] ?? $student->mother_name_with_initial,
            'mother_nic_passport' => $validated['mother_nic_passport'] ?? $student->mother_nic_passport,
            'mother_religion' => $validated['mother_religion'] ?? $student->mother_religion,
            'mother_nationality' => $validated['mother_nationality'] ?? $student->mother_nationality,
            'mother_occupation' => $validated['mother_occupation'] ?? $student->mother_occupation,
            'mother_phone' => $validated['mother_phone'] ?? $student->mother_phone,
            'mother_whatsapp' => $validated['mother_whatsapp'] ?? $student->mother_whatsapp,
            'mother_office_phone' => $validated['mother_office_phone'] ?? $student->mother_office_phone,
            'mother_emergency_number' => $validated['mother_emergency_number'] ?? $student->mother_emergency_number,
            'passport_photo_path' => $validated['passport_photo_path'] ?? $student->passport_photo_path,
            'admission_agree' => (bool) ($validated['admission_agree'] ?? $student->admission_agree),
            'monthly_fee' => $monthlyFee,
            'due_amount' => $dueAmount,
            'active' => ($validated['active'] ?? '1') === '1',
        ]);

        return back()->with('status', 'Student updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return redirect()->route('students.index')->with('status', 'Student deleted.');
    }

    /**
     * Lightweight JSON search for students by admission number, name, phone, with optional class filter.
     */
    public function search(Request $request)
    {
        // If an explicit id is provided, return a single detailed record
        $id = $request->query('id');
        if ($id) {
            $s = Student::query()->with('classRoom')->find((int) $id);
            if (! $s) {
                return response()->json(['results' => []]);
            }

            // Calculate due months
            $cycles = $s->monthlyCyclesToNow();
            $paidCount = $s->monthlyFeePaidCyclesCount();
            $dueMonths = [];
            foreach ($cycles as $index => $cycle) {
                if ($index >= $paidCount) {
                    $dueMonths[] = $cycle['start']->format('F Y');
                }
            }

            $detail = [
                'id' => $s->id,
                'name' => $s->name,
                'admission_number' => $s->admission_number,
                'phone' => $s->phone,
                'class' => $s->classRoom?->name ?? $s->class,
                'monthly_fee' => (float) ($s->monthly_fee ?? 0),
                'due_amount' => (float) ($s->computed_due_amount ?? $s->due_amount ?? 0),
                'monthly_category_id' => $s->classRoom?->monthly_fee_revenue_category_id,
                'due_months' => $dueMonths,
            ];

            return response()->json(['results' => [$detail]]);
        }

        $q = trim((string) $request->query('q', ''));
        $classRoomId = $request->query('class_room_id');
        $limit = (int) $request->query('limit', 20);
        if ($limit < 1) { $limit = 1; }
        if ($limit > 20) { $limit = 20; }

        $query = Student::query()->where('active', true)->with('classRoom');
        if ($classRoomId) {
            $query->where('class_room_id', (int) $classRoomId);
        }
        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($sub) use ($like) {
                $sub->where('admission_number', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        $results = $query->orderBy('name')->limit($limit)->get()->map(function (Student $s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'admission_number' => $s->admission_number,
                'phone' => $s->phone,
                'class' => $s->classRoom?->name ?? $s->class,
            ];
        });

        return response()->json(['results' => $results]);
    }
}
