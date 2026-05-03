<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (Schema::hasColumn('students', 'active') && Schema::hasColumn('students', 'alumni') && Schema::hasColumn('students', 'class_room_id')) {
                    $table->index(['active', 'alumni', 'class_room_id'], 'students_active_alumni_class_idx');
                }
                if (Schema::hasColumn('students', 'due_amount')) {
                    $table->index('due_amount', 'students_due_amount_idx');
                }
                if (Schema::hasColumn('students', 'name')) {
                    $table->index('name', 'students_name_idx');
                }
            });
        }

        if (Schema::hasTable('revenues')) {
            Schema::table('revenues', function (Blueprint $table) {
                if (Schema::hasColumn('revenues', 'payment_status') && Schema::hasColumn('revenues', 'paid_at')) {
                    $table->index(['payment_status', 'paid_at'], 'rev_status_paid_idx');
                }
                if (Schema::hasColumn('revenues', 'student_id') && Schema::hasColumn('revenues', 'revenue_category_id') && Schema::hasColumn('revenues', 'paid_at')) {
                    $table->index(['student_id', 'revenue_category_id', 'paid_at'], 'rev_student_cat_paid_idx');
                }
                if (Schema::hasColumn('revenues', 'payment_method') && Schema::hasColumn('revenues', 'cheque_date') && Schema::hasColumn('revenues', 'payment_status')) {
                    $table->index(['payment_method', 'cheque_date', 'payment_status'], 'rev_cheque_state_idx');
                }
            });
        }

        if (Schema::hasTable('teacher_salary_payments')) {
            Schema::table('teacher_salary_payments', function (Blueprint $table) {
                if (Schema::hasColumn('teacher_salary_payments', 'teacher_id') && Schema::hasColumn('teacher_salary_payments', 'paid_at')) {
                    $table->index(['teacher_id', 'paid_at'], 'tsp_teacher_paid_idx');
                }
                if (Schema::hasColumn('teacher_salary_payments', 'payment_month')) {
                    $table->index('payment_month', 'tsp_payment_month_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (Schema::hasColumn('students', 'active') && Schema::hasColumn('students', 'alumni') && Schema::hasColumn('students', 'class_room_id')) {
                    $table->dropIndex('students_active_alumni_class_idx');
                }
                if (Schema::hasColumn('students', 'due_amount')) {
                    $table->dropIndex('students_due_amount_idx');
                }
                if (Schema::hasColumn('students', 'name')) {
                    $table->dropIndex('students_name_idx');
                }
            });
        }

        if (Schema::hasTable('revenues')) {
            Schema::table('revenues', function (Blueprint $table) {
                if (Schema::hasColumn('revenues', 'payment_status') && Schema::hasColumn('revenues', 'paid_at')) {
                    $table->dropIndex('rev_status_paid_idx');
                }
                if (Schema::hasColumn('revenues', 'student_id') && Schema::hasColumn('revenues', 'revenue_category_id') && Schema::hasColumn('revenues', 'paid_at')) {
                    $table->dropIndex('rev_student_cat_paid_idx');
                }
                if (Schema::hasColumn('revenues', 'payment_method') && Schema::hasColumn('revenues', 'cheque_date') && Schema::hasColumn('revenues', 'payment_status')) {
                    $table->dropIndex('rev_cheque_state_idx');
                }
            });
        }

        if (Schema::hasTable('teacher_salary_payments')) {
            Schema::table('teacher_salary_payments', function (Blueprint $table) {
                $table->dropIndex('tsp_teacher_paid_idx');
                if (Schema::hasColumn('teacher_salary_payments', 'payment_month')) {
                    $table->dropIndex('tsp_payment_month_idx');
                }
            });
        }
    }
};
