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
        'interval_months',
        'applies_to_all',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'applies_to_all' => 'boolean',
        'interval_months' => 'integer',
    ];

    public function isRecurring(): bool
    {
        return !is_null($this->interval_months);
    }

    public function intervalMonths(): ?int
    {
        if (!is_null($this->interval_months)) {
            return (int) $this->interval_months;
        }

        $type = strtolower((string) $this->payment_type);
        return match ($type) {
            'monthly' => 1,
            '2_months' => 2,
            '3_months' => 3,
            '6_months' => 6,
            'yearly' => 12,
            default => null,
        };
    }

    public function classRooms(): BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'class_room_revenue_category');
    }

    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }
}
