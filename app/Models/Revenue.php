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
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'paid_at',
        'notes',
        'created_by',
        'class_fee_schedule_id',
        'class_fee_due_date',
        'class_fee_submission_id',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'cheque_date' => 'date',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'payment_meta' => 'array',
        'amount' => 'decimal:2',
        'class_fee_due_date' => 'date',
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

    public function classFeeSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassFeeSchedule::class);
    }

    public function getExamTermLabelAttribute(): ?string
    {
        if (! $this->class_fee_schedule_id || ! $this->class_fee_due_date) {
            return null;
        }
        $schedule = $this->classFeeSchedule;

        return $schedule ? app(\App\Services\Billing\ClassFeeService::class)->examTermLabel($schedule, $this->class_fee_due_date) : null;
    }

    public function getDisplayNotesAttribute(): string
    {
        $notes = trim((string) ($this->attributes['notes'] ?? ''));
        $term = $this->exam_term_label;
        if (! $term) {
            return $notes;
        }
        // Keep staff notes while replacing old generated descriptions, including converted calendars.
        $notes = preg_replace('/^(?:Exam|Computer) Fee(?: installment due \d{4}-\d{2}-\d{2}|\s*[—-]\s*Term \d+ \(\d{4}\))(?:\s*[—-]\s*)?/u', '', $notes);

        return ($this->classFeeSchedule->fee_type === 'computer' ? 'Computer Fee' : 'Exam Fee').' — '.$term.($notes !== '' ? ' — '.$notes : '');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isCancelled(): bool
    {
        return (string) ($this->payment_status ?? '') === 'cancelled' || $this->cancelled_at !== null;
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
