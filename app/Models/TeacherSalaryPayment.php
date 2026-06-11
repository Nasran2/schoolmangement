<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherSalaryPayment extends Model
{
    protected $fillable = [
        'teacher_id',
        'amount',
        'base_salary',
        'deductions',
        'total_deductions',
        'employee_epf_amount',
        'employer_epf_amount',
        'employer_etf_amount',
        'paid_at',
        'payment_month',
        'receipt_number',
        'payment_method',
        'bank_name',
        'bank_branch',
        'bank_account_no',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'amount' => 'decimal:2',
        'base_salary' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'employee_epf_amount' => 'decimal:2',
        'employer_epf_amount' => 'decimal:2',
        'employer_etf_amount' => 'decimal:2',
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

    public function advanceSettlements(): HasMany
    {
        return $this->hasMany(TeacherSalaryAdvanceSettlement::class);
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
