<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_salary_payments')) {
            return;
        }

        Schema::table('teacher_salary_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('teacher_salary_payments', 'employee_epf_amount')) {
                $table->decimal('employee_epf_amount', 12, 2)->nullable()->after('total_deductions');
            }

            if (! Schema::hasColumn('teacher_salary_payments', 'employer_epf_amount')) {
                $table->decimal('employer_epf_amount', 12, 2)->nullable()->after('employee_epf_amount');
            }

            if (! Schema::hasColumn('teacher_salary_payments', 'employer_etf_amount')) {
                $table->decimal('employer_etf_amount', 12, 2)->nullable()->after('employer_epf_amount');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teacher_salary_payments')) {
            return;
        }

        Schema::table('teacher_salary_payments', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_salary_payments', 'employee_epf_amount')) {
                $table->dropColumn('employee_epf_amount');
            }
            if (Schema::hasColumn('teacher_salary_payments', 'employer_epf_amount')) {
                $table->dropColumn('employer_epf_amount');
            }
            if (Schema::hasColumn('teacher_salary_payments', 'employer_etf_amount')) {
                $table->dropColumn('employer_etf_amount');
            }
        });
    }
};
