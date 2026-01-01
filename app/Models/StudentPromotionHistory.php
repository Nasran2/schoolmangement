<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPromotionHistory extends Model
{
    protected $fillable = [
        'student_id',
        'from_class_room_id',
        'to_class_room_id',
        'action',
        'academic_year',
        'performed_by',
        'notes',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromClassRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'from_class_room_id');
    }

    public function toClassRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'to_class_room_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
