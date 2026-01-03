<?php

namespace App\Services\Billing;

use App\Models\Revenue;
use App\Models\StudentMonthFeeAllocation;

class RevenueAdjustmentService
{
    /**
     * Apply a refund by reversing monthly fee allocations for a revenue (LIFO).
     * This keeps allocation-based reports (Collected vs Expected) consistent.
     */
    public function reverseAllocationsForRefund(Revenue $revenue, float $refundAmount): void
    {
        if ($refundAmount <= 0) {
            return;
        }

        $student = $revenue->student;
        $monthlyFee = (float) ($student?->monthly_fee ?? 0);

        $remaining = $refundAmount;

        $allocations = StudentMonthFeeAllocation::query()
            ->where('revenue_id', $revenue->id)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        foreach ($allocations as $alloc) {
            if ($remaining <= 0) {
                break;
            }

            $currentApplied = (float) ($alloc->applied_amount ?? 0);
            if ($currentApplied <= 0) {
                continue;
            }

            $toReverse = min($remaining, $currentApplied);
            $newApplied = max(0.0, $currentApplied - $toReverse);

            $alloc->applied_amount = $newApplied;

            if ($monthlyFee > 0) {
                $alloc->remaining_for_month = max(0.0, $monthlyFee - $newApplied);
                $alloc->is_partial = ($newApplied + 0.001) < $monthlyFee;
            }

            // Keep the allocation record only if it still contributes
            if ($newApplied <= 0.0001) {
                $alloc->delete();
            } else {
                $alloc->save();
            }

            $remaining -= $toReverse;
        }
    }
}
