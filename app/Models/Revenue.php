<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\RevenueAdjustment;
use App\Models\User;

class Revenue extends Model
{
    protected $fillable = [
        'bill_no',
        'revenue_category_id',
        'student_id',
        'amount',
        'payment_method',
        'payment_status',
        'payment_meta',
        'cheque_date',
        'confirmed_at',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'cheque_date' => 'date',
        'confirmed_at' => 'datetime',
        'payment_meta' => 'array',
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
    public function allocations(): HasMany
    {
        return $this->hasMany(StudentMonthFeeAllocation::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(RevenueAdjustment::class);
    }

    public function refunds(): HasMany
    {
        return $this->adjustments()->where('type', 'refund');
    }

    public function waivers(): HasMany
    {
        return $this->adjustments()->where('type', 'waiver');
    }
}
