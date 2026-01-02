<?php

namespace App\Services\Billing;

use App\Models\Revenue;
use App\Models\Student;
use App\Models\StudentMonthFeeAllocation;
use Carbon\Carbon;

class MonthlyFeeAllocator
{
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
        $fee = (float) ($student->monthly_fee ?? 0);
        $start = $student->fee_start_date ? Carbon::parse($student->fee_start_date)->startOfDay() : null;
        if (! $start || $fee <= 0) return [];

        $now = now();
        $monthsCount = $now->lt($start) ? 0 : (int) ($start->diffInMonths($now) + 1);
        // Include future horizon
        $totalMonths = $monthsCount + max(0, $horizonMonths);

        // Build base ledger
        $ledger = [];
        for ($i = 0; $i < $totalMonths; $i++) {
            $date = $start->copy()->addMonthsNoOverflow($i);
            $key = $date->format('Y-m');
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
            ->where('student_id', $student->id)
            ->orderBy('year')
            ->orderBy('month')
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
                ->orderBy('paid_at')
                ->get();
            foreach ($legacy as $rev) {
                // Skip amounts that already have allocations
                $allocatedSum = StudentMonthFeeAllocation::query()->where('revenue_id', $rev->id)->sum('applied_amount');
                $remainingAmount = max(0.0, (float)$rev->amount - (float)$allocatedSum);
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
        $fee = (float) ($student->monthly_fee ?? 0);
        if ($fee <= 0) {
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

        $ledger = $this->buildLedger($student, 24);
        $allocations = [];
        $paidDueMonths = [];
        $advanceMonthsCovered = [];
        $errors = [];

        $remainingAmount = $amount;

        // 1) Pay oldest dues first
        foreach ($ledger as $key => $m) {
            if ($remainingAmount <= 0) break;
            // Past months only (<= now)
            $monthDate = Carbon::createFromDate($m['year'], $m['month'], 1);
            if ($monthDate->gt(now()->startOfMonth())) break; // stop at current month
            if (in_array($m['status'], ['unpaid','partially_paid'], true)) {
                $toApply = min($remainingAmount, $m['remaining']);
                $isPartial = ($toApply + 0.001) < $m['remaining'];
                $remainingAfter = max(0.0, $m['remaining'] - $toApply);
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

        // 2) Handle advance months in chronological order
        if ($remainingAmount > 0 && !empty($selectedAdvanceMonths)) {
            // Enforce rule: cannot select future month if earlier month unpaid/partial remains
            $hasUnpaidEarlier = false;
            foreach ($ledger as $key => $m) {
                $monthDate = Carbon::createFromDate($m['year'], $m['month'], 1);
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
                    $toApply = min($remainingAmount, $fee);
                    $isPartial = ($toApply + 0.001) < $fee;
                    $remainingAfter = max(0.0, $fee - $toApply);
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
