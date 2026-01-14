<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraClassStudent extends Model
{
    protected $fillable = [
        'extra_class_id',
        'student_id',
        'paid',
        'paid_days',
        'enrolled_at',
        'amount',
        'paid_at',
    ];

    protected $casts = [
        'paid' => 'boolean',
        'paid_days' => 'integer',
        'enrolled_at' => 'date',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'extra_class_id' => 'integer',
        'student_id' => 'integer',
    ];

    public function getDueDaysAttribute()
    {
        if ($this->extraClass->payment_type !== 'daily') {
            return $this->paid ? 0 : 1;
        }

        $startDate = $this->enrolled_at ?: $this->extraClass->payment_start_date ?: $this->extraClass->date;
        if (!$startDate) return 0;

        $today = now()->startOfDay();
        $start = $startDate->startOfDay();

        if ($today->lt($start)) return 0;

        $totalDays = $start->diffInDays($today) + 1;
        return max(0, $totalDays - $this->paid_days);
    }

    public function getDueAmountAttribute()
    {
        return $this->due_days * ($this->amount ?: $this->extraClass->fee);
    }

    public function extraClass(): BelongsTo
    {
        return $this->belongsTo(ExtraClass::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
