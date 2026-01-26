<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\RevenueAdjustment;
use App\Models\Student;
use App\Models\StudentPromotionHistory;
use App\Models\StudentMonthlyFeeOverride;
use App\Services\AuditLogger;
use App\Services\Billing\MonthlyFeeAllocator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    private function parseFlexibleDate(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }
        $raw = trim($value);
        if ($raw === '') {
            return null;
        }

        // Prefer explicit formats used by the UI to avoid month/day swaps.
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $raw)) {
            $d = Carbon::createFromFormat('d-m-Y', $raw);
            return $d ? $d->startOfDay() : null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            $d = Carbon::createFromFormat('Y-m-d', $raw);
            return $d ? $d->startOfDay() : null;
        }

        return Carbon::parse($raw)->startOfDay();
    }

    private function computeNetDue(Student $student): float
    {
        if (! $student->fee_start_date) {
            return 0.0;
        }

        $allocator = app(\App\Services\Billing\MonthlyFeeAllocator::class);
        $ledger = $allocator->buildLedger($student, 0);
        if (empty($ledger)) {
            return 0.0;
        }

        $expectedBase = 0.0;
        foreach ($ledger as $m) {
            $expectedBase += (float) ($m['due'] ?? 0);
        }
        $paidMonthlyFeeNet = (float) $student->monthlyFeePaidAmount();
        $waivedMonthlyFee = (float) $student->monthlyFeeWaiverAmount();
        $expectedDueNet = max(0.0, $expectedBase - $waivedMonthlyFee);

        return max(0.0, $expectedDueNet - $paidMonthlyFeeNet);
    }

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

        // Optional status filter: all | active | inactive | alumni
        $status = (string) $request->query('status', 'all');
        if ($status === 'active') {
            $query->where('alumni', false)->where('active', true);
        } elseif ($status === 'inactive') {
            $query->where('alumni', false)->where('active', false);
        } elseif ($status === 'alumni') {
            $query->where('alumni', true);
        } else {
            // Default "all" excludes alumni by default as they have their own section
            $query->where('alumni', false);
        }

        return view('students.index', [
            'students' => $query->orderBy('name')->paginate(15)->withQueryString(),
            'filters' => $request->only(['q']) + ['status' => $status],
        ]);
    }

    /** Dedicated Alumni listing with optional CSV export */
    public function alumni(Request $request)
    {
        $query = Student::query()->with('classRoom')->where('alumni', true);

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

        $leavingDocs = (string) $request->query('leaving_docs', 'all');
        if ($leavingDocs === 'issued') {
            $query->where('leaving_docs_issued', true);
        } elseif ($leavingDocs === 'not_issued') {
            $query->where('leaving_docs_issued', false);
        }

        if ($request->boolean('download')) {
            $rows = $query->orderBy('name')->get(['id','admission_number','name','phone','class_room_id','class','joining_date','leaving_docs_issued']);
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Admission No', 'Student', 'Phone', 'Class', 'Joining Date', 'Leaving Docs Issued']);
                foreach ($rows as $s) {
                    $className = optional($s->classRoom)->name ?? $s->class;
                    fputcsv($out, [
                        $s->admission_number,
                        $s->name,
                        $s->phone,
                        $className,
                        optional($s->joining_date)->format('Y-m-d'),
                        $s->leaving_docs_issued ? 'Yes' : 'No',
                    ]);
                }
                fclose($out);
            }, 'alumni-'.date('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
        }

        return view('students.alumni', [
            'students' => $query->orderBy('name')->paginate(15)->withQueryString(),
            'filters' => $request->only(['q']) + ['leaving_docs' => $leavingDocs],
        ]);
    }

    /** Bulk toggle leaving documents issued for selected alumni */
    public function alumniBulkLeavingDocs(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'value' => ['required', 'in:0,1'],
        ]);

        $ids = array_map('intval', $validated['ids']);
        $val = (bool) ((int) $validated['value']);
        $affected = Student::query()->whereIn('id', $ids)->where('alumni', true)->update([
            'leaving_docs_issued' => $val,
        ]);

        return back()->with('status', ($val ? 'Marked' : 'Unmarked')." leaving documents on {$affected} alumni.");
    }

    /** Per-student toggle leaving documents issued with optional due-warning override */
    public function updateLeavingDocs(Request $request, Student $student): RedirectResponse
    {
        abort_unless($student->alumni, 403);

        $validated = $request->validate([
            'value' => ['required', 'in:0,1'],
            'reason' => ['nullable', 'string', 'max:500'],
            'allow_due' => ['nullable', 'in:0,1'],
        ]);

        $val = (bool) ((string) $validated['value'] === '1');
        $reason = trim((string) ($validated['reason'] ?? ''));
        $allowDue = ((string) ($validated['allow_due'] ?? '0')) === '1';

        // Require a reason when marking as issued.
        if ($val && $reason === '') {
            return back()->withErrors(['reason' => 'Reason is required when issuing leaving documents.']);
        }

        $netDue = $this->computeNetDue($student);
        if ($val && $netDue > 0 && ! $allowDue) {
            return back()->withErrors(['allow_due' => 'Student has a pending due amount. Confirm to proceed with due.']);
        }

        $student->update([
            'leaving_docs_issued' => $val,
        ]);

        app(AuditLogger::class)->log(
            $val ? 'students.leaving_docs_issued' : 'students.leaving_docs_unmarked',
            $student,
            $reason !== '' ? $reason : null,
            [
                'net_due' => $netDue,
                'allow_due' => $allowDue,
            ]
        );

        return back()->with('status', $val ? 'Leaving documents marked as issued.' : 'Leaving documents marked as not issued.');
    }

    /** Mark a student as alumni (left school) with required reason */
    public function markAsAlumni(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $reason = trim((string) $validated['reason']);
        $netDue = $this->computeNetDue($student);

        $fromClassRoomId = $student->class_room_id;
        $academicYear = session('academic_year') ?? $student->year;

        $student->update([
            'alumni' => true,
            'active' => false,
        ]);

        StudentPromotionHistory::create([
            'student_id' => $student->id,
            'from_class_room_id' => $fromClassRoomId,
            'to_class_room_id' => null,
            'action' => 'leave',
            'academic_year' => $academicYear,
            'performed_by' => Auth::id(),
            'notes' => $reason,
        ]);

        app(AuditLogger::class)->log(
            'students.marked_alumni',
            $student,
            $reason,
            [
                'net_due' => $netDue,
            ]
        );

        return back()->with('status', 'Student marked as alumni.');
    }

    /** Re-admit an alumni student and assign a new grade (class room). */
    public function reAdmit(Request $request, Student $student): RedirectResponse
    {
        abort_unless($student->alumni, 403);

        $validated = $request->validate([
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'reason' => ['nullable', 'string', 'max:500'],
            're_admit_date' => ['nullable', 'date'],
        ]);

        $reason = trim((string) $validated['reason']);
        $classRoom = ClassRoom::query()->find((int) $validated['class_room_id']);

        $fromClassRoomId = $student->class_room_id;
        $academicYear = session('academic_year') ?? $student->year;
        $reAdmitDate = !empty($validated['re_admit_date'])
            ? \Carbon\Carbon::parse($validated['re_admit_date'])->toDateString()
            : now()->toDateString();

        $student->update([
            'alumni' => false,
            'active' => true,
            'leaving_docs_issued' => false,
            'class_room_id' => $classRoom?->id,
            'class' => $classRoom?->name,
            'monthly_fee' => (float) ($classRoom?->monthly_fee ?? $student->monthly_fee ?? 0),
            // On re-admission, restart fee cycle from the re-admit date.
            'fee_start_date' => $reAdmitDate,
            'joining_date' => $reAdmitDate,
            'year' => $academicYear,
        ]);

        StudentPromotionHistory::create([
            'student_id' => $student->id,
            'from_class_room_id' => $fromClassRoomId,
            'to_class_room_id' => $classRoom?->id,
            'action' => 'readmit',
            'academic_year' => $academicYear,
            'performed_by' => Auth::id(),
            'notes' => $reason,
        ]);

        app(AuditLogger::class)->log(
            'students.readmitted',
            $student,
            $reason,
            [
                'from_class_room_id' => $fromClassRoomId,
                'to_class_room_id' => $classRoom?->id,
                'academic_year' => $academicYear,
            ]
        );

        return back()->with('status', 'Student re-admitted and grade assigned.');
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
            'admission_number' => ['nullable', 'string', 'max:50', 'unique:students,admission_number'],
            'name' => ['required', 'string', 'max:120'],
            // Required admission fields
            'first_name' => ['required', 'string', 'max:120'],
            'other_names' => ['nullable', 'string', 'max:255'],
            'name_with_initial' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'parent_address' => ['required', 'string', 'max:255'],
            // 'phone' => ['nullable', 'string', 'max:30'],
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
            'religion' => ['required', 'string', 'in:Buddhism,Hinduism,Islam,Christianity,Other'],
            'nationality' => ['nullable', 'string', 'max:60'],
            'desired_class' => ['nullable', 'string', 'max:120'],
            'medical_history' => ['nullable', 'string'],
            'long_term_medication' => ['required', 'in:0,1'],
            'learning_disabilities' => ['required', 'in:0,1'],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'previous_grade' => ['nullable', 'string', 'max:120'],
            'siblings' => ['nullable', 'string'],
            'has_siblings_in_college' => ['required', 'in:0,1'],
            'father_name_with_initial' => ['nullable', 'required_if:use_guardian,0', 'string', 'max:120'],
            'father_nic_passport' => ['nullable', 'string', 'max:60'],
            'father_religion' => ['nullable', 'string', 'in:Buddhism,Hinduism,Islam,Christianity,Other'],
            'father_nationality' => ['nullable', 'string', 'max:60'],
            'father_occupation' => ['nullable', 'string', 'max:120'],
            'father_phone' => ['nullable', 'string', 'max:30'],
            'father_whatsapp' => ['nullable', 'string', 'max:30'],
            'father_office_phone' => ['nullable', 'string', 'max:30'],
            'father_emergency_number' => ['nullable', 'string', 'max:30'],
            'mother_name_with_initial' => ['nullable', 'string', 'max:120'],
            'mother_nic_passport' => ['nullable', 'string', 'max:60'],
            'mother_religion' => ['nullable', 'string', 'in:Buddhism,Hinduism,Islam,Christianity,Other'],
            'mother_nationality' => ['nullable', 'string', 'max:60'],
            'mother_occupation' => ['nullable', 'string', 'max:120'],
            'mother_phone' => ['nullable', 'string', 'max:30'],
            'mother_whatsapp' => ['nullable', 'string', 'max:30'],
            'mother_office_phone' => ['nullable', 'string', 'max:30'],
            'mother_emergency_number' => ['nullable', 'string', 'max:30'],
            'passport_photo_path' => ['nullable', 'string', 'max:255'],
            'hear_about_us' => ['nullable', 'string', 'in:Facebook,Friends,TV,Ads,Other'],
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

        // Parse fee start date (UI uses DD-MM-YYYY) and calculate due amount
        $feeStartDate = $this->parseFlexibleDate($request->input('fee_start_date'));

        $dueAmount = $monthlyFee;
        if ($feeStartDate) {
            $start = $feeStartDate;
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
            'fee_start_date' => $feeStartDate?->toDateString(),
            'year' => $academicYear,
            'class_room_id' => $validated['class_room_id'],
            'class' => $classRoom?->name,
            'promoted_until_year' => $yearStart,
            'religion' => $validated['religion'] ?? null,
            'nationality' => $validated['nationality'] ?? 'Sri Lankan',
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
            'hear_about_us' => $validated['hear_about_us'] ?? null,
            'leaving_docs_issued' => false,
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
            ->withSum('refunds as refunded_amount', 'amount')
            ->withSum('waivers as waived_amount', 'amount')
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
                fputcsv($out, ['Bill No', 'Date', 'Category', 'Amount', 'Refunded', 'Waived', 'Net Collected', 'Notes']);
                foreach ($rows as $row) {
                    $refunded = (float) ($row->refunded_amount ?? 0);
                    $waived = (float) ($row->waived_amount ?? 0);
                    $net = max(0.0, (float) $row->amount - $refunded);
                    fputcsv($out, [
                        $row->bill_no,
                        optional($row->paid_at)->format('Y-m-d'),
                        $row->category?->name,
                        $row->amount,
                        $refunded,
                        $waived,
                        $net,
                        $row->notes,
                    ]);
                }
                fclose($out);
            }, 'student-payments-'.$student->id.'.csv', [
                'Content-Type' => 'text/csv',
            ]);
        }

        // Due breakdown (promotion/demotion aware via MonthlyFeeAllocator)
        $allocator = app(\App\Services\Billing\MonthlyFeeAllocator::class);
        $ledgerForDue = $allocator->buildLedger($student, 0);

        $monthsDue = count($ledgerForDue);
        $expectedBase = 0.0;
        foreach ($ledgerForDue as $m) {
            $expectedBase += (float) ($m['due'] ?? 0);
        }

        $paidMonthlyFeeNet = (float) $student->monthlyFeePaidAmount();
        $waivedMonthlyFee = (float) $student->monthlyFeeWaiverAmount();
        $expectedDueNet = max(0.0, $expectedBase - $waivedMonthlyFee);
        $netDue = max(0.0, $expectedDueNet - $paidMonthlyFeeNet);

        $monthlyFee = (float) ($ledgerForDue[now()->format('Y-m')]['due'] ?? ($student->monthly_fee ?? 0));

        $cycles = [];
        if ($student->fee_start_date) {
            $start = \Carbon\Carbon::parse($student->fee_start_date)->startOfDay();
            $now = now();
            $count = $now->lt($start) ? 0 : (int) ($start->diffInMonths($now) + 1);
            for ($i = 0; $i < $count; $i++) {
                $s = $start->copy()->addMonthsNoOverflow($i);
                $e = $start->copy()->addMonthsNoOverflow($i + 1);
                $cycles[] = [
                    'start' => $s->format('Y-m-d'),
                    'end' => $e->format('Y-m-d'),
                    'inProgress' => $now->betweenIncluded($s, $e),
                ];
            }
        }

        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();
        $recentFeeChange = StudentPromotionHistory::query()
            ->with(['fromClassRoom:id,monthly_fee', 'toClassRoom:id,monthly_fee'])
            ->where('student_id', $student->id)
            ->whereIn('action', ['promote', 'demote'])
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->orderByDesc('created_at')
            ->first();

        $currentMonthOverride = StudentMonthlyFeeOverride::query()
            ->where('student_id', $student->id)
            ->where('year', (int) $currentMonthStart->format('Y'))
            ->where('month', (int) $currentMonthStart->format('n'))
            ->first();

        $feeChoice = [
            'enabled' => (bool) $recentFeeChange,
            'oldFee' => (float) ($recentFeeChange?->fromClassRoom?->monthly_fee ?? 0),
            'newFee' => (float) ($recentFeeChange?->toClassRoom?->monthly_fee ?? 0),
            'overrideFee' => (float) ($currentMonthOverride?->fee_amount ?? 0),
            'year' => (int) $currentMonthStart->format('Y'),
            'month' => (int) $currentMonthStart->format('n'),
        ];

        $history = \App\Models\StudentPromotionHistory::query()
            ->with(['fromClassRoom','toClassRoom','performer'])
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'promotion_page')
            ->withQueryString();

        $adjustments = RevenueAdjustment::query()
            ->with(['revenue.category', 'creator'])
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'adjustments_page')
            ->withQueryString();

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
        
        // Use MonthlyFeeAllocator for accurate paid count
        $ledger = $ledgerForDue;
        $monthsPaidCount = 0;
        foreach ($ledger as $m) {
            if ($m['status'] === 'paid') {
                $monthsPaidCount++;
            } elseif ($m['status'] === 'partially_paid') {
                // Count partial as fractional? Or just ignore for "Paid Months" count?
                // Let's count it as fractional based on amount
                if ($m['due'] > 0) {
                    $monthsPaidCount += ($m['paid'] / $m['due']);
                }
            }
        }
        // Round to 1 decimal for display if needed, or just floor/ceil?
        // The UI expects an integer for "Paid Months X / Y".
        // Let's floor it to be safe, or maybe just count fully paid months.
        // The original logic was floor(total / fee).
        // Let's stick to fully paid count for the integer display, but maybe show partials in the tracker.
        $monthsPaidCount = 0;
        foreach ($ledger as $m) {
             if ($m['status'] === 'paid') $monthsPaidCount++;
        }
        
        $paidPct = ($monthsDueCount > 0) ? round(($monthsPaidCount / $monthsDueCount) * 100) : 0;

        // Seminar enrollments & payments history for this student
        $seminarEnrollments = \App\Models\SeminarStudent::query()
            ->with('seminar')
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'seminars_page')
            ->withQueryString();

        return view('students.show', [
            'student' => $student,
            'payments' => $payments->paginate(15, ['*'], 'payments_page')->withQueryString(),
            'filters' => $request->only(['from', 'to']) + ['type' => $type],
            'classRooms' => ClassRoom::query()->orderBy('name')->get(['id', 'name']),
            'dueBreakdown' => [
                'monthlyFee' => $monthlyFee,
                'startDate' => $student->fee_start_date,
                'monthsDue' => $monthsDue,
                'expectedDue' => $expectedDueNet,
                'paidMonthlyFee' => $paidMonthlyFeeNet,
                'netDue' => $netDue,
                'waivedMonthlyFee' => $waivedMonthlyFee,
                'cycles' => $cycles,
            ],
            'feeChoice' => $feeChoice,
            'history' => $history,
            'adjustments' => $adjustments,
            'recentActivities' => $recentActivities,
            'seminarEnrollments' => $seminarEnrollments,
            'progress' => [
                'monthsDueCount' => $monthsDueCount,
                'monthsPaidCount' => $monthsPaidCount,
                'paidPct' => $paidPct,
            ],
        ]);
    }

    public function setCurrentMonthFee(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'choice' => ['required', 'in:old,new'],
            'year' => ['required', 'integer'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $monthStart = now()->startOfMonth();
        if ((int) $validated['year'] !== (int) $monthStart->format('Y') || (int) $validated['month'] !== (int) $monthStart->format('n')) {
            return back()->with('status', 'Only the current month can be selected.');
        }

        $recentFeeChange = StudentPromotionHistory::query()
            ->with(['fromClassRoom:id,monthly_fee', 'toClassRoom:id,monthly_fee'])
            ->where('student_id', $student->id)
            ->whereIn('action', ['promote', 'demote'])
            ->whereBetween('created_at', [$monthStart, now()->endOfMonth()])
            ->orderByDesc('created_at')
            ->first();

        if (! $recentFeeChange) {
            return back()->with('status', 'No promotion/demotion found for this month.');
        }

        $oldFee = (float) ($recentFeeChange->fromClassRoom?->monthly_fee ?? 0);
        $newFee = (float) ($recentFeeChange->toClassRoom?->monthly_fee ?? 0);
        $fee = $validated['choice'] === 'new' ? $newFee : $oldFee;

        if ($fee <= 0) {
            return back()->with('status', 'Monthly fee is not set for old/new class.');
        }

        StudentMonthlyFeeOverride::query()->updateOrCreate(
            [
                'student_id' => $student->id,
                'year' => (int) $monthStart->format('Y'),
                'month' => (int) $monthStart->format('n'),
            ],
            [
                'fee_amount' => $fee,
                'set_by' => $request->user()?->id,
            ]
        );

        return back()->with('status', 'Current month fee updated.');
    }

    /** Download student statement as PDF */
    public function statement(Request $request, Student $student)
    {
        // Reuse the show() computation in a minimal way
        $payments = Revenue::query()->with(['category'])->where('student_id', $student->id);
        if ($request->filled('from')) $payments->whereDate('paid_at', '>=', $request->string('from'));
        if ($request->filled('to')) $payments->whereDate('paid_at', '<=', $request->string('to'));
        $rows = $payments->orderByDesc('paid_at')->get();

        $allocator = app(\App\Services\Billing\MonthlyFeeAllocator::class);
        $ledger = $allocator->buildLedger($student, 0);
        $expectedDue = 0.0;
        foreach ($ledger as $m) {
            $expectedDue += (float) ($m['due'] ?? 0);
        }

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

    /** Download student admission form as PDF */
    public function admission(Request $request, Student $student)
    {
        $schoolName = (string) app('settings')->get('school.name', config('app.name'));
        $logoPath = (string) app('settings')->get('school.logo', '');

        $logoDataUri = null;
        if ($logoPath !== '') {
            // Settings stores a path in the "public" disk (storage/app/public/...)
            $abs = storage_path('app/public/'.$logoPath);
            if (is_file($abs)) {
                $mime = @mime_content_type($abs) ?: 'image/png';
                $logoDataUri = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($abs));
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('students.admission_pdf', [
            'student' => $student,
            'schoolName' => $schoolName,
            'schoolLogoDataUri' => $logoDataUri,
            'generatedAt' => now(),
        ])->setPaper('A4');

        return $pdf->download('student-admission-'.$student->id.'.pdf');
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
            'admission_number' => ['nullable', 'string', 'max:50', 'unique:students,admission_number,'.$student->id],
            'name' => ['required', 'string', 'max:120'],
            // Required admission fields
            'first_name' => ['required', 'string', 'max:120'],
            'other_names' => ['nullable', 'string', 'max:255'],
            'name_with_initial' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'parent_address' => ['required', 'string', 'max:255'],
            // 'phone' => ['nullable', 'string', 'max:30'],
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
            'religion' => ['required', 'string', 'in:Buddhism,Hinduism,Islam,Christianity,Other'],
            'nationality' => ['nullable', 'string', 'max:60'],
            'desired_class' => ['nullable', 'string', 'max:120'],
            'medical_history' => ['nullable', 'string'],
            'long_term_medication' => ['required', 'in:0,1'],
            'learning_disabilities' => ['required', 'in:0,1'],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'previous_grade' => ['nullable', 'string', 'max:120'],
            'siblings' => ['nullable', 'string'],
            'has_siblings_in_college' => ['required', 'in:0,1'],
            'father_name_with_initial' => ['nullable', 'required_if:use_guardian,0', 'string', 'max:120'],
            'father_nic_passport' => ['nullable', 'string', 'max:60'],
            'father_religion' => ['nullable', 'string', 'in:Buddhism,Hinduism,Islam,Christianity,Other'],
            'father_nationality' => ['nullable', 'string', 'max:60'],
            'father_occupation' => ['nullable', 'string', 'max:120'],
            'father_phone' => ['nullable', 'string', 'max:30'],
            'father_whatsapp' => ['nullable', 'string', 'max:30'],
            'father_office_phone' => ['nullable', 'string', 'max:30'],
            'father_emergency_number' => ['nullable', 'string', 'max:30'],
            'mother_name_with_initial' => ['nullable', 'string', 'max:120'],
            'mother_nic_passport' => ['nullable', 'string', 'max:60'],
            'mother_religion' => ['nullable', 'string', 'in:Buddhism,Hinduism,Islam,Christianity,Other'],
            'mother_nationality' => ['nullable', 'string', 'max:60'],
            'mother_occupation' => ['nullable', 'string', 'max:120'],
            'mother_phone' => ['nullable', 'string', 'max:30'],
            'mother_whatsapp' => ['nullable', 'string', 'max:30'],
            'mother_office_phone' => ['nullable', 'string', 'max:30'],
            'mother_emergency_number' => ['nullable', 'string', 'max:30'],
            'passport_photo_path' => ['nullable', 'string', 'max:255'],
            'hear_about_us' => ['nullable', 'string', 'in:Facebook,Friends,TV,Ads,Other'],
            'leaving_docs_issued' => ['nullable', 'in:0,1'],
        ]);

        $classRoom = ClassRoom::query()->find($validated['class_room_id']);
        $monthlyFee = (float) ($validated['monthly_fee'] ?? ($classRoom?->monthly_fee ?? 0));

        $feeStartDate = $this->parseFlexibleDate($request->input('fee_start_date'));
        $feeStartDateToStore = $request->filled('fee_start_date') ? ($feeStartDate?->toDateString()) : ($student->fee_start_date?->toDateString());

        // Recompute due based on fee_start_date
        $dueAmount = $monthlyFee;
        if ($feeStartDate) {
            $start = $feeStartDate;
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
            'fee_start_date' => $feeStartDateToStore,
            'year' => isset($validated['joining_date']) 
                ? (\Carbon\Carbon::parse($validated['joining_date'])->year . '-' . (\Carbon\Carbon::parse($validated['joining_date'])->year + 1))
                : $student->year,
            'class_room_id' => $validated['class_room_id'],
            'class' => $classRoom?->name,
            'religion' => $validated['religion'] ?? $student->religion,
            'nationality' => $validated['nationality'] ?? ($student->nationality ?: 'Sri Lankan'),
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
            'hear_about_us' => $validated['hear_about_us'] ?? $student->hear_about_us,
            'leaving_docs_issued' => (bool) ($validated['leaving_docs_issued'] ?? $student->leaving_docs_issued),
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
    public function search(Request $request, MonthlyFeeAllocator $allocator)
    {
        // If an explicit id is provided, return a single detailed record
        $id = $request->query('id');
        if ($id) {
            $s = Student::query()->with('classRoom')->find((int) $id);
            if (! $s) {
                return response()->json(['results' => []]);
            }

            $ledger = $allocator->buildLedger($s, 12);
            $dueMonths = [];

            $currentMonthStart = now()->startOfMonth();
            foreach ($ledger as $key => $m) {
                $monthStart = Carbon::createFromDate((int) ($m['year'] ?? 0), (int) ($m['month'] ?? 0), 1)->startOfMonth();
                if ($monthStart->gt($currentMonthStart)) {
                    break;
                }

                $status = (string) ($m['status'] ?? 'unpaid');
                if ($status === 'paid') {
                    continue;
                }

                $isPartial = $status === 'partially_paid';
                $remaining = (float) ($m['remaining'] ?? 0);
                $label = $monthStart->format('F Y').($isPartial ? ' (Partial)' : '');

                $dueMonths[] = [
                    'label' => $label,
                    'amount' => round($remaining, 2),
                    'is_partial' => $isPartial,
                    'month_key' => $monthStart->format('Y-m'),
                ];
            }

            $advanceMonths = [];
            $base = now()->startOfMonth()->addMonth();
            for ($i = 0; $i < 12; $i++) {
                $d = $base->copy()->addMonthsNoOverflow($i)->startOfMonth();
                $monthKey = $d->format('Y-m');
                $required = isset($ledger[$monthKey]) ? (float) ($ledger[$monthKey]['remaining'] ?? 0) : 0.0;
                $advanceMonths[] = [
                    'key' => $monthKey,
                    'label' => $d->format('M Y'),
                    'month' => (int) $d->format('n'),
                    'year' => (int) $d->format('Y'),
                    'required_amount' => round($required, 2),
                ];
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
                'advance_months' => $advanceMonths,
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
