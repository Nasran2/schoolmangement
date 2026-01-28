<?php

namespace App\Services\Revenue;

use App\Models\RevenueCategory;
use Carbon\Carbon;

class RevenueCategoryScheduleService
{
    /**
     * Returns current cycle info for a recurring category.
     *
     * @return array{start:Carbon,due:Carbon,reminder:Carbon,interval_months:int}|null
     */
    public function currentCycle(RevenueCategory $category, ?Carbon $asOf = null): ?array
    {
        $asOf = ($asOf ?: now())->copy()->startOfDay();
        $interval = $category->intervalMonths();
        if (!$interval) {
            return null;
        }

        $anchor = $category->first_due_date
            ? Carbon::parse($category->first_due_date)->startOfDay()
            : ($category->created_at ? Carbon::parse($category->created_at)->startOfDay() : $asOf->copy());

        $due = $anchor->copy();
        $guard = 0;
        while ($due->lt($asOf) && $guard < 240) {
            $due->addMonthsNoOverflow($interval);
            $guard++;
        }

        $start = $due->copy()->subMonthsNoOverflow($interval);
        $daysBefore = (int) ($category->reminder_days_before ?? 5);
        if ($daysBefore < 0) {
            $daysBefore = 0;
        }
        $reminder = $due->copy()->subDays($daysBefore);

        return [
            'start' => $start,
            'due' => $due,
            'reminder' => $reminder,
            'interval_months' => $interval,
        ];
    }

    /**
     * Returns cycle info for an exact due date (used for cycle history drilldowns).
     *
     * @return array{start:Carbon,due:Carbon,reminder:Carbon,interval_months:int}|null
     */
    public function cycleForDueDate(RevenueCategory $category, Carbon $due): ?array
    {
        $interval = $category->intervalMonths();
        if (!$interval) {
            return null;
        }

        $due = $due->copy()->startOfDay();
        $start = $due->copy()->subMonthsNoOverflow($interval);
        $daysBefore = (int) ($category->reminder_days_before ?? 5);
        if ($daysBefore < 0) {
            $daysBefore = 0;
        }
        $reminder = $due->copy()->subDays($daysBefore);

        return [
            'start' => $start,
            'due' => $due,
            'reminder' => $reminder,
            'interval_months' => $interval,
        ];
    }

    /**
     * @return array<int, array{start:Carbon,due:Carbon,reminder:Carbon,interval_months:int}>
     */
    public function recentCycles(RevenueCategory $category, int $count = 6, ?Carbon $asOf = null): array
    {
        $count = max(1, min(24, $count));
        $base = $this->currentCycle($category, $asOf);
        if (!$base) {
            return [];
        }

        $interval = (int) $base['interval_months'];
        $cycles = [];
        $due = $base['due']->copy();
        for ($i = 0; $i < $count; $i++) {
            $cycles[] = $this->cycleForDueDate($category, $due);
            $due->subMonthsNoOverflow($interval);
        }

        return array_values(array_filter($cycles));
    }
}
