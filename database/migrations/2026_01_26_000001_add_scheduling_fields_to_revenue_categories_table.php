<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revenue_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('revenue_categories', 'first_due_date')) {
                $table->date('first_due_date')->nullable()->after('interval_months');
            }
            if (!Schema::hasColumn('revenue_categories', 'reminder_days_before')) {
                $table->unsignedSmallInteger('reminder_days_before')->default(5)->after('first_due_date');
            }
            if (!Schema::hasColumn('revenue_categories', 'default_amount')) {
                $table->decimal('default_amount', 10, 2)->nullable()->after('reminder_days_before');
            }
        });
    }

    public function down(): void
    {
        Schema::table('revenue_categories', function (Blueprint $table) {
            if (Schema::hasColumn('revenue_categories', 'default_amount')) {
                $table->dropColumn('default_amount');
            }
            if (Schema::hasColumn('revenue_categories', 'reminder_days_before')) {
                $table->dropColumn('reminder_days_before');
            }
            if (Schema::hasColumn('revenue_categories', 'first_due_date')) {
                $table->dropColumn('first_due_date');
            }
        });
    }
};
