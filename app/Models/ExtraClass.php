<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtraClass extends Model
{
    protected $fillable = [
        'name',
        'date',
        'start_time',
        'end_time',
        'payment_type',
        'fee',
        'teacher_payment',
        'payment_start_date',
        'class_room_id',
        'visiting_teacher_id',
        'active',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'fee' => 'decimal:2',
        'teacher_payment' => 'decimal:2',
        'payment_start_date' => 'date',
        'active' => 'boolean',
        'class_room_id' => 'integer',
        'visiting_teacher_id' => 'integer',
    ];

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(ExtraClassStudent::class);
    }
}
