<?php

namespace Database\Seeders;

use App\Models\OpeningBalance;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Services\CashBalanceService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CashBalanceSeeder extends Seeder
{
    /**
     * Seed cash balance to ensure Cash in Hand is positive.
     */
    public function run(): void
    {
        // 1. Confirm any revenues that have status 'paid' or null confirmed_at
        Revenue::query()
            ->where('payment_status', 'paid')
            ->update([
                'payment_status' => 'confirmed',
                'confirmed_at' => DB::raw('COALESCE(paid_at, NOW())'),
            ]);

        // 2. Ensure an initial opening balance exists for the start of the year if none exists
        if (OpeningBalance::query()->count() === 0) {
            OpeningBalance::create([
                'date' => Carbon::now()->startOfYear()->toDateString(),
                'amount' => 500000.00,
            ]);
        }

        // 3. Check current cash balance
        $cashService = app(CashBalanceService::class);
        $currentBalance = $cashService->balanceOn(now()->toDateString());

        // If cash in hand is still low or negative, inject a confirmed cash revenue
        if ($currentBalance < 100000) {
            $category = RevenueCategory::query()->first() ?? RevenueCategory::create(['name' => 'General Revenue']);

            Revenue::create([
                'bill_no' => 'SEED-CASH-' . strtoupper(substr(uniqid(), -6)),
                'revenue_category_id' => $category->id,
                'student_id' => null,
                'amount' => 500000.00,
                'payment_method' => 'cash',
                'payment_status' => 'confirmed',
                'paid_at' => now(),
                'confirmed_at' => now(),
                'notes' => 'Seeded cash balance injection',
            ]);
        }
    }
}
