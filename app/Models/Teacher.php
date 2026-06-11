<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'address',
        'phone',
        'joining_date',
        'payment_start_date',
        'assigned_classes',
        'salary_amount',
        'salary_components',
        'epf_enabled',
        'etf_enabled',
        'active',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'payment_start_date' => 'date',
        'salary_amount' => 'decimal:2',
        'salary_components' => 'array',
        'epf_enabled' => 'boolean',
        'etf_enabled' => 'boolean',
        'active' => 'boolean',
    ];

    public function salaryPayments(): HasMany
    {
        return $this->hasMany(TeacherSalaryPayment::class);
    }

    public function salaryAdvances(): HasMany
    {
        return $this->hasMany(TeacherSalaryAdvance::class);
    }
}
