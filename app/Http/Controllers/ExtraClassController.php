<?php

namespace App\Http\Controllers;

use App\Models\ExtraClass;
use App\Models\ExtraClassStudent;
use App\Models\ExtraClassTeacherPayment;
use App\Models\ClassRoom;
use App\Models\Expense;
use App\Models\Student;
use App\Models\VisitingTeacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ExtraClassController extends Controller
{
    public function index(Request $request)
    {
        $classesQuery = ExtraClass::query()->with('visitingTeacher')->latest();

        $term = (string) ($request->query('q') ?? $request->query('search') ?? '');
        $term = trim($term);
        if ($term !== '') {
            $like = '%' . str_replace('%', '\\%', $term) . '%';
            $classesQuery->where(function ($sub) use ($like) {
                $sub->where('name', 'like', $like)
                    ->orWhere('payment_type', 'like', $like)
                    ->orWhereHas('visitingTeacher', function ($t) use ($like) {
                        $t->where('name', 'like', $like);
                    });
            });
        }

        $extraClasses = $classesQuery->paginate(20)->withQueryString();

        return view('extra-classes.index', compact('extraClasses'));
    }

    public function create()
    {
        $classRooms = ClassRoom::query()->orderBy('name')->get();
        $students = Student::query()->orderBy('name')->limit(200)->get();
        $visitingTeachers = VisitingTeacher::query()->orderBy('name')->get();
        return view('extra-classes.create', compact('classRooms','students','visitingTeachers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'payment_type' => ['required','in:monthly,daily'],
            'fee' => ['required','numeric','min:0'],
            'teacher_payment' => ['nullable','numeric','min:0'],
            'payment_start_date' => ['nullable','date'],
            'date' => ['nullable','date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'class_room_id' => ['nullable','integer','exists:class_rooms,id'],
            'student_ids' => ['array'],
            'student_ids.*' => ['integer','exists:students,id'],
            'visiting_teacher_id' => ['nullable','integer','exists:visiting_teachers,id'],
            'active' => ['boolean'],
        ]);

        DB::transaction(function () use ($data) {
            $extra = ExtraClass::create($data);
            if (!empty($data['class_room_id'])) {
                $studentsFromClass = Student::query()
                    ->where('class_room_id', $data['class_room_id'])
                    ->where('active', true)
                    ->pluck('id')->all();
                foreach ($studentsFromClass as $sid) {
                    ExtraClassStudent::firstOrCreate([
                        'extra_class_id' => $extra->id,
                        'student_id' => $sid,
                    ], [ 
                        'amount' => $extra->fee,
                        'enrolled_at' => $extra->payment_start_date ?: $extra->date ?: now()
                    ]);
                }
            }
            if (!empty($data['student_ids'])) {
                foreach ($data['student_ids'] as $sid) {
                    ExtraClassStudent::firstOrCreate([
                        'extra_class_id' => $extra->id,
                        'student_id' => $sid,
                    ], [ 
                        'amount' => $extra->fee,
                        'enrolled_at' => $extra->payment_start_date ?: $extra->date ?: now()
                    ]);
                }
            }
        });

        return redirect()->route('extra-classes.index')->with('status', 'Extra class created');
    }

    public function edit(ExtraClass $extraClass)
    {
        $classRooms = ClassRoom::query()->orderBy('name')->get();
        $students = Student::query()->orderBy('name')->limit(200)->get();
        $visitingTeachers = VisitingTeacher::query()->orderBy('name')->get();
        $enrolled = $extraClass->students()->pluck('student_id')->all();
        return view('extra-classes.edit', compact('extraClass','classRooms','students','visitingTeachers','enrolled'));
    }

    public function update(Request $request, ExtraClass $extraClass)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'payment_type' => ['required','in:monthly,daily'],
            'fee' => ['required','numeric','min:0'],
            'teacher_payment' => ['nullable','numeric','min:0'],
            'payment_start_date' => ['nullable','date'],
            'date' => ['nullable','date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'class_room_id' => ['nullable','integer','exists:class_rooms,id'],
            'student_ids' => ['array'],
            'student_ids.*' => ['integer','exists:students,id'],
            'visiting_teacher_id' => ['nullable','integer','exists:visiting_teachers,id'],
            'active' => ['boolean'],
        ]);

        DB::transaction(function () use ($data, $extraClass) {
            $extraClass->update($data);
            $keepIds = $data['student_ids'] ?? [];
            if (!empty($data['class_room_id'])) {
                $classStudents = Student::query()
                    ->where('class_room_id', $data['class_room_id'])
                    ->where('active', true)
                    ->pluck('id')->all();
                $keepIds = array_values(array_unique(array_merge($keepIds, $classStudents)));
            }
            ExtraClassStudent::where('extra_class_id', $extraClass->id)
                ->whereNotIn('student_id', $keepIds)
                ->delete();
            foreach ($keepIds as $sid) {
                ExtraClassStudent::firstOrCreate([
                    'extra_class_id' => $extraClass->id,
                    'student_id' => $sid,
                ], [ 
                    'amount' => $extraClass->fee,
                    'enrolled_at' => $extraClass->payment_start_date ?: $extraClass->date ?: now()
                ]);
            }
        });

        return redirect()->route('extra-classes.index')->with('status', 'Extra class updated');
    }

    public function show(ExtraClass $extraClass)
    {
        $students = $extraClass->students()->with('student')->paginate(50);
        $teacherPayments = $extraClass->teacherPayments()->orderByDesc('paid_at')->get();
        return view('extra-classes.show', compact('extraClass','students','teacherPayments'));
    }

    public function payments(ExtraClass $extraClass)
    {
        $enrollments = $extraClass->students()->with('student')->orderByDesc('id')->paginate(100);
        return view('extra-classes.payments', compact('extraClass','enrollments'));
    }

    public function updatePayments(Request $request, ExtraClass $extraClass)
    {
        $data = $request->validate([
            'items' => ['required','array'],
            'items.*.id' => ['required','integer','exists:extra_class_students,id'],
            'items.*.paid' => ['nullable','boolean'],
        ]);

        foreach ($data['items'] as $item) {
            $row = ExtraClassStudent::where('extra_class_id', $extraClass->id)->where('id', $item['id'])->first();
            if (!$row) { continue; }
            $row->paid = (bool)($item['paid'] ?? false);
            $row->paid_at = $row->paid ? now() : null;
            $row->save();
        }

        return back()->with('status', 'Class payments updated');
    }

    public function togglePayment(ExtraClass $extraClass, ExtraClassStudent $enrollment)
    {
        if ($enrollment->extra_class_id !== $extraClass->id) {
            abort(404);
        }

        $enrollment->paid = !$enrollment->paid;
        $enrollment->paid_at = $enrollment->paid ? now() : null;
        $enrollment->save();

        return back()->with('status', 'Payment status updated');
    }

    public function removeEnrollment(ExtraClass $extraClass, ExtraClassStudent $enrollment)
    {
        if ($enrollment->extra_class_id !== $extraClass->id) {
            abort(404);
        }

        $enrollment->delete();

        return back()->with('status', 'Enrollment removed');
    }

    public function payDaily(Request $request, ExtraClass $extraClass)
    {
        $data = $request->validate([
            'extra_class_student_id' => ['required', 'integer', 'exists:extra_class_students,id'],
            'days' => ['required', 'integer', 'min:1'],
        ]);

        $enrollment = ExtraClassStudent::where('extra_class_id', $extraClass->id)
            ->where('id', $data['extra_class_student_id'])
            ->firstOrFail();

        $category = \App\Models\RevenueCategory::firstOrCreate(
            ['name' => 'Extra Class Fee'],
            ['payment_type' => 'other', 'active' => true]
        );

        $amountPerDay = $enrollment->amount ?: $extraClass->fee;
        $totalAmount = $amountPerDay * $data['days'];

        DB::transaction(function () use ($enrollment, $totalAmount, $category, $data, $extraClass) {
            // Create Revenue
            $billNo = app(\App\Services\Billing\BillNumberService::class)->nextRevenueBillNumber();
            \App\Models\Revenue::create([
                'bill_no' => $billNo,
                'revenue_category_id' => $category->id,
                'student_id' => $enrollment->student_id,
                'amount' => $totalAmount,
                'paid_at' => now(),
                'notes' => "Payment for {$extraClass->name} - {$data['days']} days",
                'created_by' => Auth::id(),
            ]);

            // Update enrollment
            $enrollment->paid_days += $data['days'];
            $enrollment->paid_at = now();
            // Also mark as 'paid' if it's caught up, or just always mark it?
            // For daily, 'paid' field might not be very useful anymore if we have due_days.
            $enrollment->save();
        });

        return back()->with('status', 'Payment recorded successfully');
    }

    public function storeTeacherPayment(Request $request, ExtraClass $extraClass)
    {
        $data = $request->validate([
            'amount' => ['required','numeric','min:0.01'],
            'paid_at' => ['nullable','date'],
            'notes' => ['nullable','string','max:500'],
        ]);

        $target = (float) ($extraClass->teacher_payment ?? 0);
        if ($target <= 0) {
            return back()->withErrors(['amount' => 'Please configure the teacher payment amount before recording payouts.']);
        }

        $paid = (float) $extraClass->teacherPayments()->sum('amount');
        $due = max(0, $target - $paid);
        if ($due <= 0) {
            return back()->withErrors(['amount' => 'The instructor has already been fully paid for this class.']);
        }

        if ((float) $data['amount'] > $due) {
            return back()->withErrors(['amount' => 'Amount may not exceed the remaining due ('.number_format($due, 2).').']);
        }

        $paidAt = $data['paid_at'] ? Carbon::parse($data['paid_at']) : now();
        $category = $this->teacherExpenseCategory();

        DB::transaction(function () use ($extraClass, $data, $paidAt, $category) {
            $expenseNotes = "Extra class {$extraClass->name} payout";
            if (!empty($data['notes'])) {
                $expenseNotes .= ' • ' . $data['notes'];
            }

            $expense = Expense::create([
                'expense_category_id' => $category->id,
                'amount' => $data['amount'],
                'expense_date' => $paidAt,
                'notes' => $expenseNotes,
                'created_by' => Auth::id(),
            ]);

            ExtraClassTeacherPayment::create([
                'extra_class_id' => $extraClass->id,
                'amount' => $data['amount'],
                'notes' => $data['notes'] ?? null,
                'paid_at' => $paidAt,
                'expense_id' => $expense->id,
            ]);
        });

        return back()->with('status', 'Teacher payment recorded');
    }

    public function destroyTeacherPayment(ExtraClass $extraClass, ExtraClassTeacherPayment $payment)
    {
        if ($payment->extra_class_id !== $extraClass->id) {
            abort(404);
        }

        DB::transaction(function () use ($payment) {
            $expense = $payment->expense;
            $payment->delete();
            if ($expense) {
                $expense->delete();
            }
        });
        return back()->with('status', 'Teacher payment deleted');
    }

    public function destroy(ExtraClass $extraClass)
    {
        $extraClass->delete();
        return redirect()->route('extra-classes.index')->with('status', 'Extra class deleted');
    }
}
