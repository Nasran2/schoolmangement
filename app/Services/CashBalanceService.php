<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\OpeningBalance;
use App\Models\OwnerTransfer;
use App\Models\Revenue;
use App\Models\TeacherSalaryPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashBalanceService
{
    public function balanceOn(string $date): float
    {
        return $this->calculate($date, false);
    }

    public function openingOn(string $date): float
    {
        return $this->calculate($date, true);
    }

    private function calculate(string $date, bool $opening): float
    {
        $comparison = $opening ? '<' : '<=';
        // Dated opening-balance records represent the balance at the end of that day.
        $snapshot = OpeningBalance::query()->whereDate('date', $comparison, $date)->orderByDesc('date')->first();
        $base = $snapshot ? (float) $snapshot->amount : 0.0;
        $from = $snapshot?->date->copy()->addDay()->toDateString();

        if (! $snapshot) {
            $settings = app('settings');
            $asOf = (string) $settings->get('opening_balance.as_of', '');
            if ($asOf !== '' && $asOf <= $date) {
                $base = (float) $settings->get('opening_balance.cash', 0);
                $from = Carbon::parse($asOf)->toDateString();
            }
        }

        $cash = fn ($q, string $column) => $q->where(fn ($q) => $q->where($column, 'cash')->orWhereNull($column));
        $period = function ($q, string $column) use ($from, $date, $comparison) {
            $q->whereDate($column, $comparison, $date);
            if ($from !== null) {
                $q->whereDate($column, '>=', $from);
            }

            return $q;
        };

        $income = $cash($period(Revenue::query(), 'paid_at'), 'payment_method')
            ->whereNull('cancelled_at')
            ->where(fn ($q) => $q->whereIn('payment_status', ['confirmed', 'paid'])->orWhereNull('payment_status'))
            ->sum('amount');
        $refunds = $cash($period(DB::table('revenue_adjustments')->join('revenues', 'revenues.id', '=', 'revenue_adjustments.revenue_id'), 'revenues.paid_at'), 'revenues.payment_method')
            ->where('revenue_adjustments.type', 'refund')->sum('revenue_adjustments.amount');
        $expenses = $cash($period(Expense::query(), 'expense_date'), 'payment_method')->sum('amount');
        $salaries = $cash($period(TeacherSalaryPayment::query(), 'paid_at'), 'payment_method')->sum('amount');
        $transfers = $period(OwnerTransfer::query(), 'transferred_at')->sum('amount');

        return round($base + (float) $income - (float) $refunds - (float) $expenses - (float) $salaries - (float) $transfers, 2);
    }
}
