<?php

namespace App\Http\Controllers\Rbac;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('rbac.roles.index', [
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function edit(Role $role): View
    {
        $permissions = Permission::query()->orderBy('name')->get();

        // Group permissions by category for easier management in the UI
        $groups = [
            'Dashboard' => ['dashboard.'],
            'Settings' => ['settings.'],
            'RBAC' => ['roles.'],
            'Activity Logs' => ['audit_logs.'],
            'Students' => ['students.'],
            'Teachers' => ['teachers.'],
            'Revenue' => ['revenue.'],
            'Expense' => ['expense.'],
            'Classrooms' => ['classrooms.'],
            'Reports' => ['reports.'],
            'SMS' => ['sms.'],
        ];

        $permissionGroups = [];
        foreach ($permissions as $perm) {
            $placed = false;
            foreach ($groups as $label => $prefixes) {
                foreach ($prefixes as $prefix) {
                    if (str_starts_with($perm->name, $prefix)) {
                        $permissionGroups[$label][] = $perm;
                        $placed = true;
                        break 2;
                    }
                }
            }
            if (! $placed) {
                $permissionGroups['Other'][] = $perm;
            }
        }

        $labels = [
            // Dashboard
            'dashboard.view' => 'Access Dashboard',
            'dashboard.widget.total_revenue.view' => 'Widget: Total Revenue',
            'dashboard.widget.total_expenses.view' => 'Widget: Total Expenses',
            'dashboard.widget.net_profit.view' => 'Widget: Net Profit',
            'dashboard.widget.cash_flow.view' => 'Widget: Cash Flow Chart',
            'dashboard.widget.revenue_vs_expense.view' => 'Widget: Revenue vs Expense',
            'dashboard.widget.due_students.view' => 'Widget: Due Students List',
            'dashboard.widget.revenue_category_breakdown.view' => 'Widget: Revenue Breakdown',
            'dashboard.widget.expense_category_breakdown.view' => 'Widget: Expense Breakdown',
            'dashboard.widget.upcoming_teacher_payments.view' => 'Widget: Upcoming Teacher Salaries',
            'dashboard.widget.enrollment_trend.view' => 'Widget: Enrollment Trend',
            'dashboard.widget.notifications.view' => 'Widget: Notifications',
            'dashboard.widget.recent_activity.view' => 'Widget: Recent Activity',

            // Settings
            'settings.manage' => 'Manage All Settings',

            // Classrooms
            'classrooms.view' => 'View Classrooms',
            'classrooms.create' => 'Create Classrooms',
            'classrooms.update' => 'Edit Classrooms',
            'classrooms.delete' => 'Delete Classrooms',

            // Revenue
            'revenue.add' => 'Add Revenue',
            'revenue.manage' => 'Manage Revenue Records',
            'revenue.delete' => 'Delete Revenue Records',
            'revenue.reports.download' => 'Download Revenue Reports',
            'revenue.categories.manage' => 'Manage Revenue Categories',

            // Expense
            'expense.add' => 'Add Expense',
            'expense.manage' => 'Manage Expense Records',
            'expense.delete' => 'Delete Expense Records',
            'expense.reports.download' => 'Download Expense Reports',
            'expense.categories.manage' => 'Manage Expense Categories',

            // Students
            'students.add' => 'Add Students',
            'students.manage' => 'Manage Students',
            'students.delete' => 'Delete Students',
            'students.bulk_upload' => 'Bulk Upload Students',
            'students.promote' => 'Promote Students',
            'students.demote' => 'Demote Students',
            'students.reports.download' => 'Download Student Reports',

            // Teachers
            'teachers.add' => 'Add Teachers',
            'teachers.manage' => 'Manage Teachers',
            'teachers.delete' => 'Delete Teachers',
            'teachers.bulk_upload' => 'Bulk Upload Teachers',
            'teachers.salary.pay' => 'Manage Salary Payments',
            'teachers.salary.summary.view' => 'View Salary Due & Upcoming',
            'teachers.salary.components' => 'Manage Salary Components',
            'teachers.reports.download' => 'Download Teacher Reports',

            // SMS
            'sms.send.individual' => 'Send Individual SMS',
            'sms.send.bulk' => 'Send Bulk SMS',
            'sms.send.selected_students' => 'Send to Selected Students',
            'sms.send.selected_grades' => 'Send to Selected Grades',

            // Reports
            'reports.view' => 'Access Reports Module',
            'reports.download' => 'Download Reports (Global)',
            'reports.revenue.view' => 'View Revenue Report',
            'reports.expense.view' => 'View Expense Report',
            'reports.financial.view' => 'View Financial Report',
            'reports.student_due.view' => 'View Student Due Report',
            'reports.student_due_aging.view' => 'View Due Aging Report',
            'reports.student_top_due.view' => 'View Top Due Students',
            'reports.teacher_epf.view' => 'View Teacher EPF Report',
            'reports.teacher_etf.view' => 'View Teacher ETF Report',
            'reports.fee_collection_summary.view' => 'View Fee Collection Summary',
            'reports.fee_collection_by_class.view' => 'View Collection by Class',
            'reports.fee_collection_by_category.view' => 'View Collection by Category',
            'reports.fee_collection_vs_expected.view' => 'View Collection vs Expected',
            'reports.fee_discounts.view' => 'View Fee Discounts Report',
            'reports.fee_refunds.view' => 'View Fee Refunds Report',
            'reports.seminars_collection.view' => 'View Seminars Collection',
            'reports.extra_classes_collection.view' => 'View Extra Classes Collection',

            // RBAC
            'roles.manage' => 'Manage Roles & Permissions',

            // Activity Logs
            'audit_logs.view' => 'View Activity Logs',
        ];


        return view('rbac.roles.edit', [
            'role' => $role,
            'permissions' => $permissions,
            'permissionGroups' => $permissionGroups,
            'permissionLabels' => $labels,
            'rolePermissions' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $before = $role->permissions->pluck('name')->all();

        $next = $validated['permissions'] ?? [];
        sort($before);
        sort($next);

        $role->syncPermissions($next);

        $added = array_values(array_diff($next, $before));
        $removed = array_values(array_diff($before, $next));

        app(AuditLogger::class)->log(
            'rbac.role.permissions.update',
            $role,
            'Role permissions updated',
            [
                'role' => $role->name,
                'added' => $added,
                'removed' => $removed,
            ]
        );

        return back()->with('status', 'Role permissions updated.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
        ]);

        $role = Role::create(['name' => $validated['name']]);

        app(AuditLogger::class)->log(
            'rbac.role.create',
            $role,
            'Role created',
            [
                'role' => $role->name,
            ]
        );

        return back()->with('status', 'Role created.');
    }
}
