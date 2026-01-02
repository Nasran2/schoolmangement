<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMonthFeeAllocation extends Model
{
    protected $fillable = [
        'revenue_id',
        'student_id',
        'month',
        'year',
        'type',
        'applied_amount',
        'is_partial',
        'remaining_for_month',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'applied_amount' => 'decimal:2',
        'remaining_for_month' => 'decimal:2',
        'is_partial' => 'boolean',
    ];

    public function revenue(): BelongsTo
    {
        return $this->belongsTo(Revenue::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
