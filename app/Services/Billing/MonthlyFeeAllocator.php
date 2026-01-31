<?php

namespace App\Services\Billing;

use App\Models\Revenue;
use App\Models\RevenueAdjustment;
use App\Models\Student;
use App\Models\StudentMonthlyFeeOverride;
use App\Models\StudentMonthFeeAllocation;
use App\Models\StudentPromotionHistory;
use Carbon\Carbon;

class MonthlyFeeAllocator
{
    /**
     * Resolve the monthly fee for a given month start, honoring:
     * - explicit month override (current-month old/new selection)
     * - promotion/demotion history: old fee stays for current & previous months, new fee applies from next month
     */
    private function resolveMonthlyFeeForMonth(
        Student $student,
        Carbon $monthStart,
        array $overridesByKey,
        $histories
    ): float {
        $key = $monthStart->format('Y-m');
        if (isset($overridesByKey[$key])) {
            return (float) $overridesByKey[$key];
        }

        $baseline = null;
        $fee = null;
        foreach ($histories as $h) {
            if ($baseline === null) {
                $baseline = (float) ($h->fromClassRoom?->monthly_fee ?? 0);
            }
            $effectiveFrom = Carbon::parse($h->created_at)->addMonthNoOverflow()->startOfMonth();
            if ($monthStart->greaterThanOrEqualTo($effectiveFrom)) {
                $fee = (float) ($h->toClassRoom?->monthly_fee ?? 0);
            }
        }

        $resolved = $fee ?? $baseline;
        if (! $resolved || $resolved <= 0) {
            $resolved = (float) ($student->classRoom?->monthly_fee ?? $student->monthly_fee ?? 0);
        }
        return (float) $resolved;
    }

    /**
     * Build a ledger of months from fee_start_date to now + optional horizon, and mark paid/partial via existing allocations and legacy revenues.
     * Returns array keyed by 'Y-m' with fields: month, year, due, paid, status ('unpaid'|'partially_paid'|'paid'), remaining.
     * Legacy revenues without allocations are applied oldest-first.
     *
     * @param Student $student
     * @param int $horizonMonths number of future months to include for advance suggestions
     * @return array<string,array<string,mixed>>
     */
    public function buildLedger(Student $student, int $horizonMonths = 12): array
    {
        $start = $student->fee_start_date ? Carbon::parse($student->fee_start_date)->startOfDay() : null;
        if (! $start) return [];

        $histories = StudentPromotionHistory::query()
            ->with([
                'fromClassRoom:id,monthly_fee',
                'toClassRoom:id,monthly_fee',
            ])
            ->where('student_id', $student->id)
            ->whereIn('action', ['promote', 'demote'])
            ->orderBy('created_at')
            ->get();

        $overridesByKey = StudentMonthlyFeeOverride::query()
            ->where('student_id', $student->id)
            ->get(['year', 'month', 'fee_amount'])
            ->mapWithKeys(function ($o) {
                $key = sprintf('%04d-%02d', (int) $o->year, (int) $o->month);
                return [$key => (float) $o->fee_amount];
            })
            ->all();

        $now = now();
        $monthsCount = $now->lt($start) ? 0 : (int) ($start->diffInMonths($now) + 1);
        // Include future horizon
        $totalMonths = $monthsCount + max(0, $horizonMonths);

        // Build base ledger
        $ledger = [];
        for ($i = 0; $i < $totalMonths; $i++) {
            $date = $start->copy()->addMonthsNoOverflow($i);
            $key = $date->format('Y-m');
            $fee = $this->resolveMonthlyFeeForMonth($student, $date->copy()->startOfMonth(), $overridesByKey, $histories);
            if ($fee <= 0) {
                return [];
            }
            $ledger[$key] = [
                'month' => (int) $date->format('n'),
                'year' => (int) $date->format('Y'),
                'due' => $fee,
                'paid' => 0.0,
                'status' => 'unpaid',
                'remaining' => $fee,
            ];
        }

        // Apply existing allocations first
        $allocs = StudentMonthFeeAllocation::query()
            ->join('revenues', 'revenues.id', '=', 'student_month_fee_allocations.revenue_id')
            ->where('student_month_fee_allocations.student_id', $student->id)
            ->where(function ($q) {
                $q->whereNull('revenues.payment_status')
                    ->orWhere('revenues.payment_status', 'confirmed');
            })
            ->orderBy('year')
            ->orderBy('month')
            ->select('student_month_fee_allocations.*')
            ->get();
        foreach ($allocs as $a) {
            $key = sprintf('%04d-%02d', (int)$a->year, (int)$a->month);
            if (!isset($ledger[$key])) continue;
            $ledger[$key]['paid'] += (float) $a->applied_amount;
            $ledger[$key]['remaining'] = max(0.0, $ledger[$key]['due'] - $ledger[$key]['paid']);
            $ledger[$key]['status'] = $this->statusFromAmounts($ledger[$key]['due'], $ledger[$key]['paid']);
        }

        // Legacy revenues (without allocations): apply oldest-first
        $monthlyCatId = $student->monthlyFeeCategoryId();
        if ($monthlyCatId) {
            $legacy = Revenue::query()
                ->where('student_id', $student->id)
                ->where('revenue_category_id', $monthlyCatId)
                ->where(function ($q) {
                    $q->whereNull('payment_status')
                        ->orWhere('payment_status', 'confirmed');
                })
                ->orderBy('paid_at')
                ->get();
            foreach ($legacy as $rev) {
                // Skip amounts that already have allocations
                $allocatedSum = StudentMonthFeeAllocation::query()->where('revenue_id', $rev->id)->sum('applied_amount');
                $refundedSum = (float) RevenueAdjustment::query()
                    ->where('revenue_id', $rev->id)
                    ->where('type', 'refund')
                    ->sum('amount');
                $remainingAmount = max(0.0, (float)$rev->amount - (float)$refundedSum - (float)$allocatedSum);
                if ($remainingAmount <= 0) continue;
                // Apply to oldest unpaid/partial months
                foreach ($ledger as &$m) {
                    if ($remainingAmount <= 0) break;
                    if ($m['status'] === 'paid') continue;
                    $apply = min($remainingAmount, $m['remaining']);
                    $m['paid'] += $apply;
                    $m['remaining'] = max(0.0, $m['due'] - $m['paid']);
                    $m['status'] = $this->statusFromAmounts($m['due'], $m['paid']);
                    $remainingAmount -= $apply;
                }
                unset($m);
            }
        }

        return $ledger;
    }

    private function statusFromAmounts(float $due, float $paid): string
    {
        if ($paid <= 0.0) return 'unpaid';
        if ($paid + 0.001 < $due) return 'partially_paid'; // allow tiny epsilon
        return 'paid';
    }

    /**
     * Core allocation rules per requirements. Returns array of allocations to persist and summary.
     *
     * @param Student $student
     * @param float $amount
     * @param array<int,array{month:int,year:int}> $selectedAdvanceMonths ordered future months selected by cashier
     * @return array{allocations: array<int,array{month:int,year:int,type:string,applied_amount:float,is_partial:bool,remaining_for_month:float}>,
     *               summary: array{total_applied:float,unallocated_balance:float,paid_due_months:array,advance_months:array,errors:array}}
     */
    public function allocate(Student $student, float $amount, array $selectedAdvanceMonths = []): array
    {
        $ledger = $this->buildLedger($student, 24);
        if (empty($ledger)) {
            return [
                'allocations' => [],
                'summary' => [
                    'total_applied' => 0.0,
                    'unallocated_balance' => $amount,
                    'paid_due_months' => [],
                    'advance_months' => [],
                    'errors' => ['Monthly fee is not set for this student.'],
                ],
            ];
        }
        $allocations = [];
        $paidDueMonths = [];
        $advanceMonthsCovered = [];
        $errors = [];

        $remainingAmount = $amount;

        // 1) Pay oldest dues first
        foreach ($ledger as $key => &$m) {
            if ($remainingAmount <= 0) break;
            // Past months only (<= now)
            $monthDate = Carbon::createFromDate($m['year'], $m['month'], 1)->startOfMonth();
            if ($monthDate->gt(now()->startOfMonth())) break; // stop at current month
            if (in_array($m['status'], ['unpaid','partially_paid'], true)) {
                $toApply = min($remainingAmount, $m['remaining']);
                $isPartial = ($toApply + 0.001) < $m['remaining'];
                $remainingAfter = max(0.0, $m['remaining'] - $toApply);

                // Update local ledger state so subsequent checks (advance eligibility) are based on this payment too.
                $m['paid'] = (float) ($m['paid'] ?? 0.0) + (float) $toApply;
                $m['remaining'] = $remainingAfter;
                $m['status'] = $this->statusFromAmounts((float) ($m['due'] ?? 0.0), (float) $m['paid']);

                $allocations[] = [
                    'month' => $m['month'],
                    'year' => $m['year'],
                    'type' => 'due',
                    'applied_amount' => round($toApply, 2),
                    'is_partial' => $isPartial,
                    'remaining_for_month' => round($remainingAfter, 2),
                ];
                $paidDueMonths[] = [
                    'month' => $m['month'],
                    'year' => $m['year'],
                    'partial' => $isPartial,
                ];
                $remainingAmount -= $toApply;
                if ($isPartial) {
                    // Stop if amount not enough to complete this month
                    break;
                }
            }
        }
        unset($m);

        // 2) Handle advance months in chronological order
        if ($remainingAmount > 0 && !empty($selectedAdvanceMonths)) {
            // Enforce rule: cannot select future month if earlier month unpaid/partial remains
            $hasUnpaidEarlier = false;
            foreach ($ledger as $key => $m) {
                    $monthDate = Carbon::createFromDate($m['year'], $m['month'], 1)->startOfMonth();
                if ($monthDate->gt(now()->startOfMonth())) break; // only past months
                if (in_array($m['status'], ['unpaid','partially_paid'], true)) {
                    $hasUnpaidEarlier = true; break;
                }
            }
            if ($hasUnpaidEarlier) {
                $errors[] = 'Advance months cannot be selected while dues exist.';
            } else {
                foreach ($selectedAdvanceMonths as $am) {
                    if ($remainingAmount <= 0) break;
                    $m = (int) ($am['month'] ?? 0);
                    $y = (int) ($am['year'] ?? 0);
                    if ($m < 1 || $m > 12 || $y < 2000) continue;
                    $key = sprintf('%04d-%02d', $y, $m);
                    $monthDue = isset($ledger[$key]) ? (float) ($ledger[$key]['due'] ?? 0) : (float) ($student->monthly_fee ?? 0);
                    $monthRemaining = isset($ledger[$key]) ? (float) ($ledger[$key]['remaining'] ?? $monthDue) : $monthDue;
                    $monthStatus = isset($ledger[$key]) ? (string) ($ledger[$key]['status'] ?? 'unpaid') : 'unpaid';

                    if ($monthDue <= 0 || $monthRemaining <= 0) {
                        $errors[] = 'Monthly fee is not set for the selected month.';
                        break;
                    }
                    if ($monthStatus === 'paid') {
                        continue;
                    }
                    $toApply = min($remainingAmount, $monthRemaining);
                    $isPartial = ($toApply + 0.001) < $monthRemaining;
                    $remainingAfter = max(0.0, $monthRemaining - $toApply);
                    $allocations[] = [
                        'month' => $m,
                        'year' => $y,
                        'type' => 'advance',
                        'applied_amount' => round($toApply, 2),
                        'is_partial' => $isPartial,
                        'remaining_for_month' => round($remainingAfter, 2),
                    ];
                    $advanceMonthsCovered[] = [
                        'month' => $m,
                        'year' => $y,
                        'partial' => $isPartial,
                    ];
                    $remainingAmount -= $toApply;
                    if ($isPartial) break; // stop on partial for last selected month
                }
            }
        }

        // 3) Auto-roll any remaining amount into next future months (advance), month-by-month.
        // This ensures: if cashier pays more than a month's fee, it continues to the after-next month.
        if ($remainingAmount > 0 && empty($errors)) {
            // Re-check dues rule (same as advance selection)
            $hasUnpaidEarlier = false;
            foreach ($ledger as $m) {
                $monthDate = Carbon::createFromDate($m['year'], $m['month'], 1)->startOfMonth();
                if ($monthDate->gt(now()->startOfMonth())) break;
                if (in_array($m['status'], ['unpaid', 'partially_paid'], true)) {
                    $hasUnpaidEarlier = true;
                    break;
                }
            }

            if (! $hasUnpaidEarlier) {
                $startAfter = now()->addMonthNoOverflow()->startOfMonth();
                if (!empty($selectedAdvanceMonths)) {
                    // Start after the latest selected month
                    usort($selectedAdvanceMonths, function ($a, $b) {
                        $ya = (int) ($a['year'] ?? 0);
                        $ma = (int) ($a['month'] ?? 0);
                        $yb = (int) ($b['year'] ?? 0);
                        $mb = (int) ($b['month'] ?? 0);
                        return ($ya * 100 + $ma) <=> ($yb * 100 + $mb);
                    });
                    $last = end($selectedAdvanceMonths);
                    if (is_array($last) && isset($last['year'], $last['month'])) {
                        $startAfter = Carbon::createFromDate((int) $last['year'], (int) $last['month'], 1)
                            ->addMonthNoOverflow()
                            ->startOfMonth();
                    }
                }

                foreach ($ledger as $key => $m) {
                    if ($remainingAmount <= 0) break;
                    $monthDate = Carbon::createFromDate($m['year'], $m['month'], 1)->startOfMonth();
                    if ($monthDate->lt($startAfter)) continue;
                    if ($m['status'] === 'paid') continue;

                    $monthRemaining = (float) ($m['remaining'] ?? 0);
                    if ($monthRemaining <= 0) continue;

                    $toApply = min($remainingAmount, $monthRemaining);
                    $isPartial = ($toApply + 0.001) < $monthRemaining;
                    $remainingAfter = max(0.0, $monthRemaining - $toApply);

                    $allocations[] = [
                        'month' => (int) $m['month'],
                        'year' => (int) $m['year'],
                        'type' => 'advance',
                        'applied_amount' => round($toApply, 2),
                        'is_partial' => $isPartial,
                        'remaining_for_month' => round($remainingAfter, 2),
                    ];
                    $advanceMonthsCovered[] = [
                        'month' => (int) $m['month'],
                        'year' => (int) $m['year'],
                        'partial' => $isPartial,
                    ];
                    $remainingAmount -= $toApply;

                    if ($isPartial) {
                        break;
                    }
                }
            }
        }

        $summary = [
            'total_applied' => round($amount - $remainingAmount, 2),
            'unallocated_balance' => round($remainingAmount, 2),
            'paid_due_months' => $paidDueMonths,
            'advance_months' => $advanceMonthsCovered,
            'errors' => $errors,
        ];

        return ['allocations' => $allocations, 'summary' => $summary];
    }
}
