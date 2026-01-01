<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teacher_salary_payments', function (Blueprint $table) {
            $table->decimal('base_salary', 12, 2)->after('amount');
            $table->json('deductions')->nullable()->after('base_salary');
            $table->decimal('total_deductions', 12, 2)->default(0)->after('deductions');
            $table->string('payment_month')->nullable()->after('paid_at');
            $table->string('receipt_number')->unique()->nullable()->after('payment_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_salary_payments', function (Blueprint $table) {
            $table->dropColumn(['base_salary', 'deductions', 'total_deductions', 'payment_month', 'receipt_number']);
        });
    }
};
