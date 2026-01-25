<?php

namespace App\Models;

use App\Models\Expense;
use App\Models\ExtraClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraClassTeacherPayment extends Model
{
    protected $fillable = [
        'extra_class_id',
        'amount',
        'notes',
        'paid_at',
        'expense_id',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'expense_id' => 'integer',
    ];

    public function extraClass(): BelongsTo
    {
        return $this->belongsTo(ExtraClass::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
