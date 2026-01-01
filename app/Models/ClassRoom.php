<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClassRoom extends Model
{
    use HasFactory;

    protected $table = 'class_rooms';

    protected $fillable = [
        'name',
        'level',
        'description',
        'active',
        'monthly_fee',
        'monthly_fee_revenue_category_id',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'level' => 'integer',
            'monthly_fee' => 'decimal:2',
            'monthly_fee_revenue_category_id' => 'integer',
        ];
    }

    public function revenueCategories(): BelongsToMany
    {
        return $this->belongsToMany(RevenueCategory::class, 'class_room_revenue_category');
    }

    public function monthlyFeeCategory(): BelongsTo
    {
        return $this->belongsTo(RevenueCategory::class, 'monthly_fee_revenue_category_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_room_id');
    }
}
