<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMonthlyFeeCredit extends Model
{
    protected $table = 'student_month_fee_credits';

    protected $fillable = [
        'student_id',
        'year',
        'month',
        'amount',
        'applied_at',
        'note',
        'created_by',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'year' => 'integer',
        'month' => 'integer',
        'amount' => 'decimal:2',
        'applied_at' => 'date',
        'created_by' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
