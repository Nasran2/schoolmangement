<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitingTeacher extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'specialty',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function seminars(): HasMany
    {
        return $this->hasMany(Seminar::class, 'visiting_teacher_id');
    }
}
