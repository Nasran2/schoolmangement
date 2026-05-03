<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

use App\Models\Revenue;
use App\Models\RevenueAdjustment;
use App\Models\RevenueCategory;

class Student extends Model
{
    use HasFactory;

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
        'nationality',
        'hear_about_us',
        'leaving_docs_issued',
        'alumni',
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
        'alumni' => 'boolean',
        'leaving_docs_issued' => 'boolean',
    ];

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }

    public function promotionHistories(): HasMany
    {
        return $this->hasMany(StudentPromotionHistory::class);
    }

    public function monthlyFeeOverrides(): HasMany
    {
        return $this->hasMany(StudentMonthlyFeeOverride::class);
    }

    /**
     * Compute current net due for monthly fees based on fee_start_date cycles and payments.
     */
    public function computeMonthlyDue(): float
    {
        $startDate = $this->fee_start_date ?: $this->joining_date ?: optional($this->created_at)->toDateString();
        if (! $startDate) return 0.0;

        $allocator = app(\App\Services\Billing\MonthlyFeeAllocator::class);
        $ledger = $allocator->buildLedger($this, 0);
        if (empty($ledger)) return 0.0;

        $expectedBase = 0.0;
        foreach ($ledger as $m) {
            $expectedBase += (float) ($m['due'] ?? 0);
        }

        $monthlyCatId = $this->monthlyFeeCategoryId();
        $paidGross = 0.0;
        $refunds = 0.0;
        $waivers = 0.0;

        if ($monthlyCatId) {
            $paidGross = (float) Revenue::query()
                ->where('student_id', $this->id)
                ->where('revenue_category_id', $monthlyCatId)
                ->where(function ($q) {
                    $q->whereNull('payment_status')
                        ->orWhere('payment_status', 'confirmed');
                })
                ->sum('amount');

            $refunds = (float) RevenueAdjustment::query()
                ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
                ->where('revenues.student_id', $this->id)
                ->where('revenues.revenue_category_id', $monthlyCatId)
                ->where(function ($q) {
                    $q->whereNull('revenues.payment_status')
                        ->orWhere('revenues.payment_status', 'confirmed');
                })
                ->where('revenue_adjustments.type', 'refund')
                ->sum('revenue_adjustments.amount');

            $waivers = (float) RevenueAdjustment::query()
                ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
                ->where('revenues.student_id', $this->id)
                ->where('revenues.revenue_category_id', $monthlyCatId)
                ->where(function ($q) {
                    $q->whereNull('revenues.payment_status')
                        ->orWhere('revenues.payment_status', 'confirmed');
                })
                ->where('revenue_adjustments.type', 'waiver')
                ->sum('revenue_adjustments.amount');
        }

        $paidNet = max(0.0, $paidGross - $refunds);
        $holdNet = (float) $this->monthlyFeeHoldAmount();
        $expectedNet = max(0.0, $expectedBase - $waivers);
        return max(0.0, $expectedNet - $paidNet - $holdNet);
    }

    public function getComputedDueAmountAttribute(): float
    {
        return $this->computeMonthlyDue();
    }

    public function monthlyFeeCategoryId(): ?int
    {
        $classCategoryId = $this->classRoom?->monthly_fee_revenue_category_id;
        if ($classCategoryId) {
            return (int) $classCategoryId;
        }

        static $resolved = false;
        static $defaultCategoryId = null;

        if (! $resolved) {
            $defaultCategoryId = RevenueCategory::query()
                ->where('active', true)
                ->where(function ($q) {
                    $q->where('name', 'Monthly Fee')
                        ->orWhere('payment_type', 'monthly');
                })
                ->orderByRaw("CASE WHEN name = 'Monthly Fee' THEN 0 ELSE 1 END")
                ->value('id');
            $resolved = true;
        }

        return $defaultCategoryId ? (int) $defaultCategoryId : null;
    }

    public function monthlyCyclesCountToNow(): int
    {
        $startDate = $this->fee_start_date ?: $this->joining_date ?: optional($this->created_at)->toDateString();
        if (! $startDate) return 0;
        $start = Carbon::parse($startDate)->startOfDay();
        $now = now();
        return $now->lt($start) ? 0 : (int) ($start->diffInMonths($now) + 1);
    }

    public function monthlyCyclesToNow(): array
    {
        $cycles = [];
        $count = $this->monthlyCyclesCountToNow();
        if ($count <= 0) return $cycles;
        $startDate = $this->fee_start_date ?: $this->joining_date ?: optional($this->created_at)->toDateString();
        if (! $startDate) return $cycles;
        $start = Carbon::parse($startDate)->startOfDay();
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
        $paidGross = (float) Revenue::query()
            ->where('student_id', $this->id)
            ->where('revenue_category_id', $catId)
            ->where(function ($q) {
                $q->whereNull('payment_status')
                    ->orWhere('payment_status', 'confirmed');
            })
            ->sum('amount');

        $refunds = (float) RevenueAdjustment::query()
            ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
            ->where('revenues.student_id', $this->id)
            ->where('revenues.revenue_category_id', $catId)
            ->where(function ($q) {
                $q->whereNull('revenues.payment_status')
                    ->orWhere('revenues.payment_status', 'confirmed');
            })
            ->where('revenue_adjustments.type', 'refund')
            ->sum('revenue_adjustments.amount');

        return max(0.0, $paidGross - $refunds);
    }

    public function monthlyFeeWaiverAmount(): float
    {
        $catId = $this->monthlyFeeCategoryId();
        if (! $catId) return 0.0;

        return (float) RevenueAdjustment::query()
            ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
            ->where('revenues.student_id', $this->id)
            ->where('revenues.revenue_category_id', $catId)
            ->where(function ($q) {
                $q->whereNull('revenues.payment_status')
                    ->orWhere('revenues.payment_status', 'confirmed');
            })
            ->where('revenue_adjustments.type', 'waiver')
            ->sum('revenue_adjustments.amount');
    }

    public function monthlyFeeHoldAmount(): float
    {
        $catId = $this->monthlyFeeCategoryId();
        if (! $catId) return 0.0;

        $holdGross = (float) Revenue::query()
            ->where('student_id', $this->id)
            ->where('revenue_category_id', $catId)
            ->whereIn('payment_status', ['hold', 'pending'])
            ->sum('amount');

        $refunds = (float) RevenueAdjustment::query()
            ->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id')
            ->where('revenues.student_id', $this->id)
            ->where('revenues.revenue_category_id', $catId)
            ->whereIn('revenues.payment_status', ['hold', 'pending'])
            ->where('revenue_adjustments.type', 'refund')
            ->sum('revenue_adjustments.amount');

        return max(0.0, $holdGross - $refunds);
    }

    public function monthlyFeePaidCyclesCount(): int
    {
        $fee = (float) $this->monthly_fee;
        if ($fee <= 0) return 0;
        $covered = (float) $this->monthlyFeePaidAmount() + (float) $this->monthlyFeeWaiverAmount();
        return (int) floor($covered / $fee);
    }
}
