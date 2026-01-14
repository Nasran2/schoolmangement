<?php

namespace App\Http\Controllers;

use App\Models\Seminar;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\SeminarStudent;
use App\Models\VisitingTeacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeminarController extends Controller
{
    public function index(Request $request)
    {
        $seminarsQuery = Seminar::query()->with('visitingTeacher')->latest();

        $term = (string) ($request->query('q') ?? $request->query('search') ?? '');
        $term = trim($term);
        if ($term !== '') {
            $like = '%' . str_replace('%', '\\%', $term) . '%';
            $seminarsQuery->where(function ($sub) use ($like) {
                $sub->where('name', 'like', $like)
                    ->orWhereHas('visitingTeacher', function ($t) use ($like) {
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
        $visitingTeachers = VisitingTeacher::query()->orderBy('name')->get();
        return view('seminars.create', compact('classRooms', 'students', 'visitingTeachers'));
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
            'visiting_teacher_id' => ['nullable','integer','exists:visiting_teachers,id'],
            'notes' => ['nullable','string'],
        ]);

        DB::transaction(function () use ($data) {
            $seminar = Seminar::create($data);
            if (!empty($data['class_room_ids'])) {
                $seminar->classRooms()->sync($data['class_room_ids']);

                // Auto-enroll students from selected classrooms
                $studentsFromClasses = Student::query()
                    ->whereIn('class_room_id', $data['class_room_ids'])
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
        $visitingTeachers = VisitingTeacher::query()->orderBy('name')->get();
        $selectedClassRooms = $seminar->classRooms()->pluck('id')->all();
        $enrolledStudentIds = $seminar->students()->pluck('student_id')->all();
        return view('seminars.edit', compact('seminar','classRooms','students','visitingTeachers','selectedClassRooms','enrolledStudentIds'));
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
            'visiting_teacher_id' => ['nullable','integer','exists:visiting_teachers,id'],
            'notes' => ['nullable','string'],
        ]);

        DB::transaction(function () use ($data, $seminar) {
            $seminar->update($data);
            $seminar->classRooms()->sync($data['class_room_ids'] ?? []);

            // Re-sync students (preserve present/paid where possible)
            $keepIds = [];
            if (!empty($data['class_room_ids'])) {
                $fromClasses = Student::query()
                    ->whereIn('class_room_id', $data['class_room_ids'])
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
        $students = $seminar->students()->with('student')->paginate(50);
        return view('seminars.show', compact('seminar','students'));
    }

    public function destroy(Seminar $seminar)
    {
        $seminar->delete();
        return redirect()->route('seminars.index')->with('status', 'Seminar deleted');
    }
}
