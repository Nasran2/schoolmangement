<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function visitingTeacher(): BelongsTo
    {
        return $this->belongsTo(VisitingTeacher::class, 'visiting_teacher_id');
    }
}
