<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSalaryPayment extends Model
{
    protected $fillable = [
        'teacher_id',
        'amount',
        'base_salary',
        'deductions',
        'total_deductions',
        'paid_at',
        'payment_month',
        'receipt_number',
        'payment_method',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'amount' => 'decimal:2',
        'base_salary' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'deductions' => 'array',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (!$payment->receipt_number) {
                $payment->receipt_number = 'SAL-' . strtoupper(uniqid());
            }
        });
    }
}
