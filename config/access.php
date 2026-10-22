<?php

return [
    // Dashboard
    'dashboard.view',
    // Dashboard widgets
    'dashboard.widget.total_revenue.view',
    'dashboard.widget.total_expenses.view',
    'dashboard.widget.net_profit.view',
    'dashboard.widget.cash_flow.view',
    'dashboard.widget.revenue_vs_expense.view',
    'dashboard.widget.due_students.view',
    'dashboard.widget.revenue_category_breakdown.view',
    'dashboard.widget.expense_category_breakdown.view',
    'dashboard.widget.upcoming_teacher_payments.view',
    'dashboard.widget.enrollment_trend.view',
    'dashboard.widget.notifications.view',
    'dashboard.widget.recent_activity.view',
    'dashboard.widget.exam_fees.view',
    'dashboard.widget.facility_fees.view',

    // Settings
    'settings.manage',
    'settings.general.manage',
    'settings.status.view',
    'settings.promotion.manage',
    'settings.printer.manage',
    'settings.sms.manage',
    'settings.email.manage',
    'settings.salary_components.manage',
    'settings.backups.manage',
    'settings.opening_balance.manage',
    'settings.opening_balance.reset',

    // Class Rooms
    'classrooms.view',
    'classrooms.create',
    'classrooms.update',
    'classrooms.delete',
    'class_fees.view',

    // Revenue
    'revenue.add',
    'revenue.manage',
    'revenue.delete',
    'revenue.reports.download',
    'revenue.categories.manage',

    // Expense
    'expense.add',
    'expense.manage',
    'expense.delete',
    'expense.reports.download',
    'expense.categories.manage',
    'owner_transfers.manage',

    // Students
    'students.add',
    'students.manage',
    'students.delete',
    'students.bulk_upload',
    'students.promote',
    'students.demote',
    'students.reports.download',

    // Teachers
    'hr.directory.view',
    'teacher_evaluations.view',
    'teacher_evaluations.manage',
    'teacher_evaluations.export',
    'teacher_evaluations.delete',
    'teachers.add',
    'teachers.manage',
    'teachers.delete',
    'teachers.bulk_upload',
    'teachers.salary.pay',
    'teachers.salary.summary.view',
    'teachers.salary.amounts.view',
    'teachers.salary.components',
    'teachers.reports.download',

    // SMS
    'sms.send.individual',
    'sms.send.bulk',
    'sms.send.selected_students',
    'sms.send.selected_grades',

    // Reports (global)
    'reports.view',
    'reports.download',

    // Reports (granular view)
    'reports.revenue.view',
    'reports.expense.view',
    'reports.outflows.view',
    'reports.financial.view',
    'reports.daily_ledger.view',
    'reports.cash_transactions.view',
    'reports.bank_transactions.view',
    'reports.cheque_history.view',
    'reports.student_due.view',
    'reports.student_due_aging.view',
    'reports.student_top_due.view',
    'reports.teacher_epf.view',
    'reports.teacher_etf.view',
    'reports.company_epf.view',
    'reports.epf_etf_totals.view',
    'reports.fee_collection_summary.view',
    'reports.fee_collection_by_class.view',
    'reports.fee_collection_by_category.view',
    'reports.fee_collection_vs_expected.view',
    'reports.fee_discounts.view',
    'reports.fee_refunds.view',
    'reports.seminars_collection.view',
    'reports.extra_classes_collection.view',

    // Activity Logs
    'audit_logs.view',

    // User accounts and additional modules
    'users.manage',
    'seminars.manage',
    'extra_classes.manage',
    'visiting_teachers.manage',

    // RBAC
    'roles.manage',
];
