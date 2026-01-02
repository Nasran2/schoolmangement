<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revenue extends Model
{
    protected $fillable = [
        'bill_no',
        'revenue_category_id',
        'student_id',
        'amount',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::deleting(function ($revenue) {
            $revenue->allocations()->delete();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RevenueCategory::class, 'revenue_category_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Month-wise allocations for this revenue (monthly fee payments).
     */
    public function allocations()
    {
        return $this->hasMany(StudentMonthFeeAllocation::class);
    }
}
