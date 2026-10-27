<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassFeeSchedule extends Model
{
    public const TYPES = ['exam' => 'Exam Fee', 'computer' => 'Computer Fee', 'facility' => 'Facility Fee'];

    protected $fillable = ['class_room_id', 'revenue_category_id', 'fee_type', 'amount', 'first_due_date', 'ends_on', 'terms'];

    protected $casts = ['amount' => 'decimal:2', 'first_due_date' => 'date', 'ends_on' => 'date', 'terms' => 'array'];

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }

    public function intervalMonths(): int
    {
        return in_array($this->fee_type, ['exam', 'computer'], true) ? ($this->terms ? 4 : 3) : 12;
    }
}
