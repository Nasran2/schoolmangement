<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RevenueCategory extends Model
{
    protected $fillable = [
        'name',
        'payment_type',
        'applies_to_all',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'applies_to_all' => 'boolean',
    ];

    public function classRooms(): BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'class_room_revenue_category');
    }

    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }
}
