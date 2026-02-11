<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_salary_payments', function (Blueprint $table) {
            $table->decimal('employee_epf_amount', 12, 2)->nullable()->after('total_deductions');
            $table->decimal('employer_epf_amount', 12, 2)->nullable()->after('employee_epf_amount');
            $table->decimal('employer_etf_amount', 12, 2)->nullable()->after('employer_epf_amount');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_salary_payments', function (Blueprint $table) {
            $table->dropColumn(['employee_epf_amount', 'employer_epf_amount', 'employer_etf_amount']);
        });
    }
};
