<?php

namespace App\Models;

use App\Models\ExtraClass;
use App\Models\Seminar;
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

    public function extraClasses(): HasMany
    {
        return $this->hasMany(ExtraClass::class, 'visiting_teacher_id');
    }
}
