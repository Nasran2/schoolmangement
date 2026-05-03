<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('revenue_categories') || ! Schema::hasTable('class_rooms') || ! Schema::hasTable('students')) {
            return;
        }

        $monthlyCategoryId = DB::table('revenue_categories')
            ->where('active', true)
            ->where(function ($q) {
                $q->where('name', 'Monthly Fee')
                    ->orWhere('payment_type', 'monthly');
            })
            ->orderByRaw("CASE WHEN name = 'Monthly Fee' THEN 0 ELSE 1 END")
            ->value('id');

        if ($monthlyCategoryId) {
            DB::table('class_rooms')
                ->whereNull('monthly_fee_revenue_category_id')
                ->update(['monthly_fee_revenue_category_id' => $monthlyCategoryId]);
        }

        DB::table('students')
            ->whereNull('fee_start_date')
            ->whereNotNull('joining_date')
            ->update(['fee_start_date' => DB::raw('joining_date')]);
    }

    public function down(): void
    {
        if (Schema::hasTable('class_rooms')) {
            DB::table('class_rooms')
                ->whereNotNull('monthly_fee_revenue_category_id')
                ->update(['monthly_fee_revenue_category_id' => null]);
        }

        if (Schema::hasTable('students')) {
            DB::table('students')
                ->whereNotNull('fee_start_date')
                ->update(['fee_start_date' => null]);
        }
    }
};
