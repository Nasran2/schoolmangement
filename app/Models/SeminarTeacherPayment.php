<?php

namespace App\Models;

use App\Models\Expense;
use App\Models\Seminar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeminarTeacherPayment extends Model
{
    protected $fillable = [
        'seminar_id',
        'amount',
        'notes',
        'paid_at',
        'expense_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expense_id' => 'integer',
    ];

    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
