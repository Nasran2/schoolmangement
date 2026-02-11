<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'expense_category_id',
        'amount',
        'payment_method',
        'payment_meta',
        'cheque_date',
        'expense_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'cheque_date' => 'date',
        'payment_meta' => 'array',
        'amount' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
