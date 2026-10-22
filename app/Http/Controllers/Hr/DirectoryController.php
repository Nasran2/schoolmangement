<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherEvaluation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectoryController extends Controller
{
    public const TEACHER_FIELDS = ['id', 'name', 'email', 'phone', 'address', 'joining_date', 'assigned_classes', 'active'];

    public const STUDENT_FIELDS = [
        'id', 'admission_number', 'name', 'first_name', 'other_names', 'name_with_initial',
        'address', 'phone', 'whatsapp_number', 'gender', 'date_of_birth', 'guardian_name',
        'guardian_phone', 'guardian_relationship', 'joining_date', 'class', 'class_room_id',
        'father_name_with_initial', 'father_phone', 'mother_name_with_initial', 'mother_phone',
        'active', 'alumni',
    ];

    public function index(Request $request): View
    {
        $students = $request->routeIs('hr.students.index');
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'active' => ['nullable', 'in:0,1'],
            'class_room_id' => ['nullable', 'integer', 'exists:class_rooms,id'],
        ]);
        $query = $students ? Student::query()->select(self::STUDENT_FIELDS)->with('classRoom:id,name') : Teacher::query()->select(self::TEACHER_FIELDS);
        $query->when($filters['q'] ?? null, function ($query, $term) use ($students) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('phone', 'like', '%'.$term.'%')
                ->when($students, fn ($q) => $q->orWhere('admission_number', 'like', '%'.$term.'%')));
        })->when(isset($filters['active']), fn ($q) => $q->where('active', $filters['active']))
            ->when($students && ! empty($filters['class_room_id']), fn ($q) => $q->where('class_room_id', $filters['class_room_id']));

        return view('hr.directory', [
            'students' => $students, 'records' => $query->orderBy('name')->paginate(20)->withQueryString(),
            'classrooms' => ClassRoom::orderBy('name')->get(['id', 'name']),
            'stats' => ['total' => (clone $query)->count(), 'active' => (clone $query)->where('active', true)->count()],
        ]);
    }

    public function teacher(Request $request, int $teacher): View
    {
        $person = Teacher::select(self::TEACHER_FIELDS)->findOrFail($teacher);
        $history = collect();
        $evaluationStats = ['annual' => 0, 'event' => 0, 'completed' => 0];
        if ($request->user()->can('teacher_evaluations.view')) {
            $base = TeacherEvaluation::where('teacher_id', $person->id);
            foreach (['annual', 'event'] as $type) {
                $evaluationStats[$type] = (clone $base)->where('type', $type)->count();
            }
            $evaluationStats['completed'] = (clone $base)->where('status', 'completed')->count();
            $history = $base->orderByDesc('review_date')->orderByDesc('id')->limit(6)
                ->get(['id', 'teacher_id', 'teacher_name', 'type', 'appraisal_year', 'event_name', 'event_date', 'review_date', 'score', 'status']);
        }

        return view('hr.details', ['isStudent' => false, 'person' => $person, 'history' => $history, 'evaluationStats' => $evaluationStats]);
    }

    public function student(int $student): View
    {
        return view('hr.details', ['isStudent' => true, 'person' => Student::select(self::STUDENT_FIELDS)->with('classRoom:id,name')->findOrFail($student)]);
    }
}
