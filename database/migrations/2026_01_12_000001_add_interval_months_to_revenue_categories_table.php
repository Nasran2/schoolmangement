<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revenue_categories', function (Blueprint $table) {
            $table->unsignedSmallInteger('interval_months')->nullable()->after('payment_type');
        });

        // Backfill existing rows (keep one_time as null)
        DB::table('revenue_categories')->update([
            'interval_months' => DB::raw("CASE payment_type
                WHEN 'monthly' THEN 1
                WHEN '2_months' THEN 2
                WHEN '3_months' THEN 3
                WHEN '6_months' THEN 6
                WHEN 'yearly' THEN 12
                ELSE interval_months
            END"),
        ]);
    }

    public function down(): void
    {
        Schema::table('revenue_categories', function (Blueprint $table) {
            $table->dropColumn('interval_months');
        });
    }
};
