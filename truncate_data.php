<?php
DB::statement('SET FOREIGN_KEY_CHECKS=0;');

$tablesToTruncate = [
    'audit_logs',
    'class_room_revenue_category',
    'class_rooms',
    'expense_categories',
    'expenses',
    'extra_class_students',
    'extra_class_teacher_payments',
    'extra_classes',
    'opening_balances',
    'revenue_adjustments',
    'revenue_categories',
    'revenues',
    'seminar_class_room',
    'seminar_students',
    'seminar_teacher_payments',
    'seminar_teacher_payouts',
    'seminars',
    'student_month_fee_allocations',
    'student_month_fee_credits',
    'student_monthly_fee_overrides',
    'student_promotion_histories',
    'students',
    'teacher_salary_advance_settlements',
    'teacher_salary_advances',
    'teacher_salary_payments',
    'teachers',
    'visiting_teachers'
];

foreach ($tablesToTruncate as $table) {
    DB::table($table)->truncate();
    echo "Truncated $table\n";
}

DB::statement('SET FOREIGN_KEY_CHECKS=1;');
