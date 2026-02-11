<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
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

            // Students
            'students.add',
            'students.manage',
            'students.delete',
            'students.bulk_upload',
            'students.promote',
            'students.demote',
            'students.reports.download',

            // Teachers
            'teachers.add',
            'teachers.manage',
            'teachers.delete',
            'teachers.bulk_upload',
            'teachers.salary.pay',
            'teachers.salary.summary.view',
            'teachers.salary.amounts.view',
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

            // RBAC
            'roles.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName);
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdminRole->syncPermissions(Permission::all());

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->syncPermissions(Permission::all());

        $user = User::query()->updateOrCreate(
            ['email' => 'admin@school.local'],
            [
                'name' => 'admin',
                'username' => 'admin',
                'password' => Hash::make('admin'),
            ]
        );

        if (! $user->hasRole($superAdminRole)) {
            $user->assignRole($superAdminRole);
        }
    }
}
