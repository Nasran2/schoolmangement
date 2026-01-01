<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

use App\Models\Revenue;

class Student extends Model
{
    protected $fillable = [
        'admission_number',
        'name',
        'first_name',
        'other_names',
        'name_with_initial',
        'address',
        'parent_address',
        'phone',
        'whatsapp_number',
        'gender',
        'date_of_birth',
        'guardian_name',
        'guardian_phone',
        'guardian_relationship',
        'use_guardian',
        'joining_date',
        'fee_start_date',
        'year',
        'class',
        'class_room_id',
        'promoted_until_year',
        'religion',
        'desired_class',
        'medical_history',
        'long_term_medication',
        'learning_disabilities',
        'previous_school',
        'previous_grade',
        'siblings',
        'has_siblings_in_college',
        'father_name_with_initial',
        'father_nic_passport',
        'father_religion',
        'father_nationality',
        'father_occupation',
        'father_phone',
        'father_whatsapp',
        'father_office_phone',
        'father_emergency_number',
        'mother_name_with_initial',
        'mother_nic_passport',
        'mother_religion',
        'mother_nationality',
        'mother_occupation',
        'mother_phone',
        'mother_whatsapp',
        'mother_office_phone',
        'mother_emergency_number',
        'passport_photo_path',
        'admission_agree',
        'monthly_fee',
        'due_amount',
        'active',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'fee_start_date' => 'date',
        'date_of_birth' => 'date',
        'monthly_fee' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'active' => 'boolean',
        'class_room_id' => 'integer',
        'promoted_until_year' => 'integer',
        'long_term_medication' => 'boolean',
        'learning_disabilities' => 'boolean',
        'has_siblings_in_college' => 'boolean',
        'admission_agree' => 'boolean',
        'use_guardian' => 'boolean',
    ];

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }

    /**
     * Compute current net due for monthly fees based on fee_start_date cycles and payments.
     */
    public function computeMonthlyDue(): float
    {
        $monthlyFee = (float) $this->monthly_fee;
        if ($monthlyFee <= 0) return 0.0;

        $monthsDue = 1;
        if ($this->fee_start_date) {
            $start = Carbon::parse($this->fee_start_date)->startOfDay();
            $now = now();
            $monthsDue = $now->lt($start) ? 0 : (int) ($start->diffInMonths($now) + 1);
        }

        $expected = $monthlyFee * max(0, (int) $monthsDue);
        $monthlyCatId = $this->classRoom?->monthly_fee_revenue_category_id;
        $paid = 0.0;
        if ($monthlyCatId) {
            $paid = (float) Revenue::query()
                ->where('student_id', $this->id)
                ->where('revenue_category_id', $monthlyCatId)
                ->sum('amount');
        }
        return max(0.0, $expected - $paid);
    }

    public function getComputedDueAmountAttribute(): float
    {
        return $this->computeMonthlyDue();
    }

    public function monthlyFeeCategoryId(): ?int
    {
        return $this->classRoom?->monthly_fee_revenue_category_id;
    }

    public function monthlyCyclesCountToNow(): int
    {
        if (! $this->fee_start_date) return 0;
        $start = Carbon::parse($this->fee_start_date)->startOfDay();
        $now = now();
        return $now->lt($start) ? 0 : (int) ($start->diffInMonths($now) + 1);
    }

    public function monthlyCyclesToNow(): array
    {
        $cycles = [];
        $count = $this->monthlyCyclesCountToNow();
        if ($count <= 0) return $cycles;
        $start = Carbon::parse($this->fee_start_date)->startOfDay();
        $now = now();
        for ($i = 0; $i < $count; $i++) {
            $s = $start->copy()->addMonthsNoOverflow($i);
            $e = $start->copy()->addMonthsNoOverflow($i + 1);
            $cycles[] = [
                'start' => $s,
                'end' => $e,
                'inProgress' => $now->betweenIncluded($s, $e),
            ];
        }
        return $cycles;
    }

    public function monthlyFeePaidAmount(): float
    {
        $catId = $this->monthlyFeeCategoryId();
        if (! $catId) return 0.0;
        return (float) Revenue::query()
            ->where('student_id', $this->id)
            ->where('revenue_category_id', $catId)
            ->sum('amount');
    }

    public function monthlyFeePaidCyclesCount(): int
    {
        $fee = (float) $this->monthly_fee;
        if ($fee <= 0) return 0;
        return (int) floor($this->monthlyFeePaidAmount() / $fee);
    }
}
