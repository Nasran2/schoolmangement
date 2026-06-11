<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSalaryAdvanceSettlement extends Model
{
    protected $fillable = [
        'teacher_salary_advance_id',
        'teacher_salary_payment_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function advance(): BelongsTo
    {
        return $this->belongsTo(TeacherSalaryAdvance::class, 'teacher_salary_advance_id');
    }

    public function salaryPayment(): BelongsTo
    {
        return $this->belongsTo(TeacherSalaryPayment::class, 'teacher_salary_payment_id');
    }
}
