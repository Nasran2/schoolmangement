<?php

namespace App\Http\Controllers;

use App\Models\Revenue;
use App\Models\RevenueAdjustment;
use App\Models\RevenueCategory;
use App\Models\Setting;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Services\AuditLogger;
use App\Services\Billing\BillNumberService;
use App\Services\Billing\MonthlyFeeAllocator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RevenueController extends Controller
{
    public function chequesIndex(Request $request): View
    {
        $query = Revenue::query()
            ->with(['student', 'category'])
            ->where('payment_method', 'cheque');

        if ($request->filled('status')) {
            $query->where('payment_status', $request->string('status'));
        }

        if ($request->filled('cheque_from')) {
            $query->whereDate('cheque_date', '>=', $request->string('cheque_from'));
        }

        if ($request->filled('cheque_to')) {
            $query->whereDate('cheque_date', '<=', $request->string('cheque_to'));
        }

        $state = (string) $request->query('state', '');
        $today = Carbon::today();
        if ($state === 'upcoming') {
            $query->whereDate('cheque_date', '>', $today->toDateString());
        } elseif ($state === 'due') {
            $query->whereDate('cheque_date', '=', $today->toDateString());
        } elseif ($state === 'overdue') {
            $query->whereDate('cheque_date', '<', $today->toDateString());
        }

        if ($request->filled('q')) {
            $raw = (string) $request->string('q');
            $q = '%' . str_replace('%', '\\%', $raw) . '%';
            $query->where(function ($sub) use ($q) {
                $sub->where('bill_no', 'like', $q)
                    ->orWhere('notes', 'like', $q)
                    ->orWhere('payment_meta', 'like', $q)
                    ->orWhereHas('student', function ($s) use ($q) {
                        $s->where('name', 'like', $q)
                            ->orWhere('admission_number', 'like', $q);
                    });
            });
        }

        return view('revenue.cheques', [
            'items' => $query->orderByDesc('cheque_date')->orderByDesc('paid_at')->paginate(15)->withQueryString(),
            'filters' => $request->only(['q', 'status', 'state', 'cheque_from', 'cheque_to']),
        ]);
    }

    public function markChequePassed(Request $request, Revenue $item): RedirectResponse
    {
        if (($item->payment_method ?? null) !== 'cheque') {
            return back()->withErrors(['cheque' => 'This revenue item is not a cheque payment.']);
        }
        if (($item->payment_status ?? 'confirmed') !== 'pending') {
            return back()->withErrors(['cheque' => 'This cheque is not pending.']);
        }

        $paidAt = $item->cheque_date ? Carbon::parse($item->cheque_date)->toDateString() : now()->toDateString();

        $item->forceFill([
            'payment_status' => 'confirmed',
            'confirmed_at' => now(),
            // Per workflow: only count as paid on the cheque date (pass date)
            'paid_at' => $paidAt,
        ])->save();

        return back()->with('success', 'Cheque marked as PASSED and payment confirmed.');
    }

    public function markChequeReturned(Request $request, Revenue $item): RedirectResponse
    {
        if (($item->payment_method ?? null) !== 'cheque') {
            return back()->withErrors(['cheque' => 'This revenue item is not a cheque payment.']);
        }
        if (($item->payment_status ?? 'confirmed') !== 'pending') {
            return back()->withErrors(['cheque' => 'This cheque is not pending.']);
        }

        $item->forceFill([
            'payment_status' => 'rejected',
            'confirmed_at' => now(),
        ])->save();

        return back()->with('success', 'Cheque marked as RETURNED. It will not count as paid.');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Revenue::query()->with(['category', 'student']);

        if ($request->filled('q')) {
            $raw = (string) $request->string('q');
            $q = '%' . str_replace('%', '\\%', $raw) . '%';
            $query->where(function ($sub) use ($q) {
                $sub->where('bill_no', 'like', $q)
                    ->orWhere('notes', 'like', $q)
                    ->orWhereHas('student', function ($s) use ($q) {
                        $s->where('name', 'like', $q)
                            ->orWhere('admission_number', 'like', $q)
                            ->orWhere('whatsapp_number', 'like', $q);
                    })
                    ->orWhereHas('category', function ($c) use ($q) {
                        $c->where('name', 'like', $q);
                    });
            });
        }

        if ($request->filled('category_id')) {
            $query->where('revenue_category_id', $request->string('category_id'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->string('payment_method'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status'));
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
            'filters' => $request->only(['category_id', 'from', 'to', 'q', 'payment_method', 'payment_status']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, BillNumberService $billNumbers): View
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
            // Prefer the student's configured monthly-fee category if available
            $preselectedCategoryId = $monthlyCatId;
            if (! $preselectedCategoryId) {
                $monthlyCategory = RevenueCategory::query()
                    ->where('active', true)
                    ->where('payment_type', 'monthly')
                    ->orderBy('name')
                    ->first();
                $preselectedCategoryId = $monthlyCategory?->id;
            }
        }

        // Allow direct preselect from other pages (e.g. category drilldown)
        if ($request->filled('category_id')) {
            $preselectedCategoryId = (int) $request->query('category_id');
        }

        return view('revenue.create', [
            'categories' => $categoriesQuery->orderBy('name')->get(),
            'students' => Student::query()->where('active', true)->orderBy('name')->get(),
            'selectedStudentId' => $selectedStudentId,
            'selectedStudent' => $selectedStudent,
            'autogenerate' => app('settings')->get('billing.revenue.autogenerate', '1') === '1',
            'nextBillNumberPreview' => $billNumbers->peekNextRevenueBillNumber(),
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
    public function store(Request $request, BillNumberService $billNumbers, MonthlyFeeAllocator $allocator): RedirectResponse
    {
        $autogenerate = app('settings')->get('billing.revenue.autogenerate', '1') === '1';

        // Normalize JSON advance_months if sent as string
        $advRaw = $request->input('advance_months');
        if (is_string($advRaw)) {
            $parsed = json_decode($advRaw, true);
            if (is_array($parsed)) { $request->merge(['advance_months' => $parsed]); }
        }
        $validated = $request->validate([
            'revenue_category_id' => ['required', 'exists:revenue_categories,id'],
            'student_id' => ['nullable', 'exists:students,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,cheque'],
            'bank_ref_no' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'cheque_date' => ['required_if:payment_method,cheque', 'nullable', 'date'],
            'cheque_number' => ['required_if:payment_method,cheque', 'nullable', 'string', 'max:100'],
            'cheque_bank' => ['required_if:payment_method,cheque', 'nullable', 'string', 'max:100'],
            'cheque_student_name' => ['nullable', 'string', 'max:120'],
            // When auto-generate is enabled, user input is ignored; avoid failing validation on duplicates.
            'bill_no' => $autogenerate
                ? ['nullable', 'string', 'max:50']
                : ['nullable', 'string', 'max:50', 'unique:revenues,bill_no'],
            'notes' => ['nullable', 'string'],
            // Allocation inputs (optional): array of future months {month,year}
            'advance_months' => ['nullable', 'array'],
            'advance_months.*.month' => ['required_with:advance_months', 'integer', 'min:1', 'max:12'],
            'advance_months.*.year' => ['required_with:advance_months', 'integer', 'min:2000'],
        ]);

        // Validate category applicability and, if monthly, compute allocation BEFORE creating revenue
        $student = null;
        $category = RevenueCategory::query()->with('classRooms')->find((int) $validated['revenue_category_id']);
        if (! empty($validated['student_id'])) {
            $student = Student::query()->with('classRoom')->find((int) $validated['student_id']);
            if ($student && $student->class_room_id && $category) {
                $allowed = $category->applies_to_all || $category->classRooms->contains('id', (int) $student->class_room_id);
                if (! $allowed) {
                    return back()->withInput()->withErrors([
                        'revenue_category_id' => 'This category is not applicable to the selected student class.',
                    ]);
                }
            }
        }

        $result = null;
        $monthlyCatId = $student?->monthlyFeeCategoryId();
        if ($student && $category && $monthlyCatId && (int) $category->id === (int) $monthlyCatId) {
            $adv = $validated['advance_months'] ?? [];
            $result = $allocator->allocate($student, (float)$validated['amount'], $adv);

            $errors = $result['summary']['errors'] ?? [];
            if (! empty($errors)) {
                return back()->withInput()->withErrors(['amount' => implode(' ', $errors)]);
            }
        }

        // Prepare bill number
        $billNo = null;
        if (! $autogenerate) {
            $inputBillNo = $validated['bill_no'] ?? null;
            $preview = $billNumbers->peekNextRevenueBillNumber();

            // If user keeps the suggested next bill number, reserve it (increment next_number).
            if ($inputBillNo && $preview !== '' && $inputBillNo === $preview) {
                $billNo = $billNumbers->nextRevenueBillNumber();
            } else {
                $billNo = $inputBillNo;
            }
        }
        if (! $billNo) {
            $billNo = $billNumbers->nextRevenueBillNumber() ?: null;
        }

        // Notes: only save what the user typed; do not auto-generate
        $notes = $validated['notes'] ?? null;

        $paymentMethod = $validated['payment_method'] ?? 'cash';
        if (! in_array($paymentMethod, ['cash', 'bank_transfer', 'cheque'], true)) {
            $paymentMethod = 'cash';
        }

        $paymentMeta = null;
        $paymentStatus = 'confirmed';
        $confirmedAt = now();
        $chequeDate = null;

        if ($paymentMethod === 'bank_transfer') {
            $paymentMeta = [
                'bank' => $validated['bank_name'] ?? null,
                'ref_no' => $validated['bank_ref_no'] ?? null,
            ];
        }

        if ($paymentMethod === 'cheque') {
            $paymentStatus = 'pending';
            $confirmedAt = null;
            $chequeDate = $validated['cheque_date'] ?? null;
            $paymentMeta = [
                'cheque_number' => $validated['cheque_number'] ?? null,
                'bank' => $validated['cheque_bank'] ?? null,
                'student_name' => $validated['cheque_student_name'] ?? null,
            ];
        }

        // Create revenue AFTER successful allocation preview to avoid double-counting in ledger
        $revenue = Revenue::create([
            'bill_no' => $billNo,
            'revenue_category_id' => (int) $validated['revenue_category_id'],
            'student_id' => $validated['student_id'] ? (int) $validated['student_id'] : null,
            'amount' => $validated['amount'],
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'payment_meta' => $paymentMeta,
            'cheque_date' => $chequeDate,
            'confirmed_at' => $confirmedAt,
            'paid_at' => $validated['paid_at'],
            'notes' => $notes,
            'created_by' => $request->user()?->id,
        ]);

        app(AuditLogger::class)->log(
            'revenue.create',
            $revenue,
            'Revenue created',
            [
                'bill_no' => $revenue->bill_no,
                'amount' => (float) $revenue->amount,
                'student_id' => $revenue->student_id,
                'revenue_category_id' => $revenue->revenue_category_id,
            ]
        );

        // Persist allocations if monthly
        if ($student && $result && !empty($result['allocations'])) {
            foreach ($result['allocations'] as $a) {
                \App\Models\StudentMonthFeeAllocation::create([
                    'revenue_id' => $revenue->id,
                    'student_id' => $student->id,
                    'month' => (int) $a['month'],
                    'year' => (int) $a['year'],
                    'type' => (string) $a['type'],
                    'applied_amount' => (float) $a['applied_amount'],
                    'is_partial' => (bool) $a['is_partial'],
                    'remaining_for_month' => (float) $a['remaining_for_month'],
                ]);
            }
        }

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
            'autogenerate' => app('settings')->get('billing.revenue.autogenerate', '1') === '1',
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
        $autogenerate = app('settings')->get('billing.revenue.autogenerate', '1') === '1';

        $validated = $request->validate([
            'revenue_category_id' => ['required', 'exists:revenue_categories,id'],
            'student_id' => ['nullable', 'exists:students,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            // When auto-generate is enabled, keep existing bill number unless settings are changed.
            'bill_no' => $autogenerate
                ? ['nullable', 'string', 'max:50']
                : ['nullable', 'string', 'max:50', 'unique:revenues,bill_no,'.$item->id],
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
            'bill_no' => $autogenerate ? $item->bill_no : ($validated['bill_no'] ?? null),
            'revenue_category_id' => (int) $validated['revenue_category_id'],
            'student_id' => $validated['student_id'] ? (int) $validated['student_id'] : null,
            'amount' => $validated['amount'],
            'paid_at' => $validated['paid_at'],
            'notes' => $validated['notes'] ?? null,
        ]);

        app(AuditLogger::class)->log(
            'revenue.update',
            $item,
            'Revenue updated',
            [
                'bill_no' => $item->bill_no,
                'amount' => (float) $item->amount,
                'student_id' => $item->student_id,
                'revenue_category_id' => $item->revenue_category_id,
            ]
        );

        return back()->with('status', 'Revenue updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Revenue $item): RedirectResponse
    {
        $hasAdjustments = RevenueAdjustment::query()
            ->where('revenue_id', $item->id)
            ->exists();

        if ($hasAdjustments) {
            return redirect()->route('revenue.items.index')->withErrors([
                'delete' => 'Cannot delete this revenue because it has refund/waiver adjustments.',
            ]);
        }

        app(AuditLogger::class)->log(
            'revenue.delete',
            $item,
            'Revenue deleted',
            [
                'bill_no' => $item->bill_no,
                'amount' => (float) $item->amount,
            ]
        );

        $item->delete();

        return redirect()->route('revenue.items.index')->with('status', 'Revenue deleted.');
    }

    /**
     * Display the receipt for the specified revenue.
     */
    public function receipt(Revenue $item): View
    {
        $item->load(['student.classRoom', 'category', 'allocations']);

        $settings = app('settings');
        $schoolInfo = [
            'name' => $settings->get('school.name', config('app.name')),
            'address' => $settings->get('school.address', ''),
            'phone' => $settings->get('school.phone', ''),
            'email' => $settings->get('school.email', ''),
        ];

        $autoPrint = $settings->get('receipt.auto_print', '0') === '1';

        // Prepare logo data URI
        $logoPath = (string) $settings->get('school.logo', '');
        $logoDataUri = null;
        if ($logoPath !== '') {
            $abs = storage_path('app/public/'.$logoPath);
            if (is_file($abs)) {
                $mime = @mime_content_type($abs) ?: 'image/png';
                $logoDataUri = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($abs));
            }
        }

        return view('revenue.receipt', [
            'revenue' => $item,
            'schoolInfo' => $schoolInfo,
            'autoPrint' => $autoPrint,
            'schoolLogoDataUri' => $logoDataUri,
        ]);
    }

    /**
     * Preview allocation JSON for monthly fee payments.
     */
    public function previewAllocation(Request $request, MonthlyFeeAllocator $allocator)
    {
        // Normalize JSON advance_months if sent as string
        $advRaw = $request->input('advance_months');
        if (is_string($advRaw)) {
            $parsed = json_decode($advRaw, true);
            if (is_array($parsed)) { $request->merge(['advance_months' => $parsed]); }
        }
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'revenue_category_id' => ['nullable', 'integer', 'exists:revenue_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'advance_months' => ['nullable', 'array'],
            'advance_months.*.month' => ['required_with:advance_months', 'integer', 'min:1', 'max:12'],
            'advance_months.*.year' => ['required_with:advance_months', 'integer', 'min:2000'],
        ]);

        $student = Student::query()->with('classRoom')->find((int) $validated['student_id']);
        if (! $student) return response()->json(['error' => 'Student not found'], 404);

        // Only allow allocation preview for the student's configured monthly-fee category
        $monthlyCatId = $student->monthlyFeeCategoryId();
        if (! $monthlyCatId) {
            return response()->json([
                'allocations' => [],
                'summary' => [
                    'total_applied' => 0.0,
                    'unallocated_balance' => (float) $validated['amount'],
                    'paid_due_months' => [],
                    'advance_months' => [],
                    'errors' => ['Monthly fee category is not set for this student.'],
                ],
            ]);
        }

        if (!empty($validated['revenue_category_id']) && (int) $validated['revenue_category_id'] !== (int) $monthlyCatId) {
            return response()->json([
                'allocations' => [],
                'summary' => [
                    'total_applied' => 0.0,
                    'unallocated_balance' => (float) $validated['amount'],
                    'paid_due_months' => [],
                    'advance_months' => [],
                    'errors' => [],
                ],
            ]);
        }

        $advanceMonths = $validated['advance_months'] ?? [];
        $result = $allocator->allocate($student, (float) $validated['amount'], $advanceMonths);

        // Month-aware required amount for selected advance months (respects promotion/demotion fee changes + partials)
        $required = 0.0;
        if (!empty($advanceMonths)) {
            $ledger = $allocator->buildLedger($student, 24);
            foreach ($advanceMonths as $am) {
                $m = (int) ($am['month'] ?? 0);
                $y = (int) ($am['year'] ?? 0);
                if ($m < 1 || $m > 12 || $y < 2000) {
                    continue;
                }
                $key = sprintf('%04d-%02d', $y, $m);
                if (isset($ledger[$key])) {
                    $required += (float) ($ledger[$key]['remaining'] ?? 0.0);
                }
            }
        }

        if (!isset($result['summary']) || !is_array($result['summary'])) {
            $result['summary'] = [];
        }
        $result['summary']['selected_advance_months_required_amount'] = round($required, 2);

        return response()->json($result);
    }
}

