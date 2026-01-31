<?php

namespace App\Http\Controllers;

use App\Models\Seminar;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\SeminarStudent;
use App\Models\SeminarTeacherPayment;
use App\Models\Expense;
use App\Models\Teacher;
use App\Models\VisitingTeacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SeminarController extends Controller
{
    public function index(Request $request)
    {
        $seminarsQuery = Seminar::query()->with(['visitingTeacher', 'teacher'])->latest();

        $term = (string) ($request->query('q') ?? $request->query('search') ?? '');
        $term = trim($term);
        if ($term !== '') {
            $like = '%' . str_replace('%', '\\%', $term) . '%';
            $seminarsQuery->where(function ($sub) use ($like) {
                $sub->where('name', 'like', $like)
                    ->orWhereHas('visitingTeacher', function ($t) use ($like) {
                        $t->where('name', 'like', $like);
                    })
                    ->orWhereHas('teacher', function ($t) use ($like) {
                        $t->where('name', 'like', $like);
                    });
            });
        }

        $seminars = $seminarsQuery->paginate(20)->withQueryString();

        return view('seminars.index', compact('seminars'));
    }

    public function create()
    {
        $classRooms = ClassRoom::query()->orderBy('name')->get();
        $students = Student::query()->orderBy('name')->limit(200)->get();
        return view('seminars.create', compact('classRooms', 'students'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'date' => ['required','date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'fee_per_student' => ['required','numeric','min:0'],
            'teacher_payment' => ['nullable','numeric','min:0'],
            'class_room_id' => ['nullable','integer','exists:class_rooms,id'],
            'class_room_ids' => ['array'],
            'class_room_ids.*' => ['integer','exists:class_rooms,id'],
            'student_ids' => ['array'],
            'student_ids.*' => ['integer','exists:students,id'],
            'teacher_id' => ['nullable','integer','exists:teachers,id','prohibits:visiting_teacher_id'],
            'visiting_teacher_id' => ['nullable','integer','exists:visiting_teachers,id','prohibits:teacher_id'],
            'notes' => ['nullable','string'],
        ]);

        DB::transaction(function () use ($data) {
            // Ensure only one type is stored.
            if (!empty($data['teacher_id'])) {
                $data['visiting_teacher_id'] = null;
            }
            if (!empty($data['visiting_teacher_id'])) {
                $data['teacher_id'] = null;
            }

            $seminar = Seminar::create($data);

            // Additional classrooms (separate from primary classroom)
            if (!empty($data['class_room_ids'])) {
                $seminar->classRooms()->sync($data['class_room_ids']);
            }

            // Auto-enroll students from the primary classroom + any additional classrooms
            $allClassRoomIds = [];
            if (!empty($data['class_room_id'])) {
                $allClassRoomIds[] = (int) $data['class_room_id'];
            }
            if (!empty($data['class_room_ids'])) {
                $allClassRoomIds = array_merge($allClassRoomIds, array_map('intval', $data['class_room_ids']));
            }
            $allClassRoomIds = array_values(array_unique(array_filter($allClassRoomIds)));

            if (!empty($allClassRoomIds)) {
                $studentsFromClasses = Student::query()
                    ->whereIn('class_room_id', $allClassRoomIds)
                    ->where('active', true)
                    ->pluck('id')
                    ->all();

                foreach ($studentsFromClasses as $sid) {
                    SeminarStudent::firstOrCreate([
                        'seminar_id' => $seminar->id,
                        'student_id' => $sid,
                    ], [
                        'amount' => $seminar->fee_per_student,
                    ]);
                }
            }

            if (!empty($data['student_ids'])) {
                foreach ($data['student_ids'] as $sid) {
                    SeminarStudent::firstOrCreate([
                        'seminar_id' => $seminar->id,
                        'student_id' => $sid,
                    ], [
                        'amount' => $seminar->fee_per_student,
                    ]);
                }
            }
        });

        return redirect()->route('seminars.index')->with('status', 'Seminar created');
    }

    public function edit(Seminar $seminar)
    {
        $classRooms = ClassRoom::query()->orderBy('name')->get();
        $students = Student::query()->orderBy('name')->limit(200)->get();
        $selectedClassRooms = $seminar->classRooms()->pluck('id')->all();
        $enrolledStudentIds = $seminar->students()->pluck('student_id')->all();
        return view('seminars.edit', compact('seminar','classRooms','students','selectedClassRooms','enrolledStudentIds'));
    }

    public function update(Request $request, Seminar $seminar)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'date' => ['required','date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'fee_per_student' => ['required','numeric','min:0'],
            'teacher_payment' => ['nullable','numeric','min:0'],
            'class_room_id' => ['nullable','integer','exists:class_rooms,id'],
            'class_room_ids' => ['array'],
            'class_room_ids.*' => ['integer','exists:class_rooms,id'],
            'student_ids' => ['array'],
            'student_ids.*' => ['integer','exists:students,id'],
            'teacher_id' => ['nullable','integer','exists:teachers,id','prohibits:visiting_teacher_id'],
            'visiting_teacher_id' => ['nullable','integer','exists:visiting_teachers,id','prohibits:teacher_id'],
            'notes' => ['nullable','string'],
        ]);

        // Ensure only one type is stored.
        if (!empty($data['teacher_id'])) {
            $data['visiting_teacher_id'] = null;
        }
        if (!empty($data['visiting_teacher_id'])) {
            $data['teacher_id'] = null;
        }

        DB::transaction(function () use ($data, $seminar) {
            $seminar->update($data);
            $seminar->classRooms()->sync($data['class_room_ids'] ?? []);

            // Re-sync students (preserve present/paid where possible)
            $keepIds = [];

            $allClassRoomIds = [];
            if (!empty($data['class_room_id'])) {
                $allClassRoomIds[] = (int) $data['class_room_id'];
            }
            if (!empty($data['class_room_ids'])) {
                $allClassRoomIds = array_merge($allClassRoomIds, array_map('intval', $data['class_room_ids']));
            }
            $allClassRoomIds = array_values(array_unique(array_filter($allClassRoomIds)));

            if (!empty($allClassRoomIds)) {
                $fromClasses = Student::query()
                    ->whereIn('class_room_id', $allClassRoomIds)
                    ->where('active', true)
                    ->pluck('id')->all();
                $keepIds = array_merge($keepIds, $fromClasses);
            }
            if (!empty($data['student_ids'])) {
                $keepIds = array_merge($keepIds, $data['student_ids']);
            }
            $keepIds = array_values(array_unique($keepIds));

            // Remove not in keepIds
            SeminarStudent::where('seminar_id', $seminar->id)
                ->whereNotIn('student_id', $keepIds)
                ->delete();

            // Ensure presence of keepIds
            foreach ($keepIds as $sid) {
                SeminarStudent::firstOrCreate([
                    'seminar_id' => $seminar->id,
                    'student_id' => $sid,
                ], [
                    'amount' => $seminar->fee_per_student,
                ]);
            }
        });

        return redirect()->route('seminars.index')->with('status', 'Seminar updated');
    }

    public function show(Seminar $seminar)
    {
        $seminar->syncEnrollmentsFromClassroomsIfEmpty();

        $students = $seminar->students()->with('student')->paginate(50);
        $teacherPayments = $seminar->teacherPayments()->orderByDesc('paid_at')->get();
        $teacherTarget = (float) ($seminar->teacher_payment ?? 0);
        $teacherPaidTotal = $teacherPayments->sum('amount');
        $teacherDueTotal = max(0, $teacherTarget - $teacherPaidTotal);

        $expectedTotal = (float) $seminar->students()->sum('amount');
        $collectedTotal = (float) $seminar->students()->where('paid', true)->sum('amount');
        $profitAfterPayouts = $collectedTotal - (float) $teacherPaidTotal;

        return view('seminars.show', compact(
            'seminar',
            'students',
            'teacherPayments',
            'teacherTarget',
            'teacherPaidTotal',
            'teacherDueTotal',
            'expectedTotal',
            'collectedTotal',
            'profitAfterPayouts'
        ));
    }

    public function storeTeacherPayment(Request $request, Seminar $seminar)
    {
        $data = $request->validate([
            'amount' => ['required','numeric','min:0.01'],
            'paid_at' => ['nullable','date'],
            'notes' => ['nullable','string','max:500'],
        ]);

        $target = (float) ($seminar->teacher_payment ?? 0);
        if ($target <= 0) {
            return back()->withErrors(['amount' => 'Please configure the teacher payment amount before recording payouts.']);
        }

        $paid = (float) $seminar->teacherPayments()->sum('amount');
        $due = max(0, $target - $paid);
        if ($due <= 0) {
            return back()->withErrors(['amount' => 'The instructor has already been fully paid for this seminar.']);
        }

        if ((float) $data['amount'] > $due) {
            return back()->withErrors(['amount' => 'Amount may not exceed the remaining due ('.number_format($due, 2).').']);
        }

        $paidAt = $data['paid_at'] ? Carbon::parse($data['paid_at']) : now();
        $category = $this->teacherExpenseCategory();

        DB::transaction(function () use ($seminar, $data, $paidAt, $category) {
            $expenseNotes = "Seminar {$seminar->name} payout";
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

            SeminarTeacherPayment::create([
                'seminar_id' => $seminar->id,
                'amount' => $data['amount'],
                'notes' => $data['notes'] ?? null,
                'paid_at' => $paidAt,
                'expense_id' => $expense->id,
            ]);
        });

        return back()->with('status', 'Teacher payment recorded');
    }

    public function destroyTeacherPayment(Seminar $seminar, SeminarTeacherPayment $payment)
    {
        if ($payment->seminar_id !== $seminar->id) {
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

    public function destroy(Seminar $seminar)
    {
        $seminar->delete();
        return redirect()->route('seminars.index')->with('status', 'Seminar deleted');
    }
}
