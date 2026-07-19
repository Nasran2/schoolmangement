<?php

namespace App\Http\Controllers;

use App\Models\Revenue;
use App\Models\RevenueAdjustment;
use App\Models\RevenueCategory;
use App\Models\Setting;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\StudentMonthlyFeeOverride;
use App\Services\AuditLogger;
use App\Services\Billing\BillNumberService;
use App\Services\Billing\MonthlyFeeAllocator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RevenueController extends Controller
{
    public function chequesIndex(Request $request): View
    {
        $query = Revenue::query()
            ->with(['student', 'category'])
            ->where('payment_method', 'cheque');

        if ($request->filled('status')) {
            $status = (string) $request->string('status');
            if ($status === 'hold' || $status === 'pending') {
                $query->whereIn('payment_status', ['hold', 'pending']);
            } else {
                $query->where('payment_status', $status);
            }
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
        if (! in_array((string) ($item->payment_status ?? 'confirmed'), ['hold', 'pending'], true)) {
            return back()->withErrors(['cheque' => 'This cheque is not on hold.']);
        }

        $passedDate = (string) ($request->input('passed_date') ?: now()->toDateString());
        try {
            $passedDate = Carbon::parse($passedDate)->toDateString();
        } catch (\Throwable $e) {
            return back()->withErrors(['passed_date' => 'Invalid passed date. Use YYYY-MM-DD.']);
        }

        $confirmedAt = Carbon::parse($passedDate)->setTimeFrom(now());

        $item->forceFill([
            'payment_status' => 'confirmed',
            // Store the user-selected pass date
            'confirmed_at' => $confirmedAt,
            'paid_at' => $passedDate,
        ])->save();

        return back()->with('success', 'Cheque marked as PASSED on ' . $passedDate . '.');
    }

    public function markChequeReturned(Request $request, Revenue $item): RedirectResponse
    {
        if (($item->payment_method ?? null) !== 'cheque') {
            return back()->withErrors(['cheque' => 'This revenue item is not a cheque payment.']);
        }
        if (! in_array((string) ($item->payment_status ?? 'confirmed'), ['hold', 'pending'], true)) {
            return back()->withErrors(['cheque' => 'This cheque is not on hold.']);
        }

        $item->forceFill([
            'payment_status' => 'rejected',
            'confirmed_at' => now(),
            // Keep paid_at for the cheque record date; rejected status excludes it from paid/hold ledgers.
            'paid_at' => $item->paid_at ?: now()->toDateString(),
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
        } else {
            // Keep hold cheques in Cheques page only; default list shows settled/normal revenue.
            $query->where(function ($q) {
                $q->whereNull('payment_status')
                    ->orWhereNotIn('payment_status', ['hold', 'pending']);
            });
        }

        $range = $request->input('range', 'all');

        if ($range !== 'all') {
            $start = null;
            $end = null;

            if ($range === 'today') {
                $start = now()->startOfDay();
                $end = now()->endOfDay();
            } elseif ($range === 'yesterday') {
                $start = now()->subDay()->startOfDay();
                $end = now()->subDay()->endOfDay();
            } elseif ($range === 'this_week') {
                $start = now()->startOfWeek();
                $end = now()->endOfWeek();
            } elseif ($range === 'last_week') {
                $start = now()->subWeek()->startOfWeek();
                $end = now()->subWeek()->endOfWeek();
            } elseif ($range === 'this_month') {
                $start = now()->startOfMonth();
                $end = now()->endOfMonth();
            } elseif ($range === 'last_month') {
                $start = now()->subMonth()->startOfMonth();
                $end = now()->subMonth()->endOfMonth();
            } elseif ($range === 'custom') {
                $start = $request->filled('from') ? \Carbon\Carbon::parse($request->input('from')) : null;
                $end = $request->filled('to') ? \Carbon\Carbon::parse($request->input('to')) : null;
            }

            if ($start) {
                $query->whereDate('paid_at', '>=', $start->toDateString());
            }
            if ($end) {
                $query->whereDate('paid_at', '<=', $end->toDateString());
            }
        }

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        return view('revenue.index', [
            'items' => $query->orderByDesc('paid_at')->orderByDesc('id')->paginate($perPage)->withQueryString(),
            'categories' => RevenueCategory::query()->orderBy('name')->get(),
            'filters' => $request->only(['category_id', 'from', 'to', 'q', 'payment_method', 'payment_status', 'range', 'per_page']),
            'range' => $range,
            'perPage' => $perPage,
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
        $monthlyCatId = $selectedStudent?->monthlyFeeCategoryId();
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
            if (is_array($parsed)) {
                $request->merge(['advance_months' => $parsed]);
            }
        }
        $overrideRaw = $request->input('monthly_fee_overrides');
        if (is_string($overrideRaw)) {
            $parsed = json_decode($overrideRaw, true);
            if (is_array($parsed)) {
                $request->merge(['monthly_fee_overrides' => $parsed]);
            }
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
            'base_amount' => ['nullable', 'numeric', 'min:0.01'],
            'discount_type' => ['nullable', 'in:percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            // Allocation inputs (optional): array of future months {month,year}
            'advance_months' => ['nullable', 'array'],
            'advance_months.*.month' => ['required_with:advance_months', 'integer', 'min:1', 'max:12'],
            'advance_months.*.year' => ['required_with:advance_months', 'integer', 'min:2000'],
            'monthly_fee_overrides' => ['nullable', 'array'],
            'monthly_fee_overrides.*.month' => ['required_with:monthly_fee_overrides', 'integer', 'min:1', 'max:12'],
            'monthly_fee_overrides.*.year' => ['required_with:monthly_fee_overrides', 'integer', 'min:2000'],
            'monthly_fee_overrides.*.fee_amount' => ['required_with:monthly_fee_overrides', 'numeric', 'min:0.01'],
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
        $feeOverrides = $student
            ? $this->normalizeMonthlyFeeOverrides($validated['monthly_fee_overrides'] ?? [])
            : [];
        if ($student && $category && $monthlyCatId && (int) $category->id === (int) $monthlyCatId) {
            $adv = $validated['advance_months'] ?? [];
            $result = $allocator->allocate($student, (float)$validated['amount'], $adv, $feeOverrides);

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
            $paymentStatus = 'hold';
            $confirmedAt = null;
            $chequeDate = $validated['cheque_date'] ?? null;
            $paymentMeta = [
                'cheque_number' => $validated['cheque_number'] ?? null,
                'bank' => $validated['cheque_bank'] ?? null,
                'student_name' => $validated['cheque_student_name'] ?? null,
            ];
        }

        if (!empty($validated['discount_value']) && $validated['discount_value'] > 0) {
            $paymentMeta = $paymentMeta ?? [];
            $paymentMeta['base_amount'] = $validated['base_amount'] ?? null;
            $paymentMeta['discount_type'] = $validated['discount_type'] ?? null;
            $paymentMeta['discount_value'] = $validated['discount_value'] ?? null;
        }

        $revenue = DB::transaction(function () use ($request, $validated, $billNo, $paymentMethod, $paymentStatus, $paymentMeta, $chequeDate, $confirmedAt, $notes, $student, $result, $feeOverrides) {
            if ($student && !empty($feeOverrides)) {
                foreach ($feeOverrides as $override) {
                    StudentMonthlyFeeOverride::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'year' => (int) $override['year'],
                            'month' => (int) $override['month'],
                        ],
                        [
                            'fee_amount' => (float) $override['fee_amount'],
                            'set_by' => $request->user()?->id,
                        ]
                    );
                }
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

            return $revenue;
        });

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
    public function edit(Revenue $item, BillNumberService $billNumbers): View
    {
        if ($item->isCancelled()) {
            abort(403, 'Cancelled revenue bills cannot be edited.');
        }

        $selectedStudent = $item->student_id ? Student::query()->with('classRoom')->find($item->student_id) : null;
        $monthlyCatId = $selectedStudent?->monthlyFeeCategoryId();

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

        return view('revenue.create', [
            'item' => $item->load(['category', 'student']),
            'categories' => $categoriesQuery->orderBy('name')->get(),
            'students' => Student::query()->where('active', true)->orderBy('name')->get(),
            'selectedStudentId' => $item->student_id,
            'selectedStudent' => $selectedStudent,
            'autogenerate' => app('settings')->get('billing.revenue.autogenerate', '1') === '1',
            'nextBillNumberPreview' => $item->bill_no ?: $billNumbers->peekNextRevenueBillNumber(),
            'monthlyCatId' => $monthlyCatId,
            'preselectedCategoryId' => (int) $item->revenue_category_id,
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
    public function update(Request $request, Revenue $item, MonthlyFeeAllocator $allocator): RedirectResponse
    {
        if ($item->isCancelled()) {
            abort(403, 'Cancelled revenue bills cannot be edited.');
        }

        $autogenerate = app('settings')->get('billing.revenue.autogenerate', '1') === '1';

        $advRaw = $request->input('advance_months');
        if (is_string($advRaw)) {
            $parsed = json_decode($advRaw, true);
            if (is_array($parsed)) {
                $request->merge(['advance_months' => $parsed]);
            }
        }
        $overrideRaw = $request->input('monthly_fee_overrides');
        if (is_string($overrideRaw)) {
            $parsed = json_decode($overrideRaw, true);
            if (is_array($parsed)) {
                $request->merge(['monthly_fee_overrides' => $parsed]);
            }
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
            // When auto-generate is enabled, keep existing bill number unless settings are changed.
            'bill_no' => $autogenerate
                ? ['nullable', 'string', 'max:50']
                : ['nullable', 'string', 'max:50', 'unique:revenues,bill_no,' . $item->id],
            'notes' => ['nullable', 'string'],
            'base_amount' => ['nullable', 'numeric', 'min:0.01'],
            'discount_type' => ['nullable', 'in:percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'advance_months' => ['nullable', 'array'],
            'advance_months.*.month' => ['required_with:advance_months', 'integer', 'min:1', 'max:12'],
            'advance_months.*.year' => ['required_with:advance_months', 'integer', 'min:2000'],
            'monthly_fee_overrides' => ['nullable', 'array'],
            'monthly_fee_overrides.*.month' => ['required_with:monthly_fee_overrides', 'integer', 'min:1', 'max:12'],
            'monthly_fee_overrides.*.year' => ['required_with:monthly_fee_overrides', 'integer', 'min:2000'],
            'monthly_fee_overrides.*.fee_amount' => ['required_with:monthly_fee_overrides', 'numeric', 'min:0.01'],
        ]);

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
        $feeOverrides = $student
            ? $this->normalizeMonthlyFeeOverrides($validated['monthly_fee_overrides'] ?? [])
            : [];
        $monthlyCatId = $student?->monthlyFeeCategoryId();
        if ($student && $category && $monthlyCatId && (int) $category->id === (int) $monthlyCatId) {
            $result = $allocator->allocate(
                $student,
                (float) $validated['amount'],
                $validated['advance_months'] ?? [],
                $feeOverrides,
                [(int) $item->id]
            );

            $errors = $result['summary']['errors'] ?? [];
            if (! empty($errors)) {
                return back()->withInput()->withErrors(['amount' => implode(' ', $errors)]);
            }
        }

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
            $paymentStatus = 'hold';
            $confirmedAt = null;
            $chequeDate = $validated['cheque_date'] ?? null;
            $paymentMeta = [
                'cheque_number' => $validated['cheque_number'] ?? null,
                'bank' => $validated['cheque_bank'] ?? null,
                'student_name' => $validated['cheque_student_name'] ?? null,
            ];
        }

        if (!empty($validated['discount_value']) && $validated['discount_value'] > 0) {
            $paymentMeta = $paymentMeta ?? [];
            $paymentMeta['base_amount'] = $validated['base_amount'] ?? null;
            $paymentMeta['discount_type'] = $validated['discount_type'] ?? null;
            $paymentMeta['discount_value'] = $validated['discount_value'] ?? null;
        }

        DB::transaction(function () use ($request, $item, $validated, $autogenerate, $paymentMethod, $paymentStatus, $paymentMeta, $chequeDate, $confirmedAt, $student, $result, $feeOverrides) {
            if ($student && !empty($feeOverrides)) {
                foreach ($feeOverrides as $override) {
                    StudentMonthlyFeeOverride::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'year' => (int) $override['year'],
                            'month' => (int) $override['month'],
                        ],
                        [
                            'fee_amount' => (float) $override['fee_amount'],
                            'set_by' => $request->user()?->id,
                        ]
                    );
                }
            }

            $item->update([
                'bill_no' => $autogenerate ? $item->bill_no : ($validated['bill_no'] ?? null),
                'revenue_category_id' => (int) $validated['revenue_category_id'],
                'student_id' => $validated['student_id'] ? (int) $validated['student_id'] : null,
                'amount' => $validated['amount'],
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'payment_meta' => $paymentMeta,
                'cheque_date' => $chequeDate,
                'confirmed_at' => $confirmedAt,
                'paid_at' => $validated['paid_at'],
                'notes' => $validated['notes'] ?? null,
            ]);

            \App\Models\StudentMonthFeeAllocation::query()
                ->where('revenue_id', $item->id)
                ->delete();

            if ($student && $result && !empty($result['allocations'])) {
                foreach ($result['allocations'] as $a) {
                    \App\Models\StudentMonthFeeAllocation::create([
                        'revenue_id' => $item->id,
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
        });

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
    public function destroy(Request $request, Revenue $item): RedirectResponse
    {
        if ($item->isCancelled()) {
            return redirect()->route('revenue.items.index')->with('status', 'Revenue bill is already cancelled.');
        }

        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $originalAmount = (float) $item->amount;
        $billNo = $item->bill_no;

        DB::transaction(function () use ($request, $item, $validated) {
            RevenueAdjustment::query()
                ->where('revenue_id', $item->id)
                ->delete();

            \App\Models\StudentMonthFeeAllocation::query()
                ->where('revenue_id', $item->id)
                ->delete();

            $item->forceFill([
                'amount' => 0,
                'payment_status' => 'cancelled',
                'confirmed_at' => null,
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()?->id,
                'cancel_reason' => $validated['cancel_reason'],
            ])->save();
        });

        app(AuditLogger::class)->log(
            'revenue.cancel',
            $item,
            'Revenue cancelled',
            [
                'bill_no' => $billNo,
                'original_amount' => $originalAmount,
                'cancel_reason' => $validated['cancel_reason'],
            ]
        );

        return redirect()->route('revenue.items.index')->with('status', 'Revenue bill cancelled. Bill number was kept and amount is now 0.00.');
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
            $abs = storage_path('app/public/' . $logoPath);
            if (is_file($abs)) {
                $mime = @mime_content_type($abs) ?: 'image/png';
                $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($abs));
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
            if (is_array($parsed)) {
                $request->merge(['advance_months' => $parsed]);
            }
        }
        $overrideRaw = $request->input('monthly_fee_overrides');
        if (is_string($overrideRaw)) {
            $parsed = json_decode($overrideRaw, true);
            if (is_array($parsed)) {
                $request->merge(['monthly_fee_overrides' => $parsed]);
            }
        }
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'revenue_category_id' => ['nullable', 'integer', 'exists:revenue_categories,id'],
            'revenue_id' => ['nullable', 'integer', 'exists:revenues,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'advance_months' => ['nullable', 'array'],
            'advance_months.*.month' => ['required_with:advance_months', 'integer', 'min:1', 'max:12'],
            'advance_months.*.year' => ['required_with:advance_months', 'integer', 'min:2000'],
            'monthly_fee_overrides' => ['nullable', 'array'],
            'monthly_fee_overrides.*.month' => ['required_with:monthly_fee_overrides', 'integer', 'min:1', 'max:12'],
            'monthly_fee_overrides.*.year' => ['required_with:monthly_fee_overrides', 'integer', 'min:2000'],
            'monthly_fee_overrides.*.fee_amount' => ['required_with:monthly_fee_overrides', 'numeric', 'min:0.01'],
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
        $feeOverrides = $this->normalizeMonthlyFeeOverrides($validated['monthly_fee_overrides'] ?? []);
        $excludeRevenueIds = [];
        if (!empty($validated['revenue_id'])) {
            $editingRevenue = Revenue::query()
                ->where('id', (int) $validated['revenue_id'])
                ->where('student_id', $student->id)
                ->first();
            if ($editingRevenue) {
                $excludeRevenueIds[] = (int) $editingRevenue->id;
            }
        }

        $result = $allocator->allocate($student, (float) $validated['amount'], $advanceMonths, $feeOverrides, $excludeRevenueIds);

        // Month-aware required amount for selected advance months (respects promotion/demotion fee changes + partials)
        $required = 0.0;
        if (!empty($advanceMonths)) {
            $ledger = $allocator->buildLedger($student, 24, $feeOverrides, $excludeRevenueIds);
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

    private function normalizeMonthlyFeeOverrides(array $overrides): array
    {
        $normalized = [];
        foreach ($overrides as $override) {
            $month = (int) ($override['month'] ?? 0);
            $year = (int) ($override['year'] ?? 0);
            $amount = round((float) ($override['fee_amount'] ?? $override['amount'] ?? 0), 2);

            if ($month < 1 || $month > 12 || $year < 2000 || $amount <= 0) {
                continue;
            }

            $normalized[sprintf('%04d-%02d', $year, $month)] = [
                'year' => $year,
                'month' => $month,
                'fee_amount' => $amount,
            ];
        }

        ksort($normalized);

        return array_values($normalized);
    }
}
