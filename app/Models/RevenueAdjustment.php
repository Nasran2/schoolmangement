<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class RevenueAdjustment extends Model
{
    protected $fillable = [
        'revenue_id',
        'student_id',
        'type',
        'amount',
        'reason',
        'effective_month',
        'effective_year',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'effective_month' => 'integer',
        'effective_year' => 'integer',
    ];

    public function revenue(): BelongsTo
    {
        return $this->belongsTo(Revenue::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
