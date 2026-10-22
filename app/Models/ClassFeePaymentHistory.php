<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/** A settled fee recorded without receiving income again. */
class ClassFeePaymentHistory extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2', 'paid_at' => 'date',
        'class_fee_due_date' => 'date', 'cancelled_at' => 'datetime',
    ];

    public function classFeeSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassFeeSchedule::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function getAdjustmentsAttribute(): Collection
    {
        return collect();
    }

    public function getPaymentStatusAttribute(): string
    {
        return $this->isCancelled() ? 'cancelled' : 'confirmed';
    }
}
