<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMonthlyFeeOverride extends Model
{
    protected $fillable = [
        'student_id',
        'year',
        'month',
        'fee_amount',
        'set_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'fee_amount' => 'decimal:2',
        'set_by' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function setter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }
}
