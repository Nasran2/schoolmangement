<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeminarStudent extends Model
{
    protected $fillable = [
        'seminar_id',
        'student_id',
        'present',
        'paid',
        'amount',
        'paid_at',
    ];

    protected $casts = [
        'present' => 'boolean',
        'paid' => 'boolean',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'seminar_id' => 'integer',
        'student_id' => 'integer',
    ];

    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
