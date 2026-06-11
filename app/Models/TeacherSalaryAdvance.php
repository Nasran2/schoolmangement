<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherSalaryAdvance extends Model
{
    protected $fillable = [
        'teacher_id',
        'expense_id',
        'amount',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
        'expense_id' => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(TeacherSalaryAdvanceSettlement::class);
    }
}
