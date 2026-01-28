<?php

namespace App\Models;

use App\Models\SeminarTeacherPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seminar extends Model
{
    protected $fillable = [
        'name',
        'date',
        'start_time',
        'end_time',
        'fee_per_student',
        'teacher_payment',
        'class_room_id',
        'visiting_teacher_id',
        'teacher_id',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'fee_per_student' => 'decimal:2',
        'teacher_payment' => 'decimal:2',
        'class_room_id' => 'integer',
        'visiting_teacher_id' => 'integer',
        'teacher_id' => 'integer',
    ];

    public function classRooms(): BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'seminar_class_room');
    }

    public function primaryClassRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(SeminarStudent::class);
    }

    public function syncEnrollmentsFromClassroomsIfEmpty(): void
    {
        if ($this->students()->exists()) {
            return;
        }

        $classRoomIds = [];
        if (!empty($this->class_room_id)) {
            $classRoomIds[] = (int) $this->class_room_id;
        }

        $extraIds = $this->classRooms()->pluck('class_rooms.id')->all();
        if (!empty($extraIds)) {
            $classRoomIds = array_merge($classRoomIds, array_map('intval', $extraIds));
        }

        $classRoomIds = array_values(array_unique(array_filter($classRoomIds)));
        if (empty($classRoomIds)) {
            return;
        }

        $studentIds = Student::query()
            ->whereIn('class_room_id', $classRoomIds)
            ->where('active', true)
            ->pluck('id')
            ->all();

        foreach ($studentIds as $sid) {
            SeminarStudent::firstOrCreate([
                'seminar_id' => $this->id,
                'student_id' => $sid,
            ], [
                'amount' => $this->fee_per_student,
            ]);
        }
    }

    public function visitingTeacher(): BelongsTo
    {
        return $this->belongsTo(VisitingTeacher::class, 'visiting_teacher_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function teacherPayments(): HasMany
    {
        return $this->hasMany(SeminarTeacherPayment::class);
    }
}
